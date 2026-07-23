<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Public system-status page. Runs a handful of lightweight, real health
 * probes (database, cache, redirect engine, queue) and renders them with
 * simple up/down pills. Every probe is wrapped in try/catch so the page can
 * never throw — a failing dependency is reported, not fatal.
 */
class StatusController extends Controller
{
    public function index()
    {
        $components = [
            $this->probeDatabase(),
            $this->probeCache(),
            $this->probeRedirectEngine(),
            $this->probeQueue(),
        ];

        $allUp = collect($components)->every(fn ($c) => $c['status'] === 'up');

        return view('site.status', [
            'components' => $components,
            'allUp' => $allUp,
            'checkedAt' => now(),
            'version' => config('install.version'),
        ]);
    }

    /**
     * Database: run a trivial "select 1" and measure round-trip latency.
     */
    protected function probeDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $ms = (int) round((microtime(true) - $start) * 1000);

            return $this->component('Database', 'up', 'Query responded', $ms);
        } catch (\Throwable $e) {
            return $this->component('Database', 'down', 'Unable to reach the database');
        }
    }

    /**
     * Cache: write and read back a probe key to confirm the cache store works.
     */
    protected function probeCache(): array
    {
        try {
            $start = microtime(true);
            $value = 'ok_' . now()->timestamp;
            Cache::put('status:probe', $value, 30);
            $roundtrip = Cache::get('status:probe');
            $ms = (int) round((microtime(true) - $start) * 1000);

            if ($roundtrip !== $value) {
                return $this->component('Cache', 'down', 'Cache did not return the stored value');
            }

            return $this->component('Cache', 'up', 'Read/write healthy', $ms);
        } catch (\Throwable $e) {
            return $this->component('Cache', 'down', 'Cache store is unavailable');
        }
    }

    /**
     * Redirect engine: if the application booted far enough to serve this
     * request, the HTTP layer that performs redirects is up.
     */
    protected function probeRedirectEngine(): array
    {
        try {
            return $this->component('Redirect engine', 'up', 'Serving short links');
        } catch (\Throwable $e) {
            return $this->component('Redirect engine', 'down', 'Redirect layer error');
        }
    }

    /**
     * Queue: report the configured connection. We only surface the driver name
     * (a misconfigured queue would be caught here), not liveness of workers.
     */
    protected function probeQueue(): array
    {
        try {
            $connection = (string) config('queue.default');

            if ($connection === '') {
                return $this->component('Queue', 'down', 'No queue connection configured');
            }

            return $this->component('Queue', 'up', 'Connection: ' . $connection);
        } catch (\Throwable $e) {
            return $this->component('Queue', 'down', 'Queue configuration error');
        }
    }

    protected function component(string $name, string $status, string $detail, ?int $latencyMs = null): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'detail' => $detail,
            'latency_ms' => $latencyMs,
        ];
    }
}
