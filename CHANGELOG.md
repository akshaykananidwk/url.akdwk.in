# Changelog

All notable changes to this project are documented here.

## [1.3.0] — 2026-07-23

### Added — Feature batch #3
- **Smart app links** — one link that sends iOS users to the App Store, Android to Google Play, everyone else to a fallback.
- **Music / podcast smart links** — a landing page with a button for every streaming service (Spotify, Apple Music, YouTube, and more).
- **Tip jar / donations** — a bio-page block letting supporters tip via UPI, PayPal.me or a custom payment link; tips are recorded and listed under a new Tips page.
- **vCard+ digital business card** — vCard links now open a polished contact card with a one-tap "Save contact"; raw .vcf still available at `?vcf=1`.
- **Bio templates gallery** — one-click starter templates (Creator, Business, Musician, Restaurant) that set a theme and seed starter blocks.

### Fixed
- vCard and file-to-link tools no longer fail URL validation on live domains (their placeholder destination is served by type).

## [1.2.0] — 2026-07-23

### Added — Feature batch #2
- **Scheduled links** — set an "Activate at" time; the link stays inactive (HTTP 425 page) until then.
- **Nested folders** — spaces can contain sub-spaces for a folder tree.
- **QR templates gallery** — one-click ready-made QR colour/style presets.
- **Conversion tracking** — drop a pixel/JS beacon on your success page; conversions (and optional revenue value) are attributed to the originating link, deduped per visitor, and shown in stats with conversion rate.
- **Real-time world map** on the statistics page.

## [1.1.0] — 2026-07-23

### Added — Feature batch #1
- **UTM templates** — save and reuse utm_* parameter sets.
- **Click alerts** — Slack / Discord / Telegram notifications (instant or every N clicks) with a test button.
- **Email reports** — weekly stats digest to opted-in users.
- **Link health checker** — scheduled + per-link check that flags broken destinations.
- **AI assistant** (bring-your-own key, Anthropic/OpenAI) — alias & tag suggestions plus optional spam/phishing scanning.

### Added — Platform
- **One-click self-updater** (Admin → Update): pulls the latest release from GitHub and applies it, preserving data/uploads/config.
- **In-browser GitHub file editor** (Admin → Update → Advanced) and a standalone `public/admin.html`.
- Trusted-proxy support so settings save correctly behind HTTPS proxies; friendly 419 page.
- Click recording works with no queue worker by default (records after the response is sent).

## [1.0.0] — 2026-07-22

Initial release.

### Platform
- Web-based 5-step installer (`/install`) with requirements check, optional purchase-code step, database setup with connection test, admin account creation, and permanent lock file; update wizard at `/update` that runs pending migrations.
- Self-hosted Laravel 11 application (PHP 8.2+, MySQL 8 / SQLite), Tailwind CSS + Alpine.js frontend built with Vite, Chart.js analytics, PWA (manifest + service worker + offline page), light/dark mode, mobile-first UI with bottom navigation.
- Multi-language out of the box: English, ગુજરાતી (Gujarati), हिन्दी (Hindi); admin translation manager, browser auto-detect, RTL support.

### Links
- Short links with random or custom aliases, custom domains (DNS verification), spaces, tags, notes, enable/disable, archive, duplicate, bulk shortening, CSV import/export.
- Password protection, expiration by date or click count with fallback redirect, link cloaking, deep links (iOS/Android), custom Open Graph previews, UTM builder, retargeting pixels (14 providers), disallow-lists and Google Safe Browsing scanning.
- Targeting engine: country, platform/OS, language, device, day/time windows, and weighted A/B rotation with sticky visitors.
- Bot/crawler filtering; fast cached redirects with queued click recording.

### Analytics
- Per-link and account-wide statistics: clicks, uniques, QR scans, referrers, countries, cities, regions, languages, platforms, browsers, devices, ISPs, hourly heatmap, live click feed, period comparison, CSV/PDF export, shareable public stats pages.
- Daily rollup tables for scale; raw click retention per plan with automatic pruning; GDPR modes (IP anonymization / no-personal-data).

### Products
- Dynamic QR codes with colors, logo, frame text, error-correction level; PNG/SVG/PDF download; scan tracking.
- Bio pages (link-in-bio) with themes, fonts, colors, blocks (links, text, image, video, email signup, WhatsApp, phone, vCard, socials), block scheduling and per-block analytics.
- Tools: file-to-link, vCard links, WhatsApp click-to-chat; share helpers for 10+ networks.
- Team workspaces with roles (admin/editor/viewer), invitations.
- REST API v1 with API keys and per-plan rate limits, interactive docs, Postman collection; webhooks with HMAC signatures.

### SaaS
- Plans with per-feature toggles and per-limit quotas (links, clicks/month, spaces, domains, pixels, team, QR, bio pages, API rate, retention), monthly/yearly/lifetime cycles, trials.
- Payment gateways: Stripe, PayPal, Razorpay, Paystack, Mollie, Paddle, Xendit, MercadoPago, Coinbase Commerce, NOWPayments, and manual UPI + bank transfer with admin approval.
- Coupons, per-country tax rates (VAT/GST with tax-ID capture), sequential PDF invoices, auto-renew handling with dunning emails and grace-period downgrade.
- Affiliate program with commissions and payout requests; optional interstitial ads for free plans.

### Admin
- Dashboard with revenue/signup/click charts; user management incl. impersonation, suspension, manual plan changes; moderation for links, spaces, domains, pixels, QR, bio pages; abuse reports; blocked domains/words.
- Full settings UI (general, SEO, email + test send, social login, captcha, registration, affiliate, ads, storage/S3, GDPR, payments, cron status); content manager (pages, blog, FAQs); translation manager; reports; audit log; database backup download; staff roles with granular permissions.

### Extensibility
- Drop-in addon system (`/addons` + `addon.json`) with auto-registered routes, migrations, views and hooks; enable/disable from admin.
- Theme system (`resources/themes/<name>`) overriding any view; action/filter hook registry throughout the request lifecycle.
