<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Safe Browsing v4 lookup. Fails open (returns safe) on network or
 * configuration errors so link creation never breaks when the API is down.
 * Enable by setting the API key in Admin → Settings → Security.
 */
class SafeBrowsingService
{
    public function isSafe(string $url): bool
    {
        $apiKey = setting('safe_browsing_key');
        if (! $apiKey) {
            return true;
        }

        return Cache::remember('sb:' . sha1($url), 3600, function () use ($url, $apiKey) {
            try {
                $res = Http::timeout(4)->post(
                    'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=' . $apiKey,
                    [
                        'client' => ['clientId' => 'shortl', 'clientVersion' => '1.0'],
                        'threatInfo' => [
                            'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'],
                            'platformTypes' => ['ANY_PLATFORM'],
                            'threatEntryTypes' => ['URL'],
                            'threatEntries' => [['url' => $url]],
                        ],
                    ]
                );

                return ! ($res->ok() && ! empty($res->json('matches')));
            } catch (\Throwable $e) {
                Log::warning('Safe Browsing check failed: ' . $e->getMessage());

                return true;
            }
        });
    }
}
