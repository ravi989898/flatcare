@echo off
REM Runs FlatCare with PHP 8.2 (Laravel 12 requires 8.2+), regardless of
REM whichever PHP version is set as default in your system PATH / WAMP tray.
"D:\wamp64\bin\php\php8.2.30\php.exe" "%~dp0artisan" serve
