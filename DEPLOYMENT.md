# Deploying FlatCare to http://flatcare.dineflowpro.com/

This is a plain Laravel app (no subdomain-per-society tenancy — the society
portal resolves its tenant from the session, not the hostname), so a single
domain is all you need. What differs between environments is entirely in
`.env`, which is git-ignored and must be created by hand on the server.

## 1. Server requirements

- PHP 8.2+ with the extensions Laravel needs: `pdo_mysql`, `mbstring`,
  `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`
  (or `imagick`, for PDF/receipt work).
- MySQL/MariaDB.
- Composer 2.
- Node/npm — only needed wherever you run `npm run build` (can be your local
  machine; only the compiled `public/build/` output needs to reach the
  server).

## 2. Document root — pick ONE of these

**Preferred:** point the domain's document root straight at `/public`. This
is the standard, safest Laravel layout — `app/`, `config/`, `.env`,
`storage/`, `vendor/` all sit outside the web root and are never reachable
over HTTP, and you don't need any rewrite trick.

**If your host only gives you one folder (e.g. shared cPanel hosting where
the domain root IS the project root):** the existing [.htaccess](.htaccess)
already rewrites every request into `/public`, but its `RewriteCond` is
hardcoded to the local WAMP folder name `/FlatCare/public/`. That line must
be removed/adjusted for the real deployment — as written it only protects
against re-rewriting a path that will never occur on the live server, so the
safe fix is to drop that `RewriteCond` line entirely before uploading.

## 3. Production `.env`

Copy `.env.example` on the server and fill in real values — do **not** copy
your local `.env` as-is (it has local DB creds, `APP_DEBUG=true`, test
Razorpay keys, etc.):

```env
APP_NAME=FlatCare
APP_ENV=production
APP_KEY=                      # fill in via `php artisan key:generate` on the server, see below
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=http://flatcare.dineflowpro.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flatcare_main
DB_MAIN_DATABASE=flatcare_main
DB_USERNAME=<production db user>
DB_PASSWORD=<production db password>

SESSION_DRIVER=database
SESSION_DOMAIN=null            # fine as-is — single domain, no subdomain tenancy

QUEUE_CONNECTION=sync          # nothing in the app dispatches queued jobs today; sync avoids needing a queue worker process at all

MAIL_MAILER=smtp               # replace the local `log` driver with your real SMTP provider
MAIL_HOST=<smtp host>
MAIL_PORT=587
MAIL_USERNAME=<smtp user>
MAIL_PASSWORD=<smtp password>
MAIL_FROM_ADDRESS=<real from address>
MAIL_FROM_NAME="${APP_NAME}"

# Switch to LIVE-mode keys (Razorpay Dashboard > Live Mode > Settings > API Keys).
# Test-mode keys will not accept real payments.
RAZORPAY_KEY_ID=<live key id>
RAZORPAY_KEY_SECRET=<live key secret>
```

If you get SSL working (recommended — this app takes real payments via
Razorpay), change `APP_URL` to `https://flatcare.dineflowpro.com` and add an
http→https redirect in `.htaccess`.

### Important: the multi-tenant database privilege

FlatCare provisions a **new physical MySQL database per society** at signup
time (`TenantService::runTenantMigrations()`, driven by
`SocietyDatabase`/`MigrateTenants`). The `DB_USERNAME` in production **must
have `CREATE DATABASE` privilege**, or every new society signup will fail
silently at the provisioning step. Many shared-hosting control panels
(cPanel etc.) only let you create databases through their UI and don't grant
this to the app's DB user by default — confirm with your host, or use a
VPS where you control MySQL grants directly.

## 4. Deploy steps

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # or build locally and upload public/build/
cp .env.example .env             # then fill in the values above
php artisan key:generate --force
php artisan migrate --force      # main database only — tenant DBs are provisioned per-society at signup
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set `storage/` and `bootstrap/cache/` writable by the web server user (on
Linux hosting: `chmod -R 775` + correct ownership).

## 5. Mobile app

The Flutter app's API base URL is a build-time flag, not a `.env` value —
see [mobile/lib/core/config/app_config.dart](mobile/lib/core/config/app_config.dart).
A release build with the wrong (or missing) flag silently falls back to the
emulator-only `10.0.2.2` address and can't reach the live server at all, so
it is baked into the release pipeline below (switch it to `https://` once
SSL is on, by setting the `API_BASE_URL` **repository variable**).

### How the "Download App" button works

The landing page buttons link to
`config('flatcare.apk_url') ?? '/downloads/flatcare-app.apk'` (root-relative
so the link keeps the page's own https — a hardcoded `http://` link on an
https page is a mixed-content download and browsers block it).

**Default (current) — serve the APK from the server.** Build the release
APK and upload it to:

```
public/downloads/flatcare-app.apk
```

by FTP / cPanel File Manager. That folder is committed (so `git pull`
creates it) but its contents are git-ignored, so the APK never goes through
the repo — see [public/downloads/README.md](public/downloads/README.md).
Once the file is there the download works with no deploy step. Ship an
update by overwriting that one file.

Build it with:

```bash
cd mobile
flutter build apk --release --split-per-abi \
  --dart-define=API_BASE_URL=http://flatcare.dineflowpro.com/api/v1
# upload build/app/outputs/flutter-apk/app-arm64-v8a-release.apk
# to the server as public/downloads/flatcare-app.apk  (~19 MB, fits every
# phone since ~2019)
```

(switch the flag to `https://` once SSL is on — a wrong/missing value makes
release builds fall back to the emulator-only `10.0.2.2` and never reach the
server.)

**Alternative — GitHub Releases (only if the repo is public).** Set
`MOBILE_APK_URL` in the production `.env` to
`https://github.com/<owner>/flatcare/releases/latest/download/flatcare-app.apk`
and publish the APK as a release asset named `flatcare-app.apk`. Release
assets on a **private** repo cannot be downloaded anonymously, which is why
this is not the default. [.github/workflows/release-apk.yml](.github/workflows/release-apk.yml)
can build, sign and publish that release automatically on an `app-v*` tag —
it needs a one-time upload keystore and four `ANDROID_*` Actions secrets
(see the workflow header and
[mobile/android/key.properties.example](mobile/android/key.properties.example)).

If you ever host the APK somewhere else, set `MOBILE_APK_URL` in the
production `.env`.

## 6. After go-live

- Confirm a fresh society signup actually provisions its database (tests the
  `CREATE DATABASE` privilege end-to-end).
- Send a real payment through Razorpay in live mode for a small amount to
  confirm the live keys and webhook (if configured) work.
- Watch `storage/logs/laravel.log` for the first real traffic.
