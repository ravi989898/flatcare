# public/downloads/

Static files the landing page links to directly.

## flatcare-app.apk

The resident Android app. **Not committed** (`.gitignore` keeps only this
folder, not its APK). Upload the file here by FTP / cPanel File Manager on
the production server:

    public/downloads/flatcare-app.apk

The "Download App" buttons on the landing page fall back to the root-relative
`/downloads/flatcare-app.apk` whenever `MOBILE_APK_URL` is unset in `.env`, so
once the file is in place the download just works — no deploy.

Build a fresh APK with:

    cd mobile
    flutter build apk --release --split-per-abi --dart-define=API_BASE_URL=https://flatcare.in/api/v1
    # -> build/app/outputs/flutter-apk/app-arm64-v8a-release.apk  (rename to flatcare-app.apk)

`--split-per-abi` shrinks it from a ~53 MB universal APK to ~19 MB;
`app-arm64-v8a-release.apk` covers every phone since ~2019, so that's the one
to upload.
