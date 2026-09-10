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

The landing page buttons link to `config('flatcare.apk_url')`, which
defaults to the **latest GitHub Release asset** on the public repo:

```
https://github.com/raviahir2802-hash/flatcare/releases/latest/download/flatcare-app.apk
```

The APK is **never committed** (a debug build is ~150 MB; even a release
build is over GitHub's 100 MB file limit) and does **not** go in `public/`.
It is built and attached to a Release by GitHub Actions
([.github/workflows/release-apk.yml](.github/workflows/release-apk.yml)).
`releases/latest/download/…` always resolves to the newest release, so
shipping an update never touches the server or the code.

> The workflow must live on a **public** repo (`raviahir2802-hash/flatcare`)
> — release assets on a private repo cannot be downloaded anonymously.

### One-time setup

1. **Create an upload keystore** (do this once, keep it forever — losing it
   means no installed app can ever be updated):

   ```bash
   keytool -genkey -v -keystore upload-keystore.jks -storetype JKS \
     -keyalg RSA -keysize 2048 -validity 10000 -alias upload
   ```

2. **Add four GitHub Actions secrets** (repo → Settings → Secrets and
   variables → Actions):

   | Secret | Value |
   |---|---|
   | `ANDROID_KEYSTORE_BASE64` | `base64 -w0 upload-keystore.jks` output |
   | `ANDROID_KEYSTORE_PASSWORD` | the store password from step 1 |
   | `ANDROID_KEY_ALIAS` | `upload` |
   | `ANDROID_KEY_PASSWORD` | the key password from step 1 |

3. *(optional)* Add an `API_BASE_URL` repository **variable** to override the
   default `http://flatcare.dineflowpro.com/api/v1` (e.g. once on HTTPS).

For local release builds, copy
[mobile/android/key.properties.example](mobile/android/key.properties.example)
to `key.properties` and drop `upload-keystore.jks` in `mobile/android/app/`.
Both are git-ignored. Without them, a local `flutter build apk --release`
falls back to the debug signing key (fine for testing, never for a build you
hand to users).

### Shipping an app update

```bash
# bump `version:` in mobile/pubspec.yaml, then:
git tag app-v1.0.1
git push origin app-v1.0.1
```

Actions builds, signs, and publishes the release in a few minutes; the
landing page picks it up automatically. You can also trigger a build by
hand from the repo's **Actions** tab (`workflow_dispatch`, type a version).

If you ever host the APK somewhere else, set `MOBILE_APK_URL` in the
production `.env`.

## 6. After go-live

- Confirm a fresh society signup actually provisions its database (tests the
  `CREATE DATABASE` privilege end-to-end).
- Send a real payment through Razorpay in live mode for a small amount to
  confirm the live keys and webhook (if configured) work.
- Watch `storage/logs/laravel.log` for the first real traffic.
