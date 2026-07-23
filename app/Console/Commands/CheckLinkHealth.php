<?php

namespace App\Console\Commands;

use App\Models\Link;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pings link destinations and flags dead/broken ones. Runs on a schedule and
 * can be triggered per-link from the UI. Uses a lightweight GET with a short
 * timeout; 2xx/3xx = ok, anything else (or a network error) = broken.
 */
class CheckLinkHealth extends Command
{
    protected $signature = 'app:check-link-health {--limit=200} {--link=}';

    protected $description = 'Check that link destinations are still reachable';

    public function handle(): int
    {
        $query = Link::query()->whereNull('archived_at')->where('disabled', false)
            ->where('type', 'link');

        if ($id = $this->option('link')) {
            $query->where('id', $id);
        } else {
            // Re-check the least-recently-checked links first.
            $query->orderByRaw('health_checked_at is not null, health_checked_at asc')
                ->limit((int) $this->option('limit'));
        }

        $checked = 0;
        $broken = 0;
        foreach ($query->get() as $link) {
            [$status, $code] = $this->ping($link->destination);
            $link->forceFill([
                'health_status' => $status,
                'health_code' => $code,
                'health_checked_at' => now(),
            ])->saveQuietly();
            $checked++;
            if ($status === 'broken') {
                $broken++;
            }
        }

        $this->info("Checked {$checked} links, {$broken} broken.");

        return self::SUCCESS;
    }

    protected function ping(string $url): array
    {
        try {
            $res = Http::timeout(8)->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ' . site_name() . ' LinkChecker/1.0)'])
                ->connectTimeout(5)->get($url);
            $code = $res->status();

            return [$code < 400 ? 'ok' : 'broken', $code];
        } catch (\Throwable) {
            return ['broken', null];
        }
    }
}
