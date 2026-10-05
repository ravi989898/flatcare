# public/downloads/

Static files the landing page links to directly.

## flatcare-app.apk

The resident Android app. **Committed to the repo**, so a `git pull` on the
production server puts the new version in place:

    public/downloads/flatcare-app.apk

The "Download App" buttons on the landing page fall back to the root-relative
`/downloads/flatcare-app.apk` whenever `MOBILE_APK_URL` is unset in `.env`, so
once the file is pulled the download just works — no other deploy step.

Build a fresh APK with:

    cd mobile
    flutter build apk --release --split-per-abi --dart-define=API_BASE_URL=https://flatcare.in/api/v1
    # -> build/app/outputs/flutter-apk/app-arm64-v8a-release.apk  (rename to flatcare-app.apk)

`--split-per-abi` shrinks it from a ~53 MB universal APK to ~19 MB;
`app-arm64-v8a-release.apk` covers every phone since ~2019, so that's the one
to commit. Leave out `--dart-define` and the app talks to the emulator host
(10.0.2.2) instead of the server.
