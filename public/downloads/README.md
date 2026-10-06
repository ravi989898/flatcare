# public/downloads/

## flatcare-app.apk — the Android app

This is the **only** copy of the Android app that is published. It is
committed to the repo, so a `git pull` on the server releases it.

People download it from **https://flatcare.in/app/download**, the one
download link. It is served by `App\Http\Controllers\AppDownloadController`
as `FlatCare-<version>.apk` and is never cached. The landing page button,
the SEO data and old links to `/downloads/flatcare-app.apk` (redirected in
`public/.htaccess`) all lead there.

### Releasing a new version

1. Bump `version:` in `mobile/pubspec.yaml` (e.g. `1.0.12+13`).
2. Build:

       cd mobile
       powershell -ExecutionPolicy Bypass -File build-apk.ps1

   It builds the arm64 release APK against `https://flatcare.in/api/v1`,
   checks it is signed with the FlatCare key and copies it here.
3. Commit `public/downloads/flatcare-app.apk` with the change, then push to
   **both** remotes: `git push upstream main` and `git push origin main`.
4. On the server: `git pull`.

### Signing key — do not lose it

Every APK shipped so far is signed with `%USERPROFILE%\.android\debug.keystore`
on the build PC (SHA-256 `c984648199edb04dac6cb130f6b2a010e1cfe1fb7cb90df5a84d3c65b2743cd9`).
Android installs an update only when it is signed with the same key as the
installed app. Keep a backup of that file, and copy it to any other PC that
builds the app. `build-apk.ps1` refuses to publish an APK signed with any
other key.
