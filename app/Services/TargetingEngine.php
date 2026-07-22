<?php

namespace App\Services;

use App\Models\Link;
use App\Services\Support\UserAgentParser;
use Illuminate\Http\Request;

/**
 * Resolves the final destination for a link based on its targeting rules.
 *
 * Rule groups (all optional, evaluated in priority order):
 *   rotation  → weighted A/B split (sticky per visitor via cookie)
 *   country   → per-country destination
 *   platform  → per-OS destination (Android, iOS, Windows, macOS, Linux)
 *   device    → mobile / tablet / desktop
 *   language  → browser language
 *   time      → day-of-week + time window (link owner's timezone)
 *   deep_link → native app URI on mobile
 */
class TargetingEngine
{
    public function resolve(Link $link, Request $request): string
    {
        $targeting = $link->targeting ?? [];
        $ua = new UserAgentParser($request->userAgent());

        // 1. Traffic rotation (highest priority — it defines the base URL for A/B tests)
        if ($url = $this->matchRotation($link, $targeting['rotation'] ?? [], $request)) {
            return $url;
        }

        // 2. Country
        $country = \App\Services\Support\GeoService::fromHeaders($request)['country'];
        if ($country && $url = $this->matchKey($targeting['country'] ?? [], $country)) {
            return $url;
        }

        // 3. Platform / OS
        if ($url = $this->matchKey($targeting['platform'] ?? [], $ua->os())) {
            return $url;
        }

        // 4. Device class
        if ($url = $this->matchKey($targeting['device'] ?? [], $ua->device())) {
            return $url;
        }

        // 5. Browser language
        $lang = substr((string) $request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
        if ($lang && $url = $this->matchKey($targeting['language'] ?? [], $lang)) {
            return $url;
        }

        // 6. Time of day / day of week
        if ($url = $this->matchTime($targeting['time'] ?? [], $link)) {
            return $url;
        }

        return $link->destination;
    }

    /** Case-insensitive key match against [{key, url}] rule rows. */
    protected function matchKey(array $rules, string $value): ?string
    {
        foreach ($rules as $rule) {
            if (! empty($rule['key']) && ! empty($rule['url'])
                && strcasecmp(trim($rule['key']), $value) === 0) {
                return $rule['url'];
            }
        }

        return null;
    }

    /**
     * Weighted rotation, sticky per visitor (cookie) so an A/B test user
     * always lands on the same variant.
     */
    protected function matchRotation(Link $link, array $rules, Request $request): ?string
    {
        $rules = array_values(array_filter($rules, fn ($r) => ! empty($r['url']) && (int) ($r['weight'] ?? 0) > 0));
        if (! $rules) {
            return null;
        }

        $cookie = 'rot_' . $link->id;
        $sticky = $request->cookie($cookie);
        if ($sticky !== null && isset($rules[(int) $sticky])) {
            return $rules[(int) $sticky]['url'];
        }

        $total = array_sum(array_map(fn ($r) => (int) $r['weight'], $rules));
        $roll = random_int(1, max(1, $total));
        $acc = 0;
        foreach ($rules as $i => $rule) {
            $acc += (int) $rule['weight'];
            if ($roll <= $acc) {
                cookie()->queue($cookie, (string) $i, 60 * 24 * 30);

                return $rule['url'];
            }
        }

        return $rules[0]['url'];
    }

    /** Time rules: [{days: [0..6], from: "09:00", to: "18:00", url}] in the owner's timezone. */
    protected function matchTime(array $rules, Link $link): ?string
    {
        if (! $rules) {
            return null;
        }

        $tz = $link->user?->timezone ?: config('app.timezone', 'UTC');
        $now = now($tz);
        $dow = (int) $now->dayOfWeek; // 0 = Sunday
        $time = $now->format('H:i');

        foreach ($rules as $rule) {
            if (empty($rule['url'])) {
                continue;
            }
            $days = array_map('intval', $rule['days'] ?? []);
            if ($days && ! in_array($dow, $days, true)) {
                continue;
            }
            $from = $rule['from'] ?? '00:00';
            $to = $rule['to'] ?? '23:59';
            $inWindow = $from <= $to
                ? ($time >= $from && $time <= $to)
                : ($time >= $from || $time <= $to); // overnight window (e.g. 22:00 → 06:00)
            if ($inWindow) {
                return $rule['url'];
            }
        }

        return null;
    }

    /** Deep link handling: returns [uri, fallback] when applicable, null otherwise. */
    public function deepLink(Link $link, Request $request): ?array
    {
        $dl = $link->deep_link ?? [];
        if (empty($dl['enabled'])) {
            return null;
        }

        $ua = new UserAgentParser($request->userAgent());
        $os = $ua->os();
        if ($os === 'Android' && ! empty($dl['android'])) {
            return [$dl['android'], $link->destination];
        }
        if ($os === 'iOS' && ! empty($dl['ios'])) {
            return [$dl['ios'], $link->destination];
        }

        return null;
    }
}
