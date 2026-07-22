<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;

/**
 * Central quota + feature gate. Every "can the user do X?" question goes
 * through here so plan enforcement stays consistent across web, API, and bulk.
 */
class PlanLimits
{
    public function __construct(protected StatsService $stats)
    {
    }

    public function plan(User $user): Plan
    {
        return $user->currentPlan();
    }

    public function hasFeature(User $user, string $feature): bool
    {
        return $this->plan($user)->hasFeature($feature);
    }

    /** Remaining quota for a countable resource. -1 = unlimited. */
    public function remaining(User $user, string $key): int
    {
        $limit = $this->plan($user)->limit($key);
        if ($limit < 0) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->used($user, $key));
    }

    public function canCreate(User $user, string $key, int $count = 1): bool
    {
        return $this->remaining($user, $key) >= $count;
    }

    public function used(User $user, string $key): int
    {
        return match ($key) {
            'links' => $user->links()->count(),
            'spaces' => $user->spaces()->count(),
            'domains' => $user->domains()->count(),
            'pixels' => $user->pixels()->count(),
            'team_members' => $user->teamMembers()->count(),
            'qr_codes' => $user->qrCodes()->count(),
            'bio_pages' => $user->bioPages()->count(),
            'clicks_per_month' => $this->stats->clicksThisMonth($user->id),
            default => 0,
        };
    }

    /** Usage summary rows for dashboard progress bars. */
    public function usageSummary(User $user): array
    {
        $plan = $this->plan($user);
        $rows = [];
        foreach (['links', 'clicks_per_month', 'spaces', 'domains', 'pixels', 'qr_codes', 'bio_pages', 'team_members'] as $key) {
            $limit = $plan->limit($key);
            $used = $this->used($user, $key);
            $rows[$key] = [
                'label' => Plan::LIMIT_KEYS[$key],
                'used' => $used,
                'limit' => $limit,
                'unlimited' => $limit < 0,
                'percent' => $limit > 0 ? min(100, (int) round($used / $limit * 100)) : ($limit < 0 ? 0 : 100),
            ];
        }

        return $rows;
    }

    /** API requests/minute for a user (plan limit, overridable per key). */
    public function apiRate(User $user, ?int $keyOverride = null): int
    {
        if ($keyOverride) {
            return $keyOverride;
        }
        $rate = $this->plan($user)->limit('api_rate');

        return $rate < 0 ? 6000 : max(1, $rate ?: 30);
    }
}
