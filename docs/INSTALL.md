# Installation Guide

This guide walks through a production deployment from zero to a working
platform. Every step that can be done in the browser is done in the browser —
you never edit config files by hand.

---

## 1. Server requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.2+ (8.3 recommended) |
| Extensions | BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO + pdo_mysql (or pdo_sqlite), Tokenizer, XML, GD or Imagick, Zip |
| Database | MySQL 8.x / MariaDB 10.6+ (SQLite supported for small installs) |
| Web server | Apache 2.4 with mod_rewrite, or Nginx |
| Optional | Redis 6+ (cache/queue), Supervisor (queue worker) |

The installer re-checks all of this for you with green ticks / red crosses.

## 2. Upload the application

```bash
cd /var/www
git clone <your-repo> shortl        # or upload and unzip the release
cd shortl
composer install --no-dev --optimize-autoloader
```

> Production assets are pre-built in `public/build/` — Node is only needed if
> you want to rebuild them (`npm install && npm run build`).

Set permissions (adjust `www-data` to your web server user):

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 3. Web server configuration

### Apache

Point the virtual host's `DocumentRoot` at the **`public/`** folder. The
included `public/.htaccess` handles the rest:

```apache
<VirtualHost *:80>
    ServerName links.example.com
    DocumentRoot /var/www/shortl/public
    <Directory /var/www/shortl/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name links.example.com;
    root /var/www/shortl/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Add TLS with certbot / your provider as usual. **Custom short domains** are
extra `server_name`s (or a wildcard/catch-all vhost) pointing at the same
root — the app routes by Host header automatically.

## 4. Run the web installer

Open `https://links.example.com/install`:

1. **Requirements** — ![screenshot placeholder](screenshots/install-requirements.png)
   every extension and writable folder is checked; fix anything red and re-check.
2. **License** — only shown when `INSTALL_REQUIRE_LICENSE=true`; otherwise skipped.
3. **Database** — enter host, port, database, username, password, optional
   table prefix. Click **Test Connection** (the installer will create the
   database if the MySQL user has the privilege). Optionally tick **Install
   demo data**. Submitting writes `.env` and runs all migrations + seeders.
   ![screenshot placeholder](screenshots/install-database.png)
4. **Admin account** — your name, email, password, plus website name, URL,
   default language (English / ગુજરાતી / हिन्दी), timezone and currency.
5. **Finish** — the `APP_KEY` is generated, `storage/installed.lock` is
   written, and `/install` is blocked forever. You're redirected to log in.
   ![screenshot placeholder](screenshots/install-finish.png)

## 5. Cron job (required)

The scheduler powers stats retention, subscription renewals, dunning emails
and the cron health monitor shown in Admin → Settings → Advanced:

```bash
crontab -e -u www-data
* * * * * cd /var/www/shortl && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Queue worker (required)

Clicks, webhooks and emails are processed asynchronously:

```bash
php artisan queue:work --tries=3 --timeout=90
```

Keep it alive with Supervisor — `/etc/supervisor/conf.d/shortl-worker.conf`:

```ini
[program:shortl-worker]
command=php /var/www/shortl/artisan queue:work --tries=3 --timeout=90
user=www-data
numprocs=2
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/log/shortl-worker.log
```

```bash
supervisorctl reread && supervisorctl update
```

(systemd unit files work equally well.)

## 7. Post-install configuration (all in the browser)

Open **Admin → Settings**:

- **Email** — SMTP host/port/credentials, then hit **Send test email**.
- **Payments** — toggle on the gateways you use and paste API keys. Each
  gateway card shows the webhook URL to configure at the provider
  (`https://your-domain.com/webhooks/stripe` etc.). For India, enable **UPI**
  (your VPA) and/or **Bank transfer** — those payments appear in
  Admin → Payments for one-click approval.
- **General** — logo, favicon, timezone, currency, announcement banner,
  cookie consent, maintenance mode.
- **Security** — reCAPTCHA v2/v3 or hCaptcha keys, Google Safe Browsing key.
- **Social login** — client IDs/secrets; the redirect URL to register at each
  provider is shown next to the fields.
- **Storage** — switch uploads to S3/DigitalOcean Spaces.
- **GDPR** — IP anonymization or full no-personal-data mode.

## 8. Scaling to high traffic (optional)

- Set `CACHE_STORE=redis` and `QUEUE_CONNECTION=redis` in `.env`
  (install `php-redis`). Link lookups on the redirect path are cached, so
  redirects stay **< 50 ms** even under load.
- Raw clicks are rolled up daily per link; the pruning job keeps the raw
  table bounded by each plan's retention window. Charts always read the
  rollups, never the raw table.
- Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
  after deployment.

## 9. Updating to a new version

1. Back up the database (Admin → sidebar → **Download backup**).
2. Upload the new release over the old files — **keep** `.env`, `storage/`,
   and your `addons/`.
3. `composer install --no-dev --optimize-autoloader`
4. Visit **`/update`** — it lists pending migrations, runs them with one
   click, clears caches and records the new version.

## Troubleshooting

| Symptom | Fix |
|---|---|
| 500 after upload | check `storage/` permissions; `php artisan config:clear` |
| `/install` shows 404 | the app is already installed (`storage/installed.lock` exists) |
| Emails not sending | Admin → Settings → Email → *Send test email* shows the exact SMTP error |
| Clicks not appearing | queue worker not running — see step 6; check Admin → Settings → Advanced |
| Cron "unhealthy" badge | the crontab entry from step 5 is missing |
| Custom domain won't verify | DNS not propagated yet, or the domain doesn't point at this server; the alternative HTTP check needs `/.well-known/shortl-verify` to serve the token shown on the Domains page |
