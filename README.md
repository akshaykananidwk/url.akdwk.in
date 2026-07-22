# Shortl — Self-Hosted URL Shortener SaaS Platform

A complete, production-ready, self-hosted URL shortener platform (in the spirit of Bitly / Rebrandly / phpShort) built with **Laravel 11**, **Tailwind CSS**, **Alpine.js** and **Chart.js**. Mobile-first, dark-mode ready, installable as a PWA, translated into English, Gujarati and Hindi, and monetizable out of the box with plans, 12 payment gateways, coupons, taxes, invoices and an affiliate program.

![screenshot placeholder](docs/screenshots/dashboard.png)

## Feature highlights

| Area | What you get |
|------|--------------|
| Links | Custom aliases, custom domains with DNS verification, spaces, tags, bulk shortening, CSV import/export, password protection, expiration (date / click count), cloaking, deep links, custom OG previews, UTM builder, 14 retargeting pixel providers |
| Targeting | Per-country, per-OS, per-language, per-device and time-of-day destinations, plus weighted A/B rotation with sticky visitors |
| Analytics | Referrers, countries, cities, regions, languages, platforms, browsers, devices, ISPs, hourly heatmap, live click feed, comparisons, CSV/PDF export, public stats pages — powered by daily rollups that scale to millions of clicks |
| QR codes | Dynamic (destination editable after printing), colors, logo, frame text, EC level, PNG/SVG/PDF, separate scan tracking |
| Bio pages | Link-in-bio builder with themes, fonts, colors, 11 block types, block scheduling, block analytics, email capture, custom domains |
| SaaS | Unlimited configurable plans (every feature/limit togglable), monthly/yearly/lifetime cycles, trials, coupons, per-country taxes (GST/VAT), PDF invoices, dunning, affiliate program |
| Payments | Stripe, PayPal, Razorpay, Paystack, Mollie, Paddle, Xendit, MercadoPago, Coinbase Commerce, NOWPayments + manual **UPI** and **bank transfer** with admin approval |
| Admin | Users (impersonate, suspend, plan override), moderation, manual payment approval, plans/coupons/taxes, full settings UI, translation manager, CMS (pages/blog/FAQ), reports, audit log, DB backup, staff permissions |
| Extensibility | Drop-in addons (`/addons`), swappable themes, WordPress-style action/filter hooks, DB-stored settings |
| Platform | Web installer, update wizard, REST API + Postman collection, webhooks, 2FA, social login (Google/Facebook/X/GitHub/Apple), reCAPTCHA/hCaptcha, PWA, RTL support |

## Requirements

- PHP **8.2+** with: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO (mysql or sqlite), Tokenizer, XML, GD (or Imagick), Zip
- MySQL **8.x** (or MariaDB 10.6+ / SQLite for small installs)
- Composer 2, Node 18+ (only if rebuilding assets — production assets ship pre-built)
- Apache (with `mod_rewrite`) or Nginx
- Optional: Redis for cache/queue at high traffic

## Quick install

1. **Upload / clone** the project to your server, then install PHP dependencies:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. Point your web server's document root at the **`public/`** directory
   (an `.htaccess` is included for Apache; see `docs/INSTALL.md` for the Nginx server block).

3. Make sure these paths are writable by the web server:

   ```
   storage/  bootstrap/cache/  .env (or the project root)
   ```

4. Open **`https://your-domain.com/install`** in a browser and follow the wizard:
   requirements check → (optional license) → database → admin account → done.
   The wizard writes `.env`, runs migrations + seeders, generates the `APP_KEY`,
   and locks itself permanently once finished.

5. Add the **cron entry** (powers stats retention, renewals, dunning):

   ```bash
   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
   ```

6. Click tracking works out of the box with **no worker needed** (clicks record right after the redirect response). A queue worker is **optional** — only add one if you switch `QUEUE_CONNECTION` to `database`/`redis` for very high traffic:

   ```bash
   php artisan queue:work --tries=3 --timeout=90
   ```

   Keep it running with Supervisor/systemd — sample configs in `docs/INSTALL.md`.

That's it. Log in, open **Admin → Settings** and configure email, payments and branding — everything is editable from the UI.

Full step-by-step documentation (with screenshots placeholders, Nginx config, Supervisor config, S3, Redis, updating): **[docs/INSTALL.md](docs/INSTALL.md)**.

## Updating

Upload the new release over the old files (keep `.env`, `storage/`, `addons/`), then visit **`/update`** — the wizard shows and runs pending migrations and clears caches.

## REST API

Create a key under **Developers → API Keys**, then:

```bash
curl -H "Authorization: Bearer sk_..." https://your-domain.com/api/v1/links
```

Interactive docs live at **/developers/docs** inside the app; a Postman collection ships at [`docs/postman_collection.json`](docs/postman_collection.json).

## Extending

- **Addons:** copy `addons/hello-world`, rename the slug in `addon.json`, and build. Routes, migrations, views and hooks auto-register; enable from **Admin → Addons**. See `docs/EXTENDING.md`.
- **Themes:** create `resources/themes/<name>/…` mirroring `resources/views`; any file you add overrides the stock view. Select the theme in **Admin → Settings → General**.
- **Hooks:** `Hook::addAction('click_recorded', fn ($click, $link) => …)` / `Hook::addFilter('redirect_destination', fn ($url, $link, $request) => $url)`.

## Project structure

```
app/
  Console/Commands/    scheduled jobs (retention, subscriptions)
  Http/Controllers/    Admin/ Api/ Auth/ Install/ User/ + redirect hot path
  Jobs/                queued click recording, webhook delivery
  Models/              32 Eloquent models
  Policies/            authorization (links + team roles)
  Services/            LinkService, TargetingEngine, StatsService, PlanLimits,
                       QrService, AddonManager, ThemeManager, Gateways/ (12)
  Support/             Hook system, helpers
addons/                drop-in addons (hello-world example included)
database/migrations/   full schema  ·  database/seeders/  base + demo data
docs/                  INSTALL.md, EXTENDING.md, postman_collection.json
lang/                  en/gu/hi JSON translations
resources/views/       Blade UI (user, admin, landing, install, bio, mail)
```

## Security

CSRF protection, hashed passwords (bcrypt), encrypted secrets at rest, signed verification URLs, login/API/shorten rate limiting, open-redirect prevention, blocked domain/word lists, Google Safe Browsing integration, honeypot + captcha, 2FA (TOTP + recovery codes), audit trail. Redirects are cached and clicks are recorded right after the response is flushed, so the redirect itself stays fast.

## License

Proprietary — deploy for your own use. See CHANGELOG.md for release history.
