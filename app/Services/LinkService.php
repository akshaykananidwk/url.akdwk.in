<?php

namespace App\Services;

use App\Jobs\DispatchWebhooks;
use App\Models\BlockedDomain;
use App\Models\BlockedWord;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * All link creation/update goes through this service: alias generation,
 * abuse checks (blocked domains/words, Safe Browsing), plan gating and
 * webhook/hook dispatch live here so web UI, API, and bulk import behave
 * identically.
 */
class LinkService
{
    public function __construct(
        protected PlanLimits $limits,
        protected SafeBrowsingService $safeBrowsing,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function create(User $user, array $data): Link
    {
        $this->guardQuota($user);
        $data = $this->prepare($user, $data);

        $link = Link::create($data + ['user_id' => $user->id]);
        if (! empty($data['pixel_ids'])) {
            $link->pixels()->sync($this->ownPixelIds($user, $data['pixel_ids']));
        }

        hook_action('link_created', $link);
        DispatchWebhooks::dispatch($user->id, 'link.created', ['id' => $link->id, 'alias' => $link->alias, 'short_url' => $link->shortUrl(), 'destination' => $link->destination]);

        return $link;
    }

    public function update(Link $link, array $data): Link
    {
        $data = $this->prepare($link->user, $data, $link);
        $link->update($data);
        if (array_key_exists('pixel_ids', $data)) {
            $link->pixels()->sync($this->ownPixelIds($link->user, $data['pixel_ids'] ?? []));
        }

        hook_action('link_updated', $link);
        DispatchWebhooks::dispatch($link->user_id, 'link.updated', ['id' => $link->id, 'alias' => $link->alias, 'destination' => $link->destination]);

        return $link;
    }

    public function guardQuota(User $user, int $count = 1): void
    {
        if (! $this->limits->canCreate($user, 'links', $count)) {
            throw ValidationException::withMessages([
                'destination' => __('You have reached the link limit of your plan. Upgrade to create more links.'),
            ]);
        }
    }

    /** Normalize + validate incoming attributes. */
    /** Special link types whose "destination" is a placeholder served by type (not a redirect). */
    protected const SPECIAL_TYPES = ['vcard', 'whatsapp', 'file', 'music'];

    protected function prepare(User $user, array $data, ?Link $existing = null): array
    {
        if (isset($data['destination'])) {
            $type = $data['type'] ?? $existing?->type ?? 'link';
            if (in_array($type, self::SPECIAL_TYPES, true)) {
                // vCard/file/music/whatsapp links serve special content by type,
                // so their destination is a placeholder — skip strict URL rules.
                $data['destination'] = trim($data['destination']);
            } else {
                $data['destination'] = $this->validateDestination($data['destination']);
            }
        }

        // Domain: user may only use their own verified domains or global ones.
        if (! empty($data['domain_id'])) {
            $domain = Domain::find($data['domain_id']);
            $allowed = $domain && ($domain->isGlobal() || ($domain->user_id === $user->id && $domain->isVerified()));
            if (! $allowed) {
                throw ValidationException::withMessages(['domain_id' => __('Invalid domain selected.')]);
            }
        } else {
            $data['domain_id'] = null;
        }

        // Alias: custom (plan-gated) or generated.
        $alias = trim($data['alias'] ?? '');
        if ($alias !== '' && $alias !== $existing?->alias) {
            if (! $this->limits->hasFeature($user, 'custom_alias')) {
                throw ValidationException::withMessages(['alias' => __('Custom aliases are not available on your plan.')]);
            }
            $this->validateAlias($alias, $data['domain_id'], $existing?->id);
            $data['alias'] = $alias;
        } elseif (! $existing) {
            $data['alias'] = $this->generateAlias($data['domain_id']);
        } else {
            unset($data['alias']);
        }

        // Password protection (plan-gated).
        if (! empty($data['password'])) {
            if (! $this->limits->hasFeature($user, 'password')) {
                throw ValidationException::withMessages(['password' => __('Password protection is not available on your plan.')]);
            }
            $data['password'] = Hash::make($data['password']);
        } elseif (array_key_exists('password', $data)) {
            // empty string clears, null string "keep" sentinel handled by controllers
            $data['password'] = null;
        }

        // Feature-gated blocks are silently stripped when the plan lacks them.
        foreach ([
            'expiration' => ['expires_at', 'starts_at', 'max_clicks', 'expired_redirect'],
            'targeting' => ['targeting'],
            'deep_links' => ['deep_link'],
            'cloaking' => ['cloaking'],
            'og' => ['og'],
            'utm' => ['utm'],
        ] as $feature => $keys) {
            if (! $this->limits->hasFeature($user, $feature)) {
                foreach ($keys as $key) {
                    unset($data[$key]);
                }
            }
        }

        // Rotator is a sub-feature of targeting.
        if (isset($data['targeting']['rotation']) && ! $this->limits->hasFeature($user, 'rotator')) {
            unset($data['targeting']['rotation']);
        }

        if (! empty($data['space_id']) && ! $user->spaces()->where('id', $data['space_id'])->exists()) {
            $data['space_id'] = null;
        }

        return $data;
    }

    /** Validate destination URL: scheme, blocked domains/words, self-loop, Safe Browsing. */
    public function validateDestination(string $url): string
    {
        $url = trim($url);
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) || strlen($url) > 5000) {
            throw ValidationException::withMessages(['destination' => __('Please enter a valid URL.')]);
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! $host || ! str_contains($host, '.')) {
            throw ValidationException::withMessages(['destination' => __('Please enter a valid URL.')]);
        }

        // Prevent shortening links to this platform itself (open-redirect loops).
        $selfHosts = Domain::pluck('domain')->push(parse_url(config('app.url'), PHP_URL_HOST))->filter()->map(fn ($h) => strtolower($h));
        if ($selfHosts->contains($host)) {
            throw ValidationException::withMessages(['destination' => __('You cannot shorten a link that points to this service.')]);
        }

        foreach (BlockedDomain::pluck('domain') as $blocked) {
            $blocked = strtolower($blocked);
            if ($host === $blocked || str_ends_with($host, '.' . $blocked)) {
                throw ValidationException::withMessages(['destination' => __('This domain is not allowed.')]);
            }
        }

        foreach (BlockedWord::pluck('word') as $word) {
            if ($word !== '' && stripos($url, $word) !== false) {
                throw ValidationException::withMessages(['destination' => __('This URL contains blocked content.')]);
            }
        }

        if (! $this->safeBrowsing->isSafe($url)) {
            throw ValidationException::withMessages(['destination' => __('This URL was flagged as unsafe and cannot be shortened.')]);
        }

        // Optional AI spam/phishing gate (Admin → Settings → AI).
        if (setting('ai_spam_check') && setting('ai_key')) {
            if (app(\App\Services\AiService::class)->spamScore($url) >= 80) {
                throw ValidationException::withMessages(['destination' => __('This URL was flagged as unsafe and cannot be shortened.')]);
            }
        }

        return hook_filter('link_destination', $url);
    }

    public function validateAlias(string $alias, ?int $domainId, ?int $ignoreId = null): void
    {
        if (! preg_match('/^[a-zA-Z0-9\-_]{1,100}$/', $alias)) {
            throw ValidationException::withMessages(['alias' => __('Aliases may only contain letters, numbers, dashes and underscores.')]);
        }

        $reserved = [
            'install', 'update', 'admin', 'login', 'register', 'logout', 'password', 'dashboard',
            'links', 'spaces', 'domains', 'pixels', 'stats', 'qr', 'bio', 'api', 'billing', 'account',
            'team', 'tools', 'pricing', 'blog', 'page', 'contact', 'terms', 'privacy', 'about',
            'assets', 'build', 'storage', 'vendor', 'img', 'css', 'js', 'favicon.ico', 'robots.txt',
            'sitemap.xml', 'manifest.json', 'sw.js', 'offline', 'up', 'report', 'invite', 'checkout', 'admin.html',
            'webhooks', 'verify-email', 'two-factor', 'file', 'files', 'lang', 'go', 'app',
        ];
        if (in_array(strtolower($alias), $reserved, true)) {
            throw ValidationException::withMessages(['alias' => __('This alias is reserved.')]);
        }

        foreach (BlockedWord::pluck('word') as $word) {
            if ($word !== '' && stripos($alias, $word) !== false) {
                throw ValidationException::withMessages(['alias' => __('This alias contains blocked content.')]);
            }
        }

        $exists = Link::where('alias', $alias)
            ->where('domain_id', $domainId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['alias' => __('This alias is already taken.')]);
        }
    }

    public function generateAlias(?int $domainId, int $length = 6): string
    {
        do {
            $alias = Str::random($length);
            $length = min(12, $length + 1);
        } while (Link::where('alias', $alias)->where('domain_id', $domainId)->exists());

        return $alias;
    }

    /** Only sync pixels the user owns. */
    protected function ownPixelIds(User $user, array $ids): array
    {
        return $user->pixels()->whereIn('id', $ids)->pluck('id')->all();
    }

    public function duplicate(Link $link): Link
    {
        $copy = $link->replicate(['clicks_count', 'unique_clicks_count', 'qr_scans_count', 'last_click_at']);
        $copy->alias = $this->generateAlias($link->domain_id);
        $copy->clicks_count = 0;
        $copy->unique_clicks_count = 0;
        $copy->qr_scans_count = 0;
        $copy->save();
        $copy->pixels()->sync($link->pixels->pluck('id'));

        return $copy;
    }

    public function delete(Link $link): void
    {
        $userId = $link->user_id;
        $payload = ['id' => $link->id, 'alias' => $link->alias];
        $link->pixels()->detach();
        $link->clicks()->delete();
        $link->rollups()->delete();
        $link->qrCodes()->update(['link_id' => null]);
        $link->delete();

        hook_action('link_deleted', $payload);
        DispatchWebhooks::dispatch($userId, 'link.deleted', $payload);
    }
}
