<?php

namespace App\Jobs;

use App\Models\Click;
use App\Models\ClickRollup;
use App\Models\Link;
use App\Services\Support\GeoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Records a click asynchronously so the redirect itself stays <50ms.
 * The redirect controller captures the raw request facts and queues this job;
 * geo lookup, uniqueness detection, rollups, and webhooks all happen here.
 */
class RecordClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $linkId,
        public int $userId,
        public ?string $ip,
        public ?string $userAgent,
        public ?string $refererUrl,
        public ?string $acceptLanguage,
        public bool $isQr,
        public string $occurredAt,
        public array $headerGeo = [],
    ) {
    }

    public function handle(): void
    {
        $link = Link::find($this->linkId);
        if (! $link) {
            return;
        }

        $anonymize = (bool) setting('anonymize_ips', false);
        $noPersonal = (bool) setting('no_personal_data', false);

        // Salted hash — raw IPs are never stored. In anonymize mode the last
        // octet is zeroed before hashing (GDPR-style anonymization).
        $ip = $this->ip;
        if ($ip && $anonymize) {
            $ip = preg_replace('/(\.\d+)$/', '.0', $ip);
            $ip = preg_replace('/(:[0-9a-f]*){4}$/i', '::', $ip);
        }
        $ipHash = $ip && ! $noPersonal ? hash('sha256', $ip . config('app.key')) : null;

        // Unique = first click from this visitor on this link in 24h.
        $isUnique = false;
        if ($ipHash) {
            $isUnique = Cache::add('uniq:' . $this->linkId . ':' . $ipHash, 1, 86400);
        }

        // Geo: prefer CDN headers captured at request time, else full lookup.
        $geo = array_filter($this->headerGeo);
        if (empty($geo['country']) && ! $noPersonal) {
            $geo = GeoService::lookup($this->ip);
        }

        $ua = \App\Services\Support\UserAgentParser::parse($this->userAgent);

        $refererHost = null;
        if ($this->refererUrl) {
            $refererHost = parse_url($this->refererUrl, PHP_URL_HOST) ?: null;
        }

        // Click row, denormalized counters and the daily rollup are written in
        // ONE transaction so a retried job can never double-count.
        $click = DB::transaction(function () use ($link, $ipHash, $geo, $ua, $refererHost, $isUnique) {
            $click = Click::create([
                'link_id' => $this->linkId,
                'user_id' => $this->userId,
                'ip_hash' => $ipHash,
                'country' => $geo['country'] ?? null,
                'region' => isset($geo['region']) ? mb_substr($geo['region'], 0, 100) : null,
                'city' => isset($geo['city']) ? mb_substr($geo['city'], 0, 100) : null,
                'language' => $this->acceptLanguage ? substr($this->acceptLanguage, 0, 2) : null,
                'os' => $ua['os'],
                'browser' => $ua['browser'],
                'device' => $ua['device'],
                'referer_host' => $refererHost ? mb_substr($refererHost, 0, 190) : null,
                'referer_url' => $this->refererUrl ? mb_substr($this->refererUrl, 0, 1000) : null,
                'isp' => isset($geo['isp']) ? mb_substr($geo['isp'], 0, 190) : null,
                'is_unique' => $isUnique,
                'is_qr' => $this->isQr,
                'created_at' => $this->occurredAt,
            ]);

            $update = [
                'clicks_count' => DB::raw('clicks_count + 1'),
                'last_click_at' => $this->occurredAt,
            ];
            if ($isUnique) {
                $update['unique_clicks_count'] = DB::raw('unique_clicks_count + 1');
            }
            if ($this->isQr) {
                $update['qr_scans_count'] = DB::raw('qr_scans_count + 1');
            }
            Link::withoutEvents(fn () => Link::where('id', $this->linkId)->update($update));

            $this->applyToRollup($link, $click);

            return $click;
        }, 3);

        hook_action('click_recorded', $click, $link);

        // Click alerts (Slack/Discord/Telegram): instant, and on click milestones.
        $this->maybeAlert($link);

        DispatchWebhooks::dispatch($this->userId, 'click.created', [
            'link_id' => $link->id,
            'alias' => $link->alias,
            'short_url' => $link->shortUrl(),
            'country' => $click->country,
            'os' => $click->os,
            'browser' => $click->browser,
            'device' => $click->device,
            'referer' => $click->referer_host,
            'is_unique' => $click->is_unique,
            'is_qr' => $click->is_qr,
            'clicked_at' => $click->created_at->toIso8601String(),
        ]);
    }

    /** Fire Slack/Discord/Telegram alerts for instant + milestone rules. */
    protected function maybeAlert(Link $link): void
    {
        if (! \App\Models\AlertChannel::where('user_id', $this->userId)->where('active', true)->exists()) {
            return;
        }

        $total = (int) $link->clicks_count + 1; // +1: the row update may not be reflected on $link yet
        $channels = \App\Models\AlertChannel::where('user_id', $this->userId)->where('active', true)->get();

        $instant = $channels->firstWhere('instant', true) !== null;
        $milestoneHit = $channels->contains(fn ($c) => $c->milestone > 0 && $total % $c->milestone === 0);

        if ($instant) {
            \App\Jobs\SendClickAlert::dispatch($this->userId, $link->id, 'instant', $total);
        } elseif ($milestoneHit) {
            \App\Jobs\SendClickAlert::dispatch($this->userId, $link->id, 'milestone', $total);
        }
    }

    /** Incrementally maintain the daily rollup row (runs inside the click transaction). */
    protected function applyToRollup(Link $link, Click $click): void
    {
        $date = $click->created_at->toDateString();

        $rollup = ClickRollup::where('link_id', $link->id)->where('date', $date)->lockForUpdate()->first();
        if (! $rollup) {
            try {
                $rollup = ClickRollup::create([
                    'link_id' => $link->id, 'date' => $date, 'user_id' => $link->user_id,
                    'clicks' => 0, 'uniques' => 0, 'qr_scans' => 0, 'breakdown' => [],
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                // concurrent worker created it first
                $rollup = ClickRollup::where('link_id', $link->id)->where('date', $date)->lockForUpdate()->firstOrFail();
            }
        }

        $b = $rollup->breakdown ?? [];
        foreach ([
            'country' => $click->country, 'os' => $click->os, 'browser' => $click->browser,
            'device' => $click->device, 'referer' => $click->referer_host ?: 'direct',
            'language' => $click->language, 'city' => $click->city, 'region' => $click->region,
            'isp' => $click->isp, 'hour' => (string) $click->created_at->format('G'),
        ] as $dim => $key) {
            if ($key === null || $key === '') {
                continue;
            }
            $b[$dim][$key] = ($b[$dim][$key] ?? 0) + 1;
        }

        $rollup->breakdown = $b;
        $rollup->clicks += 1;
        $rollup->uniques += $click->is_unique ? 1 : 0;
        $rollup->qr_scans += $click->is_qr ? 1 : 0;
        $rollup->save();
    }
}
