# Builds the Android App Bundle (.aab) to upload to Google Play.
#
#   cd mobile
#   powershell -ExecutionPolicy Bypass -File build-aab.ps1
#
# Bump `version:` in pubspec.yaml first - Play rejects a build number it has
# already seen. Use the same version for build-apk.ps1 so the website APK and
# the Play Store version stay in step. This builds the bundle against the
# live API, checks it is signed with the FlatCare release key (see the top of
# build-apk.ps1), and copies it to _release/flatcare-<version>.aab, which is
# git-ignored: upload that file in Play Console, don't commit it.

$ErrorActionPreference = 'Stop'

$ExpectedCert = '52:C3:9D:D8:0A:27:3A:B1:66:16:DD:73:D0:BD:C8:31:9D:35:65:A9:3A:8A:C8:CF:26:9D:EF:3B:CA:0B:51:A3'
$ApiBaseUrl = 'https://flatcare.in/api/v1'

$mobile = $PSScriptRoot
$output = Join-Path $mobile 'build\app\outputs\bundle\release\app-release.aab'

$version = (Select-String -Path (Join-Path $mobile 'pubspec.yaml') -Pattern '^version:\s*(\S+)').Matches[0].Groups[1].Value
Write-Host "Building FlatCare $version (Play Store bundle) ..."

Push-Location $mobile
try {
    flutter build appbundle --release "--dart-define=API_BASE_URL=$ApiBaseUrl"
    if ($LASTEXITCODE -ne 0) { throw 'flutter build failed.' }
} finally {
    Pop-Location
}

# keytool ships with the JDK Flutter builds with (`flutter config --jdk-dir`).
$config = flutter config --machine | Out-String | ConvertFrom-Json
$jdk = if ($env:JAVA_HOME) { $env:JAVA_HOME } else { $config.'jdk-dir' }
$keytool = Join-Path $jdk 'bin\keytool.exe'
if (-not (Test-Path $keytool)) { throw "keytool not found under $jdk." }

$certs = & $keytool -printcert -jarfile $output | Out-String
if (-not $certs.Contains("SHA256: $ExpectedCert")) {
    throw "The bundle is NOT signed with the FlatCare release key. Restore android/app/upload-keystore.jks and android/key.properties (see build-apk.ps1). Signer: $certs"
}

$releaseDir = Join-Path $mobile '..\_release'
New-Item -ItemType Directory -Force $releaseDir | Out-Null
$target = Join-Path $releaseDir "flatcare-$($version -replace '\+', '-build').aab"
Copy-Item $output $target -Force
Write-Host ''
Write-Host "FlatCare $version is ready for Play Console at $((Resolve-Path $target).Path)"
