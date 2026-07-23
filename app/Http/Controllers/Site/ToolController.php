<?php

namespace App\Http\Controllers\Site;

use App\Models\Link;
use App\Models\User;
use App\Services\LinkService;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Free, login-free public tools. Each method renders an SEO landing page that
 * doubles as a genuinely useful tool and funnels visitors toward signing up.
 */
class ToolController extends \App\Http\Controllers\Controller
{
    /** Grid of every free tool with icon, title and one-liner. */
    public function hub()
    {
        return view('site.tools.hub');
    }

    /** Free QR code generator. Optional ?data= renders a server-side SVG. */
    public function qr(Request $request)
    {
        $data = trim((string) $request->query('data', ''));
        $svg = null;

        if ($data !== '' && mb_strlen($data) <= 2000) {
            $svg = app(QrService::class)->svg($data, ['size' => 260]);
        }

        return view('site.tools.qr', [
            'data' => mb_strlen($data) <= 2000 ? $data : '',
            'svg' => $svg,
        ]);
    }

    /** UTM campaign URL builder — fully client-side. */
    public function utm()
    {
        return view('site.tools.utm');
    }

    /** Bulk shortener form (one URL per line, up to 20). */
    public function bulk()
    {
        return view('site.tools.bulk');
    }

    /**
     * Create up to 20 guest short links, mirroring LandingController::guestShorten
     * exactly (same owner, alias generation, validation, expiry and meta).
     */
    public function bulkStore(Request $request, LinkService $links)
    {
        abort_unless(setting('guest_shorten_enabled', true), 403);

        $request->validate(['urls' => 'required|string|max:20000']);

        $owner = User::where('role', 'admin')->orderBy('id')->first();
        abort_unless($owner, 503);

        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('urls')))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->unique()
            ->take(20);

        $results = [];
        $days = (int) setting('guest_link_days', 30);

        foreach ($lines as $line) {
            try {
                $destination = $links->validateDestination($line);
            } catch (ValidationException $e) {
                $results[] = [
                    'original' => $line,
                    'short' => null,
                    'error' => collect($e->errors())->flatten()->first(),
                ];

                continue;
            }

            $link = Link::create([
                'user_id' => $owner->id,
                'alias' => $links->generateAlias(null),
                'destination' => $destination,
                'expires_at' => now()->addDays($days),
                'meta' => ['guest' => true, 'bulk' => true, 'ip' => $request->ip()],
            ]);

            $results[] = [
                'original' => $line,
                'short' => $link->shortUrl(),
                'error' => null,
            ];
        }

        return back()->with('results', $results);
    }

    /** QR scanner using the native BarcodeDetector API — client-side only. */
    public function scanner()
    {
        return view('site.tools.scanner');
    }

    /** Password / passphrase generator — client-side only. */
    public function password()
    {
        return view('site.tools.password');
    }

    /** Link expander / unshortener form. */
    public function expander()
    {
        return view('site.tools.expander');
    }

    /** Follow redirects server-side and report the full chain. */
    public function expanderCheck(Request $request)
    {
        $request->validate(['url' => 'required|url|max:2000']);

        $url = trim($request->input('url'));

        if ($this->isBlockedUrl($url)) {
            return back()->with('expanded', [
                'error' => __('That address cannot be checked.'),
            ])->withInput();
        }

        try {
            $response = Http::withOptions([
                'allow_redirects' => ['track_redirects' => true, 'max' => 10],
            ])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; ' . site_name() . ' LinkExpander/1.0)',
            ])->timeout(6)->get($url);

            $psr = $response->toPsrResponse();
            $history = $psr->getHeader('X-Guzzle-Redirect-History');
            $chain = array_values(array_merge([$url], $history));
            $final = end($chain) ?: $url;

            $expanded = [
                'final' => $final,
                'chain' => $chain,
                'status' => $response->status(),
                'redirects' => max(0, count($chain) - 1),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $expanded = ['error' => __('Could not reach that URL. It may be offline or blocking requests.')];
        }

        return back()->with('expanded', $expanded)->withInput();
    }

    /** Social share preview form. */
    public function ogPreview()
    {
        return view('site.tools.og-preview');
    }

    /** Fetch a page and extract its share metadata. */
    public function ogPreviewFetch(Request $request)
    {
        $request->validate(['url' => 'required|url|max:2000']);

        $url = trim($request->input('url'));

        if ($this->isBlockedUrl($url)) {
            return back()->with('og', ['error' => __('That address cannot be previewed.')])->withInput();
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; ' . site_name() . ' OGPreview/1.0)',
            ])->timeout(6)->get($url);

            $html = (string) $response->body();

            $og = [
                'url' => $url,
                'title' => $this->metaTag($html, 'og:title') ?: $this->titleTag($html),
                'description' => $this->metaTag($html, 'og:description') ?: $this->metaName($html, 'description'),
                'image' => $this->absoluteUrl($this->metaTag($html, 'og:image'), $url),
                'site' => $this->metaTag($html, 'og:site_name') ?: parse_url($url, PHP_URL_HOST),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $og = ['error' => __('Could not fetch that page. It may be offline or blocking requests.')];
        }

        return back()->with('og', $og)->withInput();
    }

    /** Digital business card / vCard QR generator — client-side build. */
    public function vcard()
    {
        return view('site.tools.business-card');
    }

    /** Programmatic SEO: "Shorten {service} links" landing pages. */
    public function programmatic(string $service)
    {
        $services = $this->programmaticServices();

        abort_unless(isset($services[$service]), 404);

        return view('site.tools.programmatic', [
            'service' => $service,
            'meta' => $services[$service],
            'services' => $services,
        ]);
    }

    /** Whitelisted services for the programmatic landing pages. */
    protected function programmaticServices(): array
    {
        return [
            'youtube' => ['name' => 'YouTube', 'icon' => 'megaphone', 'desc' => __('Turn long, tracker-laden YouTube video and channel URLs into clean, shareable short links.'), 'example' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'amazon' => ['name' => 'Amazon', 'icon' => 'gift', 'desc' => __('Shrink messy Amazon product and affiliate URLs into tidy links that are easy to share and track.'), 'example' => 'https://www.amazon.com/dp/B08N5WRWNW?ref=example'],
            'instagram' => ['name' => 'Instagram', 'icon' => 'palette', 'desc' => __('Create short, branded links for your Instagram bio, stories and posts.'), 'example' => 'https://www.instagram.com/p/CxAmPLe12345/'],
            'tiktok' => ['name' => 'TikTok', 'icon' => 'device', 'desc' => __('Shorten TikTok video and profile links so they are clean enough to share anywhere.'), 'example' => 'https://www.tiktok.com/@creator/video/7123456789012345678'],
            'facebook' => ['name' => 'Facebook', 'icon' => 'users', 'desc' => __('Tidy up long Facebook post, page and event URLs into short, trackable links.'), 'example' => 'https://www.facebook.com/events/1234567890123456/'],
            'twitter' => ['name' => 'Twitter / X', 'icon' => 'share', 'desc' => __('Shorten Twitter and X links to save characters and measure every click.'), 'example' => 'https://x.com/username/status/1234567890123456789'],
            'linkedin' => ['name' => 'LinkedIn', 'icon' => 'user', 'desc' => __('Turn lengthy LinkedIn profile, post and company URLs into professional short links.'), 'example' => 'https://www.linkedin.com/posts/username_activity-1234567890123456789'],
            'whatsapp' => ['name' => 'WhatsApp', 'icon' => 'phone', 'desc' => __('Create short click-to-chat WhatsApp links that are easy to share and track.'), 'example' => 'https://wa.me/15551234567?text=Hello'],
            'spotify' => ['name' => 'Spotify', 'icon' => 'bolt', 'desc' => __('Shorten Spotify track, album, playlist and podcast links for cleaner sharing.'), 'example' => 'https://open.spotify.com/track/4cOdK2wGLETKBW3PvgPWqT'],
            'github' => ['name' => 'GitHub', 'icon' => 'code', 'desc' => __('Shrink long GitHub repo, file and pull-request URLs into short, memorable links.'), 'example' => 'https://github.com/laravel/laravel/blob/master/README.md'],
        ];
    }

    /** Minimal SSRF guard: reject non-http(s) and obvious local/private hosts. */
    protected function isBlockedUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || $host === 'localhost' || $host === '::1' || $host === '[::1]') {
            return true;
        }

        foreach (['127.', '10.', '192.168.', '169.254.', '0.'] as $prefix) {
            if (str_starts_with($host, $prefix)) {
                return true;
            }
        }

        // 172.16.0.0 – 172.31.255.255 private range.
        if (preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $host)) {
            return true;
        }

        return false;
    }

    /** <meta property="og:*"|name="twitter:*"> content extractor. */
    protected function metaTag(string $html, string $property): ?string
    {
        $prop = preg_quote($property, '/');
        // property="og:x" ... content="..."
        if (preg_match('/<meta[^>]+(?:property|name)\s*=\s*["\']' . $prop . '["\'][^>]*content\s*=\s*["\']([^"\']*)["\']/i', $html, $m)) {
            return $this->clean($m[1]);
        }
        // content="..." ... property="og:x"
        if (preg_match('/<meta[^>]+content\s*=\s*["\']([^"\']*)["\'][^>]*(?:property|name)\s*=\s*["\']' . $prop . '["\']/i', $html, $m)) {
            return $this->clean($m[1]);
        }

        return null;
    }

    protected function metaName(string $html, string $name): ?string
    {
        return $this->metaTag($html, $name);
    }

    protected function titleTag(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            return $this->clean($m[1]);
        }

        return null;
    }

    protected function clean(string $value): string
    {
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** Resolve a possibly-relative URL against the fetched page's URL. */
    protected function absoluteUrl(?string $candidate, string $base): ?string
    {
        if (! $candidate) {
            return null;
        }
        if (preg_match('~^https?://~i', $candidate)) {
            return $candidate;
        }

        $parts = parse_url($base);
        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }
        $root = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        if (str_starts_with($candidate, '//')) {
            return $parts['scheme'] . ':' . $candidate;
        }
        if (str_starts_with($candidate, '/')) {
            return $root . $candidate;
        }

        return $root . '/' . ltrim($candidate, '/');
    }
}
