# FlatCare – Security Audit Report

Date: 2026-09-19 · Scope: Laravel 12 backend (web admin panel, society portal, `/api/v1`), Blade/JS front end, Flutter mobile app (`mobile/`), server configuration files.

**This is not a claim that the application is "100% secure".** It lists what was found, what was changed, how each change was verified and what risk is still open. One **Critical** risk (R1, fixed OTP code) remains because it needs an SMS gateway that only you can provide.

## 0. Architecture (what was audited)

| Layer | Finding |
|---|---|
| Framework | Laravel 12 / PHP 8.2, Blade + AdminLTE 3 + Bootstrap, Flutter (Riverpod, Dio) mobile app |
| Multi-tenant | Main DB (`flatcare_main`) + one MySQL database per society, selected per request (`TenantService`) |
| Auth | Web: session (`web` guard for super-admin, `society` guard for society portal). Mobile API: opaque bearer token, SHA-256 hashed in DB, 90-day expiry (`ApiTokenService`), roles via `role:` middleware |
| Existing controls reused | `SecurityHeaders` middleware, `SafeUploadedFile` rule, `SvgSanitizer`, per-route `throttle`, per-email+IP login lockout, Blade auto-escaping, FormRequest validation, `EnsureMenuItemVisible` |
| Scans run | Search of the whole codebase for `eval/exec/system/shell_exec/unserialize`, `{!! !!}`, `innerHTML`, raw SQL, redirects, mass-assignment, file I/O; `composer audit`; `npm audit`; `flutter pub outdated`; secret grep of tracked files |

Clean results: **no** command execution / eval / unsafe deserialization anywhere in `app/`; **no** `$request->all()` mass assignment and no `$guarded = []`; **no** user-controlled `orderBy`; **no** user-controlled redirect target (only `redirect()->intended()`, whose target is set by the framework); **no** user-controlled file path (downloads/deletes use paths stored by the server); `composer audit` and `npm audit`: **0 advisories**; no API keys / private keys / passwords in tracked files.

## 1. Vulnerabilities found and fixed

Format: **A** finding · **B** location · **C** risk · **D** why · **E** fix · **F** improvement · **G** test · **H** remaining.

### F1 – XXE / entity expansion and incomplete script stripping in the SVG logo sanitizer
- **B** `app/Support/SvgSanitizer.php` (used by the platform-logo upload)
- **C** High
- **D** It parsed with `LIBXML_NOENT`, which substitutes entities. A `<!ENTITY x SYSTEM "file:///...">` SVG could pull a local server file into the stored, publicly served logo. It also left `<foreignObject>`, SMIL `<animate>/<set>` (can rewrite an `href` to `javascript:` at runtime) and CSS `style="…url(javascript:…)"` intact.
- **E** Reject any `<!DOCTYPE`/`<!ENTITY`; parse without `LIBXML_NOENT`; remove `script, foreignObject, iframe, object, embed, animate, set, handler, listener`; strip `javascript:/data:/vbscript:` URIs (control-char tolerant) and dangerous `style` values.
- **F** Closes local-file disclosure and SVG-based script execution.
- **G** PHPUnit `test_svg_sanitizer_strips_script_handlers_and_js_uris`, `test_svg_sanitizer_refuses_xxe_and_entity_declarations` – pass.
- **H** SVG is only accepted for the logo (super-admin only).

### F2 – Society-portal authorization failed **open** for routes missing from the menu catalog
- **B** `app/Http/Middleware/EnsureMenuItemVisible.php`
- **C** High
- **D** Route-level authorization was only enforced when a route matched a `menu_items` row; otherwise `return $next()`. With a stale/incomplete catalog (this dev DB is missing `admins`, `blocks`, `security`, `extra-charges`) any signed-in tenant user – including a *resident who set a password through "forgot password"* – could open `/society/admins` (create admin users), `/society/blocks`, `/society/security`. Verified by listing the routes vs the catalog.
- **E** An uncataloged route is now allowed **only for `admin`/`super_admin`**; everyone else gets 404 and a `authz.uncataloged_route_denied` security-log entry.
- **F** Fail-closed: a missing catalog row can no longer become a privilege-escalation path. No behaviour change when the catalog is complete (all `society.*` routes map to a menu item – verified).
- **G** Route/catalog enumeration via tinker (before/after); middleware lint.
- **H** See R7 (any tenant user can still *sign in* to the society portal; access is by menu role). Run `php artisan migrate` + `RoleMenuSettingSeeder` so the catalog is complete.

### F3 – Inline `confirm()` handlers were dead under the CSP (destructive actions ran without asking) and interpolated data into a JS string
- **B** 24 places in `resources/views/admin/**` and `society/**` (delete inquiry/role/block/flat/document/contact/fee type/poll…, reset admin password)
- **C** High (integrity) / Medium (injection)
- **D** `onsubmit="return confirm('…')"` / `onclick=` are blocked by `script-src 'self'`, so the prompt never appeared and the form submitted directly. One handler embedded `{{ $admin->name }}` inside a JS string – HTML-escaped, but the browser decodes `&#039;` before JS parses it, so a name such as `x');…//` breaks out of the string (DOM XSS; blocked only by the CSP).
- **E** All converted to `data-confirm="…"` (plain escaped attribute). New `public/js/security-ui.js` (delegated, capture-phase click/submit listener) is loaded on every AdminLTE page via the `SecurityUi` plugin in `config/adminlte.php`. `society-ui.js` no longer binds its own copy (avoids a double prompt).
- **F** Confirmation prompts work again; no data ever reaches a JS context.
- **G** `grep` – 0 inline `on*=` handlers left in `resources/views` (excluding the vendor AdminLTE partials); pages render (200).
- **H** None.

### F4 – Upload validation only inspected the first 4 KB and never decoded images
- **B** `app/Rules/SafeUploadedFile.php` (used by visitor/guard photos, daily helpers, security guards, documents, logo/icon)
- **C** Medium
- **D** A PHP/JS payload appended after valid image bytes (polyglot) passed; no dimension limit (decompression bomb); PDFs weren't signature-checked; rejections weren't logged.
- **E** Now: extension allow-list → `finfo` sniffed MIME must match → images must decode (`getimagesize`) and be ≤ 8000×8000 → PDFs must start `%PDF-` → executable magic (`MZ`, `ELF`, `#!`) → whole-file scan (≤ 12 MB) for `<?php`, `<?=`, `<script`, `<%`, `javascript:`, `<iframe/object/embed`. Every rejection writes `upload.blocked` (reason, extension, sniffed MIME – never the file name/content) to the security log. Also fixed a leaked `finfo` handle.
- **F** Blocks polyglots, renamed executables, HTML/JS masquerading as PDF/images, pixel bombs. Server-side names remain random (`->store()` hash names; extension from sniffed MIME).
- **G** PHPUnit (8 upload cases incl. polyglot, bomb, `x.php.jpg.php`, HTML-as-PDF) + **live** `POST /api/v1/visitors` with `shell.php`, `shell.jpg` (PHP), `gif.php.gif`, polyglot PNG, `page.html` → all **422**; a genuine PNG → 201 and stored under a random name (test file deleted afterwards).
- **H** No antivirus (R4). Office formats (docx/xlsx) are zip containers: type is enforced by MIME sniffing, contents are not scanned.

### F5 – Uploaded files served by Apache with no script/HTML protection or headers
- **B** `public/.htaccess` (uploads live under `/storage` → `storage/app/public`)
- **C** Medium
- **D** `/storage/*` is served directly by the web server, so Laravel's `SecurityHeaders` never runs, and nothing stopped a `.php/.html/.js` file placed there (by another bug) from being executed/rendered. Dot-files were not blocked either.
- **E** `.htaccess`: 403 for dot-files/dirs (except `.well-known`); 403 for `*.php|phtml|phar|cgi|pl|py|sh|asp(x)|jsp|exe|html?|js|mjs` under `/storage`; for `/storage/*` responses add `X-Content-Type-Options: nosniff` and `Content-Security-Policy: default-src 'none'; …; sandbox` (so even a directly-opened SVG cannot run script); remove `X-Powered-By`. Directory listing was already off.
- **F** Defence in depth if any future upload bug lands a script in the uploads folder.
- **G** Apache `httpd -t` OK. **Not executed** – the dev server (`artisan serve`) ignores `.htaccess`; verify on the real Apache/Nginx (checklist).
- **H** Nginx needs the equivalent rules (checklist).

### F6 – CSP silently broke two inline scripts; CDN assets had no integrity check
- **B** `welcome.blade.php` (trial modal auto-open), `admin/settings/branding.blade.php` (file-name label), CDN tags in `welcome.blade.php` and `society/layout.blade.php`
- **C** Medium (supply chain) / Low (function)
- **D** Inline `<script>` is blocked by `script-src 'self'`, so the thank-you/validation modal never re-opened. Bootstrap/Bootstrap-Icons were loaded from a CDN without Subresource Integrity – a CDN compromise would run arbitrary code on the landing page.
- **E** Scripts moved to `public/js/welcome-trial-modal.js` and `admin-branding.js` (state passed via a `data-open` attribute). `integrity="sha384-…" crossorigin="anonymous"` added to Bootstrap CSS 5.3.3, Bootstrap-Icons 1.11.3 and the Bootstrap bundle (hashes computed from the live files and match the official Bootstrap values).
- **F** No inline JS anywhere; tampered CDN files are refused by the browser.
- **G** Rendered `/`: 3 `integrity=` attributes, 0 inline `<script>`, 0 inline handlers; after an invalid submission `data-open="1"` is present.
- **H** CSP still allows `cdn.jsdelivr.net` for scripts (needed for Bootstrap). Vendoring these files locally would let you drop it (R5).

### F7 – No rate limit on the authenticated API; OTP requests limited per IP only
- **B** `routes/api.php`, `app/Providers/AppServiceProvider.php`
- **C** Medium
- **D** After login, endpoints were unthrottled (scraping/abuse with one token); `otp/request` could be used from many IPs to spam one phone number (and burn SMS credits once a gateway exists).
- **E** `throttle:api-auth` (default 120/min, `API_RATE_LIMIT_PER_MINUTE`, keyed by hashed token, else IP) on the authenticated group; `throttle:otp-request` = 5/min per IP **and** 10/hour per mobile number.
- **G** Live: 7 OTP requests → `200×5, 429×2`; 7 wrong API logins → lockout message after 5; both produced `rate_limit.exceeded` / `auth.lockout` log lines.
- **H** Web society-portal routes rely on login throttles only.

### F8 – Old API tokens survived a password change
- **B** `Api\V1\Resident\ProfileController::updatePassword`, `ApiTokenService`
- **C** Medium
- **D** Changing the password kept every previously issued token alive, so a stolen token outlived the fix.
- **E** New `ApiTokenService::revokeAllExcept()`; all other tokens of that user are deleted, the current device stays signed in. (Password *reset* already revoked all tokens; logout already deletes the token; web logout already invalidates the session and regenerates the CSRF token.)
- **G** Code path lint-checked; wrong-current-password path tested live (422). The success path was **not** exercised end-to-end (it would change a demo account's password).
- **H** None.

### F9 – OTP code and phone number written to `laravel.log` in every environment
- **B** `app/Services/Api/OtpService.php`
- **C** Medium
- **D** A log file must never hold a working credential; the fixed code and full number were logged on every request.
- **E** Code logged only when `APP_ENV=local`; elsewhere only the last 4 digits of the number.
- **G** Read-through + security log contains no codes/passwords/tokens (grep = 0).
- **H** R1.

### F10 – Cookie/session defaults
- **B** `config/session.php`
- **C** Low
- **E** `secure` defaults to **true when `APP_URL` is `https://`**; `encrypt` defaults to **true**; `http_only=true` and `same_site=lax` were already set.
- **H** Your local `.env` has `SESSION_ENCRYPT=false` (explicit override) – set it to `true` on the server (checklist).

### F11 – Header/CSP gaps
- **B** `app/Http/Middleware/SecurityHeaders.php`
- **C** Low
- **E** Added `object-src 'none'`, `upgrade-insecure-requests` + HSTS (HTTPS only), `Cross-Origin-Opener-Policy`, `X-Permitted-Cross-Domain-Policies`, wider `Permissions-Policy`, removal of `X-Powered-By`; JSON API responses get `default-src 'none'; frame-ancestors 'none'` and `Cache-Control: no-store`; signed-in admin/society pages get `no-store, private` (no Back-button view after logout). No `unsafe-eval`, no `unsafe-inline` on `script-src`.
- **G** PHPUnit (3 header tests) + live `curl -D -` of `/` and `/api/v1/visitors` (values shown in SECURITY_CHECKLIST.md).
- **H** `style-src 'unsafe-inline'` is kept because Bootstrap/AdminLTE/inline `style=` need it (R5).

### F12 – Database name interpolated into DDL
- **B** `TenantService::createDatabase()` · **C** Low
- **E** Identifier must match `[A-Za-z0-9_-]{1,64}` and contain no backtick, else `InvalidArgumentException`. (The name is `society_{id}_{slug}` – the slug is generated by the framework – so this is defence in depth.)

### F13 – Unvalidated query-string filters caused HTTP 500
- **B** `Api\V1\Resident\VisitorController::index` · **C** Low
- **D** `?to=x';DROP…` → unhandled Carbon exception (500, noisy, information-leaking in debug).
- **E** `status`, `kind` (allow-lists), `search` (≤100), `from`/`to` (`date`) validated → clean 422.
- **G** Live: `search=' OR 1=1--` → 200 (treated as literal, bound parameter), bad date → 422, bad status → 422.

### F14 – Proxy trust
- **B** `bootstrap/app.php` · **C** Low
- **D** `trustProxies(at: '*')` lets any client spoof `X-Forwarded-For`, defeating per-IP throttles when the app is reachable directly.
- **E** Honours new `TRUSTED_PROXIES` env (comma-separated IPs); default unchanged (`*`) so nothing breaks until you set it.

### F15 – Android release allowed backup and cleartext traffic
- **B** `mobile/android/app/src/main/AndroidManifest.xml`, `src/debug/AndroidManifest.xml` · **C** Low/Medium
- **E** Release: `allowBackup=false` (token not extractable via `adb backup`), `usesCleartextTraffic=false` (HTTPS only – production is `https://flatcare.in`). Debug manifest re-enables cleartext (`tools:replace`) for the emulator (`http://10.0.2.2:8000`). Token storage was already `flutter_secure_storage`.
- **G** Both manifests are valid XML. **Not built** (no Gradle build run) – build a release APK and test login before shipping.

### F16 – `{!! !!}` in the landing page
- **B** `welcome.blade.php` testimonials · **C** Low (was static text, but a raw-HTML pattern)
- **E** Text is escaped with `e()` first; only our own `**bold**` markers become `<b>`.
- **G** Verified: `"><script>alert(1)</script><img onerror=…>` submitted through the public form is re-rendered as `&quot;&gt;&lt;script&gt;…` (0 raw tags).

### F17 – Production guards + security monitoring (new)
- **B** `AppServiceProvider`, `bootstrap/app.php`, `app/Support/SecurityLog.php`, `config/logging.php` (`security` channel, 90 days), login form requests
- If `APP_ENV=production` and `APP_DEBUG=true`, debug is forced **off** and a `critical` line is logged; a `critical` line is also logged while `OTP_DEFAULT_CODE` is still `0000`.
- Security log (`storage/logs/security-YYYY-MM-DD.log`) records: `auth.*_login_failed` (web, society, API, OTP), `auth.lockout`, `authz.denied` (403), `authz.uncataloged_route_denied`, `rate_limit.exceeded`, `csrf.token_mismatch`, `signature.invalid`, `upload.blocked`. Fields: event, IP, method, path (no query string), user agent, and the e-mail or last 4 digits of the mobile number. `SecurityLog` drops any context key that looks like a secret (`pass|token|secret|otp|code|key|authorization|cookie`).
- **G** Live: log lines produced for failed login, lockout and rate limit; grep for passwords/`0000`/`Bearer` in the log = 0.
- Admin actions are additionally recorded by the existing **Audit Log** (Admin → Audit).

## 2. Areas tested and found sound (no change needed)

| Area | Result |
|---|---|
| IDOR / BOLA (API) | Logged in as flat A-101's resident, planted a visitor and a daily helper on flat A-102 and tried `GET/DELETE/approve/reject visitors/{id}`, `DELETE daily-helpers/{id}` → all **404**, and neither appears in the lists. Guard endpoints with a resident token → **403**. Family/vehicle/notification endpoints scope by `user_id` |
| Mass assignment | `POST /visitors` with `status=checked_in&invited_by_user_id=1` → stored as `pending` (validated fields only) |
| SQL injection | All searches use bound `LIKE`; raw fragments (`whereRaw`/`orderByRaw`) use constants + bindings; only DDL identifier is interpolated (F12) |
| CSRF | Web group has `ValidateCsrfToken`, no exempt paths; live `POST /trial-inquiries` without token → **419**. API is bearer-token (no cookies), so CSRF tokens would be ineffective there and were not added |
| Sensitive files | Live: `/.env`, `/.git/config`, `/composer.json`, `/vendor/autoload.php`, `/artisan` → 404; `/storage/logs/laravel.log` → 403 |
| Reflected/stored XSS | Blade `{{ }}` everywhere; only one `{!! !!}` (F16). One `innerHTML` with a constant string (`society-ui.js`) |
| Open redirect | No user-supplied redirect; `intended()` only |
| Passwords | bcrypt (12 rounds), `hashed` cast; random 20-char passwords for OTP/admin-created accounts |
| Login brute force | Web/society: 5 attempts per email+IP + route throttle; API: same + `throttle:10,1`; OTP: `throttle:10,1` |
| Error handling | Laravel hides traces when `APP_DEBUG=false` (now enforced in production); API errors use the `{success,message,errors}` envelope |

## 3. Remaining risks (not fixed)

| # | Risk | Level | Why it remains / recommendation |
|---|---|---|---|
| **R1** | **Fixed OTP code `0000`** (`config/flatcare.php`, `OtpService`). Anyone who knows a registered mobile number can sign in as that resident/guard. | **Critical** | No SMS provider is wired in; replacing it changes the login workflow, so it was not altered. **Connect MSG91/Twilio Verify to `OtpService::send()/verify()` before real users rely on the app** (per-number throttles from F7 are already in place). Until then the app logs a `critical` line in production. |
| R2 | Society documents and photos are stored on the public disk and reachable by anyone who has the (unguessable, 40-char) URL. | Medium | Moving to a private disk + signed/authorised download route changes the document URL contract used by the app; plan it as a follow-up. |
| R3 | The app uses the MySQL **root** user, and every tenant database record reuses the same DB user. | Medium | Create least-privilege users (checklist has the SQL). One DB user per society is better still. |
| R4 | No antivirus/malware scanning of uploads (ClamAV not installed). | Medium | Add `clamdscan` in `SafeUploadedFile` when the server can run ClamAV. |
| R5 | CSP allows `style-src 'unsafe-inline'` and `cdn.jsdelivr.net`/`code.jquery.com` for scripts. | Low | Vendor Bootstrap locally and use nonces/classes instead of inline `style=` to tighten further. |
| R6 | Password policy: min 8 (API) / 10 (admin) with no complexity or breached-password check. | Low | Add `Password::defaults()` (`mixedCase()->numbers()`), optionally `uncompromised()`. |
| R7 | Any active tenant user with a password can sign in to the society portal; access is decided only by the menu-role catalog. | Low | Add an explicit "portal roles" allow-list on `SocietyLoginRequest` if residents should never use it. |
| R8 | API tokens are fixed 90-day bearer tokens (no rotation). | Low | Shorten expiry / add refresh rotation. |
| R9 | Flutter packages behind latest (e.g. `flutter_secure_storage` 9.2.4 → 11.x, `share_plus` 10 → 13, `go_router` 14 → 17). `pub` has no advisory database; no major upgrade was made blindly. | Low | Upgrade in a dedicated release and regression-test. |
| R10 | `.htaccess` rules (F5) and Android manifest changes (F15) are unverified on a real server / build. | Low | Follow the deployment checklist. |

## 4. Files changed

Backend: `app/Http/Middleware/{SecurityHeaders,EnsureMenuItemVisible}.php`, `app/Rules/SafeUploadedFile.php`, `app/Support/{SvgSanitizer,SecurityLog}.php`, `app/Providers/AppServiceProvider.php`, `app/Services/{TenantService,Api/OtpService,Api/ApiTokenService}.php`, `app/Http/Controllers/Api/V1/Resident/{ProfileController,VisitorController}.php`, `app/Http/Requests/{Auth,Api/V1/Auth}/*LoginRequest.php`+`OtpVerifyRequest.php`, `bootstrap/app.php`, `routes/api.php`, `config/{session,logging,adminlte}.php`, `public/.htaccess`.
Front end: 24 Blade confirm conversions, `welcome.blade.php`, `society/layout.blade.php`, `admin/settings/branding.blade.php`, `public/js/{security-ui,admin-branding,welcome-trial-modal,society-ui}.js`.
Mobile: `AndroidManifest.xml` (main + debug).
Tests/docs: `tests/Feature/SecurityHardeningTest.php` (13 tests), this report, `SECURITY_CHECKLIST.md`.

Test status: `php artisan test` → **15 passed** (13 new). `composer audit` / `npm audit` → 0 advisories.
