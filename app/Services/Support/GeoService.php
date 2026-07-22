<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IP geolocation with three strategies (in order):
 *  1. CDN-provided headers (Cloudflare CF-IPCountry / CF-IPCity — zero latency)
 *  2. Local MaxMind GeoLite2 database, if the admin uploaded one (storage/app/geoip/GeoLite2-City.mmdb)
 *  3. ip-api.com HTTP lookup (free tier), only used from the queued click job — never on the redirect path
 *
 * Results are cached to keep repeat lookups instant.
 */
class GeoService
{
    /** Fast, header-only lookup (safe on the redirect hot path). */
    public static function fromHeaders(\Illuminate\Http\Request $request): array
    {
        $country = $request->header('CF-IPCountry')
            ?: $request->header('X-Country-Code')
            ?: $request->header('X-Vercel-IP-Country');

        return [
            'country' => $country && strlen($country) === 2 && $country !== 'XX' ? strtoupper($country) : null,
            'city' => $request->header('CF-IPCity') ?: null,
            'region' => null,
            'isp' => null,
        ];
    }

    /** Full lookup for an IP (queue-side only — may perform an HTTP call). */
    public static function lookup(?string $ip): array
    {
        $empty = ['country' => null, 'city' => null, 'region' => null, 'isp' => null];
        if (! $ip || self::isPrivate($ip)) {
            return $empty;
        }

        return Cache::remember('geo:' . $ip, 86400, function () use ($ip, $empty) {
            // Strategy 2: local MaxMind DB (works fully offline)
            $mmdb = storage_path('app/geoip/GeoLite2-City.mmdb');
            if (is_file($mmdb) && function_exists('maxminddb_open')) {
                try {
                    $reader = maxminddb_open($mmdb);
                    $rec = maxminddb_get($reader, $ip);
                    maxminddb_close($reader);
                    if ($rec) {
                        return [
                            'country' => $rec['country']['iso_code'] ?? null,
                            'city' => $rec['city']['names']['en'] ?? null,
                            'region' => $rec['subdivisions'][0]['names']['en'] ?? null,
                            'isp' => null,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('GeoIP mmdb lookup failed: ' . $e->getMessage());
                }
            }

            // Strategy 3: HTTP lookup (only if enabled in settings)
            if (! setting('geo_http_lookup', true)) {
                return $empty;
            }

            try {
                $res = Http::timeout(3)->get('http://ip-api.com/json/' . $ip, [
                    'fields' => 'status,countryCode,regionName,city,isp',
                ]);
                if ($res->ok() && $res->json('status') === 'success') {
                    return [
                        'country' => $res->json('countryCode'),
                        'city' => $res->json('city'),
                        'region' => $res->json('regionName'),
                        'isp' => $res->json('isp'),
                    ];
                }
            } catch (\Throwable) {
                // network failure — record the click without geo data
            }

            return $empty;
        });
    }

    public static function isPrivate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
