<?php

namespace App\Services\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * DB-backed settings store with a single cached map.
 * Values are JSON-encoded so arrays/booleans round-trip cleanly.
 * Sensitive values (SMTP passwords, gateway secrets) are stored encrypted.
 */
class SettingsRepository
{
    protected ?array $all = null;

    public function all(): array
    {
        if ($this->all !== null) {
            return $this->all;
        }

        return $this->all = Cache::remember('settings:all', 3600, function () {
            $map = [];
            foreach (Setting::all() as $row) {
                $value = $row->value;
                if ($row->encrypted && $value !== null) {
                    try {
                        $value = Crypt::decryptString($value);
                    } catch (\Throwable) {
                        $value = null;
                    }
                }
                $map[$row->key] = $value === null ? null : json_decode($value, true);
            }

            return $map;
        });
    }

    public function get(string $key, $default = null)
    {
        $all = $this->all();

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    public function set(string $key, $value, bool $encrypted = false): void
    {
        $stored = json_encode($value);
        if ($encrypted) {
            $stored = Crypt::encryptString($stored);
        }

        Setting::updateOrCreate(['key' => $key], ['value' => $stored, 'encrypted' => $encrypted]);
        $this->flush();
    }

    public function setMany(array $values, array $encryptedKeys = []): void
    {
        foreach ($values as $key => $value) {
            $stored = json_encode($value);
            $encrypted = in_array($key, $encryptedKeys, true);
            if ($encrypted) {
                $stored = Crypt::encryptString($stored);
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $stored, 'encrypted' => $encrypted]);
        }
        $this->flush();
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
        $this->flush();
    }

    public function flush(): void
    {
        $this->all = null;
        Cache::forget('settings:all');
    }
}
