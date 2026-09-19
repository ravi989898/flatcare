# FlatCare – Backend / API Security Audit

Date: 2026-09-19 · Second, backend-focused round. The first round (web front end, CSP, uploads, headers) is in [SECURITY_REPORT.md](SECURITY_REPORT.md); its fixes are kept and summarised in §6. Checklist: [SECURITY_CHECKLIST.md](SECURITY_CHECKLIST.md).

**This is not a claim that the backend is "100% secure".** One CRITICAL risk (fixed OTP code, R1) is still open because it needs an SMS gateway.

Severity scale: CRITICAL · HIGH · MEDIUM · LOW · INFO.

## 1. Architecture (inspected before any change)

| Item | Finding |
|---|---|
| Framework | Laravel 12, PHP 8.2 (bcrypt 12 rounds), MySQL, Blade + AdminLTE, Flutter client (`mobile/`) |
| Tenancy | **Database-per-society.** Main DB `flatcare_main` (societies, api_tokens, roles/menu catalog, audit log) + one MySQL database per society (`society_{id}_{slug}`); `TenantService::setTenant()` points the `society` connection at the caller's database on every request. There is no `society_id` column to filter on – isolation is the connection itself |
| API | `/api/v1/*` (routes/api.php): public = login, forgot/reset password, OTP request/verify; everything else behind `api.auth` (+ `throttle:api-auth`); gate-security endpoints also behind `role:security` |
| Web | Super-admin panel `/admin/*` (`auth`,`verified`,`admin`), society portal `/society/*` (`society.context`,`menu.visible`) – session + CSRF |
| Auth | Bearer token (64 random chars, SHA-256 in `api_tokens`, 90-day expiry, society + user resolved **from the token row**), OTP/e-mail login, per-email+IP lockout |
| Authorization | Roles per tenant (`super_admin, admin, committee_member, resident, security`), role → menu catalog (`role_menu_item`), route middleware, `myFlatIds()` for resident data |
| Models / DTOs | 35 tenant models (`$fillable` allow-lists, no `$guarded=[]`), API Resources for every response |
| Uploads | 6 endpoints, `SafeUploadedFile`, public disk (`storage/app/public` → `public/storage`) |
| Background | **No queue jobs, no scheduler entries, no webhooks, no outbound HTTP** except the Razorpay SDK (fixed host). Only artisan command: `tenants:migrate` (CLI) |
| Third party | Razorpay (payments: signature verify + server-side payment fetch + amount check + row lock), SMTP/log mail, SMS = stub (no gateway) |
| Config | `.env` untracked, `APP_KEY`, DB credentials of tenants stored `encrypt()`-ed in `society_databases` |

## 2. Vulnerabilities found and fixed in this round

### V1 – HIGH – A plain resident could use the society-admin portal and promote themselves to admin
- **Endpoint/file**: `POST /society/login`, every `/society/*` route; `SetSocietyContext`, `EnsureMenuItemVisible`, `RoleMenuSettingSeeder`
- **Root cause**: `SocietyLoginRequest` signs in *any* active tenant user. The default menu seed gave the `resident` role **every** menu item (`'resident' => $allKeys`), so a resident (who can set a password through the app's "forgot password") got Admins, Blocks, Security, Payments, Extra Charges… A resident could create admin users, edit guards/blocks, post announcements/documents.
- **Reproduced before the fix**: resident with a password → login 302; `/society/admins`, `/society/admins/create`, `/society/blocks`, `/society/security`, `/society/extra-charges`, `/society/payments` → **200**; `POST /society/admins` → 302.
- **Fix**: (1) `SetSocietyContext` now refuses a user whose highest role is not a staff role (`PORTAL_ROLES` allow-list; `resident`/no role → logged out, message "use the mobile app", `authz.portal_role_denied` logged). (2) `EnsureMenuItemVisible::ADMIN_ONLY_KEYS` (`admins, blocks, security, payments, extra-charges, water-readings`) are admin-only **regardless of the menu catalog**. (3) Seeder default for `resident` no longer lists every module.
- **Test**: same resident → every portal URL → **302 to login**; staff (admin A/B) unaffected (see §4).
- **Result**: PASS.
- **Remaining**: committee/security roles still see what the catalog gives them (as designed).

### V2 – HIGH – Society admin could grant the platform `super_admin` role by posting its id
- **Endpoint/file**: `POST /society/admins`, `PUT /society/admins/{id}` – `StoreAdminUserRequest`, `AdminUserRequest`
- **Root cause**: `role` was validated only with `exists:society.roles,id`; the form hides `super_admin` but the server accepted it (vertical privilege escalation).
- **Fix**: `Rule::exists('society.roles','id')->where(name != 'super_admin')` in both requests.
- **Test**: live POST with the super_admin role id → no `role_user` row created.
- **Result**: PASS.

### V3 – MEDIUM – CORS answered `Access-Control-Allow-Origin: *` on the API
- **Endpoint/file**: `/api/*`; no `config/cors.php` (framework default = allow all)
- **Root cause**: default CORS config with wildcard origin.
- **Fix**: new `config/cors.php` – allow-list from `CORS_ALLOWED_ORIGINS` (empty by default), `localhost`/`127.0.0.1` allowed only when `APP_ENV=local`, no credentials, explicit methods/headers. The native app does not use CORS.
- **Test**: PHPUnit ×2 + live: `Origin: https://evil.example` → **no** ACAO header; `http://localhost:5000` → allowed (dev only).
- **Result**: PASS. **Remaining**: set `CORS_ALLOWED_ORIGINS` if you ever serve a web client from another origin.

### V4 – MEDIUM – Login brute-force limit bypassed by rotating the source IP
- **Endpoint/file**: `/api/v1/auth/login`, `/api/v1/auth/otp/verify`, `/login`, `/society/login`
- **Root cause**: lockout key was `email|IP` and the route throttle is per IP; with `trustProxies('*')` a client can send a fresh `X-Forwarded-For` per attempt and never be limited.
- **Reproduced**: 26 wrong passwords for one account with 26 spoofed IPs → all answered normally.
- **Fix**: additional per-account counter (no IP): 20 failures / 15 min in all four flows (cleared on success); `TRUSTED_PROXIES` env to stop honouring spoofed proxy headers.
- **Test**: same 26-request run → locked out after 20 ("try again in 810 seconds"), even from new IPs.
- **Result**: PASS. **Remaining**: the per-*route* throttles are still IP based until `TRUSTED_PROXIES` is set in production; an attacker can temporarily lock a victim's login for ≤15 min (accepted trade-off).

### V5 – MEDIUM – Response-time difference revealed which e-mails exist
- **Endpoint/file**: `POST /api/v1/auth/login` (`TenantAccountLocator::findByCredentials`)
- **Root cause**: an unknown e-mail returned without a bcrypt check, a known one after ~100 ms of hashing.
- **Fix**: unknown e-mail burns one verification against a precomputed cost-12 dummy hash. (First attempt re-hashed per request and made unknown *slower*; fixed by using a constant hash.)
- **Test**: 4 pairs, unknown vs known e-mail: 0.32–0.40 s vs 0.36–0.36 s.
- **Result**: PASS. Error text was already identical for both cases.

### V6 – MEDIUM – No server-side neutralisation of HTML/JS in stored text
- **Endpoint/file**: every write endpoint (visitor name/notes/purpose, complaints, announcements, profile, addresses …)
- **Root cause**: safety relied only on output escaping (Blade `{{ }}`, Flutter `Text`). A raw payload was stored and returned to any client.
- **Fix**: global `SanitizeHtmlInput` middleware (JSON, form and query string): removes real HTML tags, script/style/iframe/object/embed/svg blocks, comments and NUL bytes, blanks values that are only a `javascript:/data:/vbscript:` URI. Conservative: `5 < 6`, `I <3 you`, `x<y` survive; keys matching `pass|token|otp|secret|signature|_key` are untouched. No view renders user data as HTML, so no rich-text feature is affected.
- **Test**: 15 payload PHPUnit cases + legitimate-text case + middleware cases; live `POST /visitors` with `<img onerror=…>…<script>` name and `<b onmouseover=…>` notes → stored `'RLTEST Rahul'` / `'hello'`.
- **Result**: PASS. **Remaining**: output escaping stays the primary defence; rows stored before this fix are not rewritten.

### V7 – MEDIUM – Abuse-prone writes had no dedicated rate limits
- **Endpoint/file**: `POST /visitors`, `POST /daily-helpers` (photo upload), `POST /guard/visitors`, `PUT /profile/password`, admin/society panels
- **Fix**: named limiters keyed by token/session+IP (not by request parameters): `visitor-create` 30/h, `guard-visitor-create` 240/h, `uploads` 30/h, `password-change` 5/min, `panel` 300/min; existing: login 10/min, forgot/reset 5/min, OTP 5/min/IP + 10/h/number, authenticated API 120/min.
- **Test**: PHPUnit route-middleware assertions (8 routes) + live: 32 visitor creations → 29×201 then **429**.
- **Result**: PASS.

### V8 – LOW – Privilege changes were not logged; deactivated/reset users kept working on the app
- **Endpoint/file**: `Society\AdminController` (grant role, change role, activate, deactivate, reset password)
- **Fix**: `SecurityLog` entries (`admin.role_granted/changed/activated/deactivated/password_reset`, actor + target ids, never passwords); deactivate and password reset now revoke the target's API tokens.
- **Test**: PHP lint + code review only – the admin actions and their log lines were **not** exercised end-to-end (the `SecurityLog` pipeline itself is verified: failed logins, lockouts, 429s and blocked uploads all wrote entries).
- **Result**: implemented, partially verified.

## 3. Areas audited and found sound (no change)

| Requirement | Result |
|---|---|
| §1 Passwords | bcrypt(12) via `hashed`/`Hash::make`; random 20-char passwords for OTP/directory accounts; no password/token/hash in any API Resource (`UserResource`, `SocietyResource` etc. reviewed) |
| §1 Tokens | SHA-256 hashed at rest, 90-day expiry, deleted on logout and on reset/change/deactivate; `tampered token → 401`, `no token → 401`, `logged-out token → 401` (tested) |
| §1/§10 Trusting client identity | `society_id`, `user_id`, `role`, `status`, `email`, `password` sent by a client are ignored: `PUT /profile` with those fields changed only `name`; `POST /visitors` with `status`, `invited_by_user_id`, `society_id` stored server values (tested) |
| §2 IDOR/BOLA | Resident data always `whereIn('flat_id', myFlatIds())` / `user_id`; write endpoints re-check flat ownership (visitors, helpers, complaints, settings); payments resolve the bill by the caller's flats and verify the amount server-side |
| §4 SQL injection | 8 raw-SQL fragments in `app/`, all constants or bound parameters; every search/filter is bound; **no user-controlled `ORDER BY`/`LIMIT`**; the only interpolated identifier (DB name in DDL) is regex-validated. Live: `search=' OR 1=1--` → 200 (literal), invalid `to`/`status` → 422 |
| §5 Validation | FormRequest on every write; query filters on the visitor list now validated (was a 500) |
| §7/§8 Uploads, traversal | See §6; no endpoint takes a filesystem path (paths come from DB rows); `/storage/../.env` and `%2e%2e` → not served |
| §11 CSRF | Web group has `ValidateCsrfToken`, no exempt paths (POST without token → **419**, tested); API is bearer-only, no cookie auth → CSRF tokens would be ineffective and were not added |
| §15 Errors | `APP_DEBUG` forced off in production; API errors use the safe `{success,message,errors}` envelope |
| §16 Command injection | No `exec/system/shell_exec/passthru/popen/proc_open/eval/unserialize` in `app/` |
| §17 SSRF | No feature fetches a user-supplied URL (no image/document import, no URL preview, no webhook dispatch); only fixed Razorpay host |
| §18 Webhooks | None exist. Payment confirmation is client-initiated but verified: Razorpay signature via SDK, payment re-fetched from Razorpay (`captured`, order id, amount = our amount), DB transaction + `lockForUpdate` |
| §19 Sensitive data | Directory masks other residents' phone/e-mail (`DirectoryResource::mask`); own data unmasked; no PII beyond what each screen needs |
| §22 Jobs/cron | None; nothing to trigger remotely |
| §23 Third parties | Razorpay secret only server-side (`config/services.php` ← `.env`); the app receives only the public key id; SDK timeouts/exceptions are mapped to generic messages |

## 4. Mandatory multi-society test (§27)

Fixtures: Society A = the demo society; **Society B created for the test** (own database, provisioned through `TenantService`, ids deliberately overlapping A's). Accounts: A – admin, resident, security; B – admin, resident, security (`tests/Security/multi_society_setup.php`; residents/guards signed in through the real OTP flow, admins through the real portal login). Harness: `tests/Security/multi_society_test.py` – **59 checks, 59 passed, 0 failed** (re-run after every fix; test data removed afterwards with `multi_society_cleanup.php`, Society B's database dropped).

| Attempt by a Society A user | Result |
|---|---|
| List B's visitors / helpers / complaints / residents (directory) / flats / guards | none returned (symmetric for B→A) |
| GET / DELETE / approve / reject a B-only visitor id; DELETE a B-only helper; GET a B-only complaint | **404**; B row still present |
| Create a visitor / helper / complaint for a B-only flat id | **422/403**, nothing written to B |
| Change B's visitor-settings (send B's flat id) | B unchanged; the call only affected A's own flat |
| Forge `society_id` / `tenant_society_id` in body, query string and `X-Society-Id` headers | ignored – record created in A only, list still A-only |
| Guard A: list B visitors, B flats; check-in / check-out / GET a B-only visitor; register a visitor for a B flat | none / **404** / rejected |
| Resident token on `/guard/*` | **403** |
| Guard token on resident endpoints | empty result |
| Tampered / missing / logged-out token | **401** |
| Mass assignment (`role`, `society_id`, `is_admin`, `status`, `email`, `password`) via `PUT /profile` | fields ignored, roles/status/email unchanged |
| Admin A portal: blocks, visitors, directory, block/flat edit, flats of a B block, DELETE/PUT a B block | A-only content; B ids → **404**; B rows untouched; `?society_id=2` ignored |
| Admin A: assign `super_admin`; open `/admin/*` (platform panel) | role not granted; redirected |
| Anonymous: `/society/*`, `/admin/*` | redirect to login |
| Files: `/storage/visitors/` listing, traversal to `.env` | not served |
| Resident with a password → society portal (V1) | bounced to login |

**Honest gap – files (§27 "access Society B files")**: uploads live on the shared public disk with 40-character random names. Society A can neither list nor discover B's files (no API returns another society's URL; directory listing is off), but anyone who *is given* a B file URL can open it – the URL is a capability, not an access-checked resource (R2).

## 5. Tests run

| Suite | Result |
|---|---|
| `php artisan test` (existing 2 + `SecurityHardeningTest` 13 + `BackendSecurityTest` 23) | **38 passed, 0 failed** (94 assertions) |
| `tests/Security/multi_society_test.py` (live, two societies) | **59 passed, 0 failed** |
| Live probes (curl) | headers, CSRF 419, sensitive files 404/403, IDOR, upload attacks (php/polyglot/html → 422), SQLi-looking input, CORS, brute force with spoofed IPs, timing, stored XSS, rate limit 429 |
| `npm audit --omit=dev` | 0 vulnerabilities |
| `composer audit` | clean in round 1; **today's re-run failed: packagist returned HTTP 502** – re-run in CI |
| `php -l` on every changed PHP file; all Blade views compile (`view:cache`) | OK |

Security tests added this round: `tests/Feature/BackendSecurityTest.php` (input sanitisation ×15+, middleware, CORS ×2, every API route requires a token, guard routes require `role:security`, rate-limit wiring) and the multi-society harness.

## 6. Carried over from round 1 (still in place)
SVG XXE/script stripping · upload validation (sniffed MIME, image decode/size caps, whole-file script scan, blocked-upload logging) · `.htaccess` blocking scripts/HTML/dot-files under `/storage` · CSP without inline scripts + SRI for CDN assets · inline `confirm()` replaced by `data-confirm` · fail-closed menu authorization · rate limits on the authenticated API and OTP per number · tokens revoked on password change · OTP code never logged outside local dev · secure/encrypted session defaults · security headers incl. API `no-store` · dedicated `security` log channel · Android release without backup/cleartext.

## 7. Remaining risks

| # | Risk | Severity | Note |
|---|---|---|---|
| R1 | **Fixed OTP `0000`** – anyone knowing a registered mobile number can sign in as that resident/guard | **CRITICAL** | Needs an SMS provider in `OtpService`. A `critical` security-log line is written in production while it is unchanged. Not altered: it would change the login workflow |
| R2 | Uploaded files (photos, documents) are on a shared public disk; URLs are unguessable but not tenant-checked | MEDIUM | Move to a private disk and serve through an authorised/signed route (changes the URL contract used by the app) |
| R3 | App connects to MySQL as `root`; all tenant DB records reuse that user | MEDIUM | Least-privilege user (SQL in the checklist); ideally one DB user per society |
| R4 | No malware scanning of uploads | MEDIUM | ClamAV integration |
| R5 | `TRUSTED_PROXIES` defaults to `*` → per-IP route throttles are spoofable until set on the server | MEDIUM (config) | Per-account lockout (V4) limits the damage |
| R6 | `POST otp/request` reveals whether a number is registered (documented product decision) | LOW | Return a neutral message once a real SMS gateway exists |
| R7 | Same e-mail/mobile in two societies → first active society wins at login | LOW | Enforce global uniqueness or require a society code |
| R8 | 90-day non-rotating bearer tokens, no refresh-token flow | LOW | Shorter expiry + rotation |
| R9 | Password policy min 8/10, no breached-password check | LOW | `Password::defaults()` |
| R10 | CSP still allows `style-src 'unsafe-inline'` and the jsDelivr host | LOW | Vendor Bootstrap, remove inline styles |
| R11 | Flutter packages behind latest (no advisory DB for pub) | LOW | Dedicated upgrade release |
| R12 | `.htaccess` (uploads protection) and Android manifest changes not verified on real Apache / release build | LOW | See deployment checklist |

## 8. Files changed in this round
`app/Http/Middleware/{SanitizeHtmlInput (new),SetSocietyContext,EnsureMenuItemVisible}.php` · `app/Http/Requests/Society/{StoreAdminUserRequest,AdminUserRequest}.php` · `app/Http/Requests/{Auth/LoginRequest,Auth/SocietyLoginRequest,Api/V1/Auth/LoginRequest,Api/V1/Auth/OtpVerifyRequest}.php` · `app/Http/Controllers/Society/AdminController.php` · `app/Services/Api/TenantAccountLocator.php` · `app/Providers/AppServiceProvider.php` · `bootstrap/app.php` · `routes/{api,web}.php` · `config/cors.php` (new) · `database/seeders/RoleMenuSettingSeeder.php` · `tests/Feature/BackendSecurityTest.php` (new) · `tests/Security/*` (new).

Local environment side effects (dev DB only): ran the pending main-DB migrations from the earlier `git pull` (needed for the portal to render at all); Society B and all `sectest` rows were created and removed again.
