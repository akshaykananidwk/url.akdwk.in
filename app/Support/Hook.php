<?php

namespace App\Support;

/**
 * WordPress-style action/filter hook registry.
 *
 * Actions fire callbacks at key points (before_redirect, click_recorded,
 * user_registered, payment_completed, ...). Filters pass a value through a
 * chain of callbacks and return the result (redirect_destination,
 * landing_features, admin_menu, user_menu, ...).
 *
 * Addons register hooks from their service provider:
 *   Hook::addAction('before_redirect', fn ($link, $request) => ...);
 *   Hook::addFilter('redirect_destination', fn ($url, $link) => $url);
 */
class Hook
{
    protected static array $actions = [];
    protected static array $filters = [];

    public static function addAction(string $name, callable $callback, int $priority = 10): void
    {
        static::$actions[$name][$priority][] = $callback;
    }

    public static function addFilter(string $name, callable $callback, int $priority = 10): void
    {
        static::$filters[$name][$priority][] = $callback;
    }

    /** Fire all callbacks registered for an action. */
    public static function action(string $name, ...$args): void
    {
        $groups = static::$actions[$name] ?? [];
        ksort($groups);
        foreach ($groups as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    /** Pass $value through all registered filter callbacks. */
    public static function filter(string $name, $value, ...$args)
    {
        $groups = static::$filters[$name] ?? [];
        ksort($groups);
        foreach ($groups as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public static function hasAction(string $name): bool
    {
        return ! empty(static::$actions[$name]);
    }

    public static function flush(): void
    {
        static::$actions = [];
        static::$filters = [];
    }
}
