<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Carbon;

/**
 * Evaluates gamification badges and maintains the daily activity streak.
 * Badge definitions live in config/badges.php.
 */
class BadgeService
{
    /**
     * Award any newly-earned badges for the user. Idempotent — the
     * unique(user_id, badge_key) constraint plus firstOrCreate guard dupes.
     *
     * @return array<int, string> newly awarded badge keys
     */
    public function sync(User $user): array
    {
        $definitions = config('badges', []);
        if (empty($definitions)) {
            return [];
        }

        // Gather stats once (no N+1).
        $linksCount = (int) $user->links()->count();
        $totalClicks = (int) $user->links()->sum('clicks_count');
        $streak = (int) ($user->streak_days ?? 0);
        $ageDays = $user->created_at ? (int) $user->created_at->diffInDays(now()) : 0;
        $isPaid = ! $user->currentPlan()->is_free;

        // Keys already earned — one query, avoids per-badge lookups.
        $existing = $user->badges()->pluck('badge_key')->all();

        $awarded = [];

        foreach ($definitions as $key => $def) {
            if (in_array($key, $existing, true)) {
                continue;
            }

            $metric = $def['metric'] ?? null;
            $threshold = (int) ($def['threshold'] ?? 0);

            $earned = match ($metric) {
                'links' => $linksCount >= $threshold,
                'clicks' => $totalClicks >= $threshold,
                'streak' => $streak >= $threshold,
                'member' => $threshold > 0 ? $ageDays >= $threshold : $isPaid,
                default => false,
            };

            if (! $earned) {
                continue;
            }

            $badge = UserBadge::firstOrCreate(
                ['user_id' => $user->id, 'badge_key' => $key],
                ['awarded_at' => now()],
            );

            if ($badge->wasRecentlyCreated) {
                $awarded[] = $key;
            }
        }

        return $awarded;
    }

    /**
     * Update the user's daily activity streak. Called once per active day.
     */
    public function touchStreak(User $user): void
    {
        $today = Carbon::today();
        $last = $user->last_active_on ? Carbon::parse($user->last_active_on)->startOfDay() : null;

        if ($last && $last->isSameDay($today)) {
            return; // already counted today
        }

        if ($last && $last->isSameDay($today->copy()->subDay())) {
            $user->streak_days = (int) ($user->streak_days ?? 0) + 1;
        } else {
            $user->streak_days = 1;
        }

        $user->last_active_on = $today;
        $user->save();
    }
}
