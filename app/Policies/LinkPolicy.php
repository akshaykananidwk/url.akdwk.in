<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\TeamMember;
use App\Models\User;

/**
 * Link access: the owner has full control; accepted team members get access
 * to the owner's workspace based on their role (viewer → read, editor/admin →
 * write, admin → delete).
 */
class LinkPolicy
{
    public function view(User $user, Link $link): bool
    {
        return $link->user_id === $user->id || $this->teamRole($user, $link->user_id) !== null;
    }

    public function update(User $user, Link $link): bool
    {
        return $link->user_id === $user->id
            || in_array($this->teamRole($user, $link->user_id), ['admin', 'editor'], true);
    }

    public function delete(User $user, Link $link): bool
    {
        return $link->user_id === $user->id || $this->teamRole($user, $link->user_id) === 'admin';
    }

    protected function teamRole(User $user, int $ownerId): ?string
    {
        static $cache = [];
        $key = $user->id . ':' . $ownerId;

        return $cache[$key] ??= TeamMember::where('owner_id', $ownerId)
            ->where('user_id', $user->id)
            ->whereNotNull('accepted_at')
            ->value('role');
    }
}
