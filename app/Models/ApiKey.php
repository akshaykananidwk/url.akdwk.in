<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = ['user_id', 'name', 'key_hash', 'key_prefix', 'rate_limit', 'last_used_at'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Generate a new key. Returns [model, plaintext] — plaintext shown once. */
    public static function generate(int $userId, string $name, ?int $rateLimit = null): array
    {
        $plain = 'sk_' . Str::random(40);
        $model = static::create([
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => hash('sha256', $plain),
            'key_prefix' => substr($plain, 0, 10),
            'rate_limit' => $rateLimit,
        ]);

        return [$model, $plain];
    }

    public static function findByPlainKey(string $plain): ?ApiKey
    {
        return static::where('key_hash', hash('sha256', $plain))->first();
    }
}
