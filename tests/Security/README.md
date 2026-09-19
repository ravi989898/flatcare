# Multi-society security test

`multi_society_*` verify that a Society A user can never read or change Society B data
(mandatory isolation test, see SECURITY_AUDIT.md §Multi-society).

```
php tests/Security/multi_society_setup.php     # creates a throw-away "SecTest Society B" + sectest accounts
php artisan serve                              # in another terminal (PHP 8.2)
python tests/Security/multi_society_test.py    # ~60 checks: API (resident/guard), web (admins), files, tokens
php tests/Security/multi_society_cleanup.php   # removes every sectest row and drops Society B's database
```

Run it against a **local/staging** database only. It uses the fixed OTP code (`OTP_DEFAULT_CODE`, default `0000`)
to sign in the sectest residents/guards, so it stops working once a real SMS gateway replaces the stub -
switch `otp_login()` to a test-only token in that case. It only ever touches rows carrying a
`sectest` / `SECTEST` marker.
