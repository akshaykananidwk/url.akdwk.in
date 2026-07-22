<?php

namespace App\Http\Controllers;

use App\Jobs\RecordClick;
use App\Models\Domain;
use App\Models\Link;
use App\Services\PlanLimits;
use App\Services\Support\GeoService;
use App\Services\Support\UserAgentParser;
use App\Services\TargetingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * The redirect hot path. Link + domain lookups are cached, click recording is
 * queued — the happy path does zero synchronous writes and at most one DB
 * query (a cache miss).
 */
class RedirectController extends Controller
{
    public function __construct(
        protected TargetingEngine $targeting,
        protected PlanLimits $limits,
    ) {
    }

    public function __invoke(Request $request, string $alias)
    {
        $domain = $this->resolveDomain($request);
        $domainId = $domain?->id;

        $link = Cache::remember(
            Link::cacheKey($domainId, $alias),
            now()->addMinutes(30),
            fn () => Link::with(['domain', 'pixels', 'user:id,plan_id,plan_cycle,plan_expires_at,timezone,suspended_at'])
                ->where('alias', $alias)->where('domain_id', $domainId)->first()
        );

        if (! $link || $link->archived_at || $link->user?->suspended_at) {
            return $this->notFound($request, $domain);
        }

        hook_action('before_redirect', $link, $request);

        if ($link->disabled) {
            return response()->view('link.expired', ['link' => $link, 'reason' => 'disabled'], 410);
        }

        if ($link->isExpired()) {
            if ($link->expired_redirect) {
                return redirect()->away($link->expired_redirect, 302);
            }

            return response()->view('link.expired', ['link' => $link, 'reason' => 'expired'], 410);
        }

        // Social/crawler bots: serve the OG preview card instead of redirecting,
        // so custom social previews work. Other bots get a plain redirect and
        // are never recorded as clicks.
        $ua = new UserAgentParser($request->userAgent());
        if ($ua->isBot()) {
            if ($link->og && array_filter($link->og)) {
                return response()->view('link.og', ['link' => $link]);
            }

            return redirect()->away($link->destinationWithUtm(), 301);
        }

        // Password protection.
        if ($link->password && ! session()->get('unlocked_' . $link->id)) {
            return response()->view('link.password', ['link' => $link, 'alias' => $alias]);
        }

        return $this->finish($request, $link);
    }

    /** POST from the password form. */
    public function unlock(Request $request, string $alias)
    {
        $domain = $this->resolveDomain($request);
        $link = Link::where('alias', $alias)->where('domain_id', $domain?->id)->firstOrFail();

        if (! $link->password || ! Hash::check((string) $request->input('password'), $link->password)) {
            return back()->withErrors(['password' => __('Incorrect password.')]);
        }

        session()->put('unlocked_' . $link->id, true);

        return $this->finish($request, $link);
    }

    protected function finish(Request $request, Link $link)
    {
        // vCard links serve a downloadable .vcf instead of redirecting.
        if ($link->type === 'vcard') {
            $this->recordClick($request, $link);

            return $this->vcardResponse($link);
        }

        $destination = $this->targeting->resolve($link, $request);
        $destination = $link->destinationWithUtm($destination);
        $destination = hook_filter('redirect_destination', $destination, $link, $request);

        $this->recordClick($request, $link);

        // Deep link → intermediate page that tries the app URI then falls back.
        if ($deep = $this->targeting->deepLink($link, $request)) {
            return response()->view('link.deeplink', ['uri' => $deep[0], 'fallback' => $destination, 'link' => $link]);
        }

        // Cloaking → destination inside an iframe under the short URL.
        if ($link->cloaking) {
            return response()->view('link.cloak', ['link' => $link, 'destination' => $destination]);
        }

        // Interstitial ad page for plans without the no_ads feature (admin toggle).
        $owner = $link->user;
        if (setting('interstitial_enabled', false) && $owner && ! $this->limits->hasFeature($owner, 'no_ads')) {
            return response()->view('link.interstitial', [
                'link' => $link,
                'destination' => $destination,
                'seconds' => (int) setting('interstitial_seconds', 5),
                'ad_code' => setting('interstitial_ad_code', ''),
            ]);
        }

        // Pixel firing needs an HTML page; only used when the link has pixels.
        if ($link->pixels->isNotEmpty()) {
            return response()->view('link.pixels', ['link' => $link, 'destination' => $destination]);
        }

        return redirect()->away($destination, 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    protected function vcardResponse(Link $link)
    {
        $m = $link->meta ?? [];
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' . ($m['last_name'] ?? '') . ';' . ($m['first_name'] ?? '') . ';;;',
            'FN:' . trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')),
        ];
        if (! empty($m['organization'])) {
            $lines[] = 'ORG:' . $m['organization'];
        }
        if (! empty($m['phone'])) {
            $lines[] = 'TEL;TYPE=CELL:' . $m['phone'];
        }
        if (! empty($m['email'])) {
            $lines[] = 'EMAIL:' . $m['email'];
        }
        if (! empty($m['website'])) {
            $lines[] = 'URL:' . $m['website'];
        }
        if (! empty($m['address'])) {
            $lines[] = 'ADR;TYPE=WORK:;;' . str_replace(["\r", "\n"], ' ', $m['address']) . ';;;;';
        }
        $lines[] = 'END:VCARD';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=contact.vcf',
        ]);
    }

    protected function recordClick(Request $request, Link $link): void
    {
        // Respect the monthly tracked-clicks quota: over quota the redirect
        // still works, only analytics collection pauses.
        $owner = $link->user;
        if ($owner) {
            $quota = $owner->currentPlan()->limit('clicks_per_month');
            if ($quota >= 0) {
                $used = Cache::remember(
                    'clicks_month:' . $owner->id,
                    300,
                    fn () => app(\App\Services\StatsService::class)->clicksThisMonth($owner->id)
                );
                if ($used >= $quota) {
                    return;
                }
            }
        }

        $args = [
            $link->id,
            $link->user_id,
            $request->ip(),
            $request->userAgent(),
            $request->headers->get('referer'),
            $request->server('HTTP_ACCEPT_LANGUAGE'),
            $request->boolean('qr') || $request->query('src') === 'qr',
            now()->toDateTimeString(),
            GeoService::fromHeaders($request),
        ];

        // Reliability first: with the default "sync" queue (no worker running,
        // typical on shared hosting) record the click AFTER the response is
        // flushed to the visitor — the redirect stays fast and every click is
        // still captured. When a real queue (database/redis + worker) is
        // configured for scale, hand off to the worker instead.
        if (config('queue.default') === 'sync') {
            RecordClick::dispatchAfterResponse(...$args);
        } else {
            RecordClick::dispatch(...$args);
        }
    }

    /** Resolve the requesting host to a custom Domain row (cached). null = main app domain. */
    protected function resolveDomain(Request $request): ?Domain
    {
        $host = strtolower($request->getHost());
        $appHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($host === $appHost || $host === 'www.' . $appHost) {
            return null;
        }

        return Cache::remember('domain:' . $host, now()->addMinutes(30), function () use ($host) {
            return Domain::where('domain', $host)->first() ?: false;
        }) ?: null;
    }

    /** Root of a custom domain → its configured index redirect. */
    public function domainIndex(Request $request)
    {
        $domain = $this->resolveDomain($request);
        if ($domain && $domain->index_redirect) {
            return redirect()->away($domain->index_redirect, 302);
        }
        if ($domain) {
            return response()->view('link.domain-index', ['domain' => $domain]);
        }

        return app(\App\Http\Controllers\LandingController::class)->index($request);
    }

    protected function notFound(Request $request, ?Domain $domain)
    {
        if ($domain?->not_found_redirect) {
            return redirect()->away($domain->not_found_redirect, 302);
        }

        abort(404);
    }
}
