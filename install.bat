@echo off
setlocal
cd /d "%~dp0"

where php >nul 2>nul || (echo Fehler: PHP wurde nicht gefunden. Bitte PHP 8.3 oder neuer installieren. & exit /b 1)
where composer >nul 2>nul || (echo Fehler: Composer wurde nicht gefunden ^(https://getcomposer.org^). & exit /b 1)

call composer install --no-interaction --prefer-dist || exit /b 1
if not exist .env copy .env.example .env >nul

php artisan marketplace:install %*
