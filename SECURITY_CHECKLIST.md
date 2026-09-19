# FlatCare – Security Checklist

Companion to [SECURITY_AUDIT.md](SECURITY_AUDIT.md) (backend/API, this round) and [SECURITY_REPORT.md](SECURITY_REPORT.md) (front end / uploads / headers). ✅ = in place and tested · ⚠️ = partial / needs your action · ❌ = not done. Nothing here means "100% secure".

## Authentication
- ✅ Passwords bcrypt (12 rounds), never returned by any API Resource; OTP/directory accounts get random 20-char passwords
- ✅ Bearer tokens: 64 random chars, SHA-256 at rest, 90-day expiry, identity (user + society) resolved from the token row, never from the request
- ✅ Logout deletes the token; password reset, password change (all other tokens), deactivation and admin password-reset revoke tokens
- ✅ Login lockout per email+IP **and** per account (20 failures / 15 min, IP-independent); route throttles: login 10/min, forgot/reset 5/min, OTP verify 10/min, OTP request 5/min per IP + 10/hour per number
- ✅ Same error for unknown e-mail and wrong password; constant-time dummy hash for unknown e-mails
- ✅ Session regenerated on login, invalidated on logout; cookies HttpOnly, SameSite=Lax, Secure on HTTPS, encrypted
- ❌ **OTP is the fixed code `0000` – CRITICAL (R1). Connect an SMS gateway before real use**
- ⚠️ No refresh-token rotation (long-lived 90-day tokens)

## Authorization
- ✅ Every `/api/v1` route except login/reset/OTP requires `api.auth` (asserted by a test); `/api/v1/guard/*` requires `role:security` (asserted)
- ✅ Resident data scoped by `myFlatIds()` / `user_id`; client-supplied `flat_id` is re-checked on every write
- ✅ Society portal is staff-only: a plain `resident`/role-less user is bounced at the door; `admins, blocks, security, payments, extra-charges, water-readings` are admin-only regardless of the menu catalog; uncataloged routes are admin-only (fail-closed)
- ✅ A society admin cannot grant `super_admin`
- ✅ Client-sent `role`, `society_id`, `is_admin`, `status`, `email`, `password` are ignored on profile updates (tested)
- ⚠️ Roles below admin see the modules the menu catalog gives them (Settings → Menu Settings)

## Multi-tenant isolation
- ✅ Database-per-society; `AuthenticateApiToken` / `SetSocietyContext` point the connection at the token's/session's society – there is no client-controlled tenant selector
- ✅ **Two-society test: 59/59 checks pass** (`tests/Security/`): list/read/write/delete of B by an A resident, guard and admin all fail; forged `society_id` in body/query/headers ignored; symmetric B→A
- ⚠️ Uploaded files share one public disk with unguessable names – not access-checked per tenant (R2)
- ⚠️ Same e-mail/mobile in two societies: first active society wins at login (R7)

## SQL injection
- ✅ Query builder / bound parameters everywhere; the 8 raw fragments use constants + bindings; no user-controlled `ORDER BY`/`LIMIT`/column names
- ✅ Query-string filters validated (allow-lists, max length, `date`); the only interpolated identifier (tenant DB name in DDL) is regex-validated
- ✅ Live: `search=' OR 1=1--` is treated as a literal; invalid date/status → 422

## XSS / injection
- ✅ Output: Blade `{{ }}` escaping; one `{!! !!}` (escape-then-format testimonials); no inline JS; no `innerHTML` with data
- ✅ Input: global `SanitizeHtmlInput` strips tags/script blocks/comments/NUL and script-URI-only values from JSON, form and query input (credentials untouched)
- ✅ CSP without `unsafe-inline`/`unsafe-eval` for scripts; SRI on CDN assets; SVG sanitizer without entity expansion
- ⚠️ `style-src 'unsafe-inline'` remains (R10)

## File uploads
- ✅ Allow-listed extensions, sniffed MIME must match, images must decode and be ≤ 8000×8000, PDFs need `%PDF-`, executable magic rejected, **whole file** scanned for `<?php`, `<script`, `<%`, `javascript:` …; random server names; blocked attempts logged
- ✅ `.htaccess` denies `*.php/phtml/html/js/exe…` under `/storage`, blocks dot-files, adds `nosniff` + sandbox CSP for stored files
- ✅ Per-token upload/creation limits (30/hour); 4 MB image cap
- ❌ No antivirus scanning (R4) · ⚠️ files are inside the web root via a symlink (script execution is denied, but consider a private disk – R2)

## CSRF
- ✅ Web (session) routes: `ValidateCsrfToken`, no exempt paths, POST without token → 419 (live), SameSite=Lax cookies
- ✅ API: bearer token, no cookie auth → CSRF tokens intentionally not added

## CORS
- ✅ `config/cors.php`: no wildcard, allow-list via `CORS_ALLOWED_ORIGINS` (empty by default), localhost only when `APP_ENV=local`, no credentials
- ✅ Live: foreign origin gets no `Access-Control-Allow-Origin`

## Rate limiting
- ✅ login, OTP, password reset, password change, visitor/gate-pass creation, guard creation, uploads, authenticated API (120/min per token), admin & society panels (300/min per session+IP) – all return 429; keys never include request parameters
- ⚠️ Per-IP limits are only as good as the trusted-proxy setting – set `TRUSTED_PROXIES` (R5)

## API security
- ✅ Resources return only needed fields; other residents' phone/e-mail masked in the directory
- ✅ Errors: generic in production (`APP_DEBUG` forced off), consistent `{success,message,errors}`
- ✅ No SSRF surface (no user-supplied URLs fetched), no webhooks, no outbound HTTP except Razorpay
- ✅ Payments: Razorpay signature via SDK + server-side payment fetch + amount check + transaction/row lock; secret never sent to the app

## Database security
- ❌ App uses MySQL `root` locally (R3) – use the least-privilege user below in production
- ✅ Tenant DB passwords stored `encrypt()`-ed; `.env` untracked
- ⚠️ Review: MySQL bound to 127.0.0.1, port 3306 firewalled, encrypted off-server backups

## Server security
- ✅ `public/.htaccess`: no directory listing, dot-files denied, scripts/HTML under `/storage` denied, `X-Powered-By` removed
- ✅ Security headers (CSP, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, COOP; HSTS + `upgrade-insecure-requests` only over HTTPS; API `no-store`)
- ⚠️ `.htaccess` verified for syntax only; verify on the real server. Nginx needs the equivalent rules (below)

## Dependency security
- ✅ `npm audit --omit=dev`: 0 vulnerabilities
- ⚠️ `composer audit`: clean in round 1; the latest re-run failed with Packagist HTTP 502 – re-run in CI
- ⚠️ Flutter: no advisory database; outdated majors listed in SECURITY_REPORT.md (R11). Unused-package review not done

## Logging
- ✅ `storage/logs/security-*.log` (90 days): failed logins (web/society/API/OTP), lockouts, 403s, portal/route denials, CSRF failures, invalid signatures, 429s, blocked uploads, privilege changes (grant/change/activate/deactivate/password reset – see note in SECURITY_AUDIT V8)
- ✅ Never logged: passwords, OTPs, tokens, secrets (`SecurityLog` drops secret-looking keys; OTP only logged on `APP_ENV=local`)
- ⚠️ Ship the log off-server and alert on bursts of `auth.lockout`, `rate_limit.exceeded`, `authz.*`, `upload.blocked`
- ✅ Admin panel actions are also in the existing Audit Log

## Production deployment
**`.env`**
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://flatcare.in
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
LOG_LEVEL=warning
TRUSTED_PROXIES=<load balancer / reverse proxy IP(s)>      # not "*"
CORS_ALLOWED_ORIGINS=                                       # only if a web client on another origin exists
OTP_DEFAULT_CODE=<not 0000 until an SMS gateway exists>
API_RATE_LIMIT_PER_MINUTE=120
MAIL_MAILER=smtp
```
**Least-privilege MySQL user**
```sql
CREATE USER 'flatcare_app'@'localhost' IDENTIFIED BY '<long random password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON `flatcare_main`.* TO 'flatcare_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON `society\_%`.* TO 'flatcare_app'@'localhost';
GRANT CREATE ON *.* TO 'flatcare_app'@'localhost';  -- only while provisioning new society databases
```
**Steps**
- [ ] `php artisan migrate --force`, `php artisan tenants:migrate`, seed `RoleMenuSettingSeeder`, then `php artisan config:cache route:cache view:cache`
- [ ] Document root = `public/`; `/.env`, `/.git/config`, `/storage/logs/laravel.log`, `/composer.json`, `/vendor/autoload.php` must all be 403/404
- [ ] HTTPS only, HTTP→HTTPS redirect, TLS ≥ 1.2, `ServerTokens Prod`, `expose_php=Off`, directory listing off
- [ ] Apache: `mod_rewrite` + `mod_headers`, `AllowOverride All` for `public/`. **Nginx:** `location ~* ^/storage/.*\.(php\d?|phtml|phar|cgi|pl|py|sh|html?|js)$ { return 403; }`, `location ~ /\.(?!well-known) { deny all; }`, and for `/storage/` add `X-Content-Type-Options nosniff` and `Content-Security-Policy "default-src 'none'; sandbox"`
- [ ] No PHP execution in `storage/`; app user owns `storage/` and `bootstrap/cache/` only; files 644 / dirs 755; `.env` 640
- [ ] Firewall: 22 (keys, restricted IPs), 80, 443 only; MySQL on 127.0.0.1; fail2ban for SSH and repeated 401/419/429
- [ ] `curl -sI https://flatcare.in/` shows CSP, HSTS, `nosniff`, `X-Frame-Options`, no `X-Powered-By`; `/api/v1/me` → 401 + `no-store`; `Origin: https://evil.example` gets no CORS header
- [ ] Log in as a *resident with a password* to `/society/login` → must be refused
- [ ] Run `tests/Security/multi_society_*` on staging (never on production)
- [ ] Mobile: build with `--dart-define=API_BASE_URL=https://flatcare.in/api/v1`, sign with your keystore, test login on a real device (release forbids cleartext/backup)
- [ ] Wire an SMS OTP provider (R1) and rotate any credential that ever appeared in a log

## Re-run the checks
```
php artisan test                                       # 38 tests incl. 36 security tests
php tests/Security/multi_society_setup.php && python tests/Security/multi_society_test.py; php tests/Security/multi_society_cleanup.php
composer audit && npm audit --omit=dev
git grep -nE "\b(eval|exec|system|shell_exec|passthru|popen|proc_open|unserialize)\s*\(" -- app     # expect none
```
