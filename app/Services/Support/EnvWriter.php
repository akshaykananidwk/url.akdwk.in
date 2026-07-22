<?php

namespace App\Services\Support;

/**
 * Safely rewrites keys in the project .env file, preserving everything else.
 * Values with spaces or special characters are quoted.
 */
class EnvWriter
{
    public static function write(array $values): void
    {
        $path = base_path('.env');
        $content = is_file($path) ? (string) file_get_contents($path) : (string) file_get_contents(base_path('.env.example'));

        foreach ($values as $key => $value) {
            $formatted = static::format($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            if (preg_match($pattern, $content)) {
                $content = (string) preg_replace($pattern, $key . '=' . $formatted, $content);
            } else {
                $content = rtrim($content, "\n") . "\n" . $key . '=' . $formatted . "\n";
            }
        }

        file_put_contents($path, $content);
    }

    protected static function format($value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        $value = (string) $value;
        if ($value === '' || preg_match('/[\s#"\'\\\\$]/', $value)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }

        return $value;
    }
}
