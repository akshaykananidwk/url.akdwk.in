# Extending the platform

The codebase is deliberately modular: Services hold business logic,
Controllers stay thin, settings live in the database, and every important
moment in the request lifecycle fires a hook. You can add features three ways,
from lightest to heaviest: **hooks → themes → addons**.

## 1. Hooks (actions & filters)

`App\Support\Hook` is a WordPress-style registry.

```php
use App\Support\Hook;

// Do something when an event happens:
Hook::addAction('click_recorded', function ($click, $link) { ... });

// Change a value as it flows through the system:
Hook::addFilter('redirect_destination', function ($url, $link, $request) {
    return $url . '#from-short-link';
});
```

### Actions fired by the core

| Hook | Arguments | When |
|---|---|---|
| `before_redirect` | Link, Request | before a short link resolves |
| `click_recorded` | Click, Link | after the queued click job stores a click |
| `link_created` / `link_updated` / `link_deleted` | Link (or payload array) | link lifecycle |
| `user_registered` / `user_verified` / `user_logged_in` | User | auth lifecycle |
| `user_deleting` | User | before GDPR account erasure |
| `payment_completed` | Payment | after a payment is fulfilled |

### Filters offered by the core

| Hook | Value | Purpose |
|---|---|---|
| `redirect_destination` | string URL | rewrite the final destination |
| `link_destination` | string URL | rewrite/deny at creation time |
| `user_menu` / `admin_menu` | array of nav items | add sidebar entries |
| `payment_gateways` | array slug ⇒ class | register custom gateways |
| `addon_settings_pages` | array | list your settings screens on Admin → Addons |

## 2. Themes

Create `resources/themes/<name>/` and mirror any file from
`resources/views/` — your copy wins, everything else falls back to stock.
Optionally add a `theme.json`:

```json
{ "name": "Midnight", "author": "You" }
```

Select the theme in **Admin → Settings → General**. Because themes are plain
Blade + the same Tailwind classes, run `npm run build` if your theme
introduces new utility classes.

## 3. Addons

An addon is a folder in `/addons` with an `addon.json` manifest:

```
addons/
  my-feature/
    addon.json            required manifest
    src/                  PSR-4 classes, namespace Addons\MyFeature\
      MyFeatureServiceProvider.php
    routes/web.php        auto-loaded with the web middleware group
    routes/api.php        auto-loaded with the api middleware group
    migrations/           run automatically when the addon is enabled
    views/                view('my-feature::someview')
    lang/                 trans('my-feature::file.key')
```

`addon.json`:

```json
{
    "slug": "my-feature",
    "name": "My Feature",
    "version": "1.0.0",
    "description": "What it does",
    "provider": "Addons\\MyFeature\\MyFeatureServiceProvider"
}
```

Enable/disable from **Admin → Addons**. The bundled `addons/hello-world`
addon is a working template — copy it and rename.

### Adding a payment gateway from an addon

```php
Hook::addFilter('payment_gateways', function (array $gateways) {
    $gateways['mygateway'] = \Addons\MyFeature\MyGateway::class; // extends App\Services\Gateways\Gateway
    return $gateways;
});
```

Your gateway then appears in Admin → Settings → Payments and at checkout
automatically.

## 4. Conventions to copy

Look at any existing module (Spaces is the smallest) and mirror it:

1. Migration → `database/migrations`
2. Model with casts + relations → `app/Models`
3. Business rules in a Service → `app/Services`
4. Thin controller → `app/Http/Controllers/User` (+ Admin moderation screen)
5. Routes in `routes/web.php`, views under `resources/views/user/<module>/`
6. Feature/limit gating through `App\Services\PlanLimits`
7. Strings wrapped in `__()' so the translation manager picks them up

Settings never go in config files — store them with `setting_set()` /
read with `setting()` so they're editable from the admin UI.
