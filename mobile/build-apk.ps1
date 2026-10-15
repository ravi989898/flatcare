# The one way to build the Android app for download from the website.
#
#   cd mobile
#   powershell -ExecutionPolicy Bypass -File build-apk.ps1
#
# Bump `version:` in pubspec.yaml first. This builds the arm64 release APK
# against the live API, checks it is signed with the FlatCare signing key,
# and puts it at public/downloads/flatcare-app.apk. Commit that file and
# push to both remotes (origin and upstream); a `git pull` on the server then
# serves it at https://flatcare.in/app/download.
#
# Signing: from 1.0.10 the APK is signed with the FlatCare release key,
# mobile/android/app/upload-keystore.jks + mobile/android/key.properties (both
# git-ignored) - the same key Google Play signs the store version with, so
# the website APK and the Play Store version update each other. (Up to 1.0.9
# it was this PC's debug.keystore; those installs must be uninstalled once.)
# Android only installs an update signed with the same key as the installed
# app, so both files must be backed up and copied to any other PC that builds
# the app. The build stops here instead of shipping an APK signed with
# anything else. build-aab.ps1 builds the Play Store bundle.

$ErrorActionPreference = 'Stop'

$ExpectedCert = '52c39dd80a273ab16616dd73d0bdc8319d3565a93a8ac8cf269def3bca0b51a3'
$ApiBaseUrl = 'https://flatcare.in/api/v1'

$mobile = $PSScriptRoot
$output = Join-Path $mobile 'build\app\outputs\flutter-apk\app-release.apk'
$target = Join-Path $mobile '..\public\downloads\flatcare-app.apk'

$version = (Select-String -Path (Join-Path $mobile 'pubspec.yaml') -Pattern '^version:\s*(\S+)').Matches[0].Groups[1].Value
Write-Host "Building FlatCare $version ..."

Push-Location $mobile
try {
    # No --split-per-abi: it adds 2000 to the arm64 versionCode, which would
    # put the website APK ahead of the Play Store bundle (plain versionCode)
    # and stop Play from ever updating it. --target-platform alone still
    # gives a small arm64-only APK with the pubspec build number as is.
    flutter build apk --release --target-platform android-arm64 "--dart-define=API_BASE_URL=$ApiBaseUrl"
    if ($LASTEXITCODE -ne 0) { throw 'flutter build failed.' }
} finally {
    Pop-Location
}

# apksigner lives in the newest Android SDK build-tools, and needs the JDK
# Flutter builds with (`flutter config --jdk-dir`) when Java isn't on PATH.
$config = flutter config --machine | Out-String | ConvertFrom-Json
if (-not $env:JAVA_HOME -and $config.'jdk-dir') { $env:JAVA_HOME = $config.'jdk-dir' }
$sdk = (Select-String -Path (Join-Path $mobile 'android\local.properties') -Pattern '^sdk\.dir=(.+)$').Matches[0].Groups[1].Value -replace '\\\\', '\'
$apksigner = Get-ChildItem (Join-Path $sdk 'build-tools\*\apksigner.bat') | Sort-Object FullName | Select-Object -Last 1
if (-not $apksigner) { throw "apksigner not found under $sdk\build-tools." }

$certs = & $apksigner.FullName verify --print-certs $output
if ($LASTEXITCODE -ne 0) { throw 'The APK signature does not verify.' }
if (-not ($certs -match "SHA-256 digest: $ExpectedCert")) {
    throw "The APK is NOT signed with the FlatCare key, so it cannot update the installed app. Restore android/app/upload-keystore.jks and android/key.properties (see the top of this script). Signer: $($certs -join ' | ')"
}

Copy-Item $output $target -Force
Write-Host ''
Write-Host "FlatCare $version is ready at public/downloads/flatcare-app.apk"
Write-Host 'Next: git add + commit it, then git push upstream main; git push origin main'
