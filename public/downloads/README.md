# public/downloads/

Static files the landing page links to directly.

## flatcare-app.apk

The resident Android app. **Not committed** (`.gitignore` keeps only this
folder, not its APK). Upload the file here by FTP / cPanel File Manager on
the production server:

    public/downloads/flatcare-app.apk

The "Download App" buttons on the landing page fall back to
`asset('downloads/flatcare-app.apk')` whenever `MOBILE_APK_URL` is unset in
`.env`, so once the file is in place the download just works — no deploy.

Build a fresh APK with:

    cd mobile
    flutter build apk --release --dart-define=API_BASE_URL=http://flatcare.dineflowpro.com/api/v1
    # -> build/app/outputs/flutter-apk/app-release.apk  (rename to flatcare-app.apk)

To shrink it (~53 MB universal -> ~19 MB), add `--split-per-abi` and upload
`app-arm64-v8a-release.apk` instead (covers every phone since ~2019).
