<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'workspace_id', 'action', 'subject', 'meta', 'ip', 'created_at'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity for the actor, and (if they belong to a workspace)
     * for that workspace's feed.
     */
    public static function log(string $action, ?string $subject = null, array $meta = [], ?int $workspaceId = null): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }
        static::create([
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'action' => $action,
            'subject' => $subject,
            'meta' => $meta ?: null,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
