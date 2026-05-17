@echo off
echo ========================================
echo EMERGENCY LOGIN FIX SCRIPT
echo ========================================
echo.

cd /d "%~dp0"

echo [1/5] Checking storage directories...
if not exist "storage\framework\sessions" (
    echo Creating sessions directory...
    mkdir "storage\framework\sessions"
)
if not exist "storage\framework\cache" (
    echo Creating cache directory...
    mkdir "storage\framework\cache"
)
if not exist "storage\framework\views" (
    echo Creating views directory...
    mkdir "storage\framework\views"
)
if not exist "storage\logs" (
    echo Creating logs directory...
    mkdir "storage\logs"
)
echo Storage directories checked/created.
echo.

echo [2/5] Clearing all caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo Caches cleared.
echo.

echo [3/5] Checking APP_KEY...
php artisan key:generate --show
echo.

echo [4/5] Testing session functionality...
echo Visit: http://localhost/test-session-set
echo Then: http://localhost/test-session-check
echo.

echo [5/5] Fix complete!
echo.
echo ========================================
echo NEXT STEPS:
echo ========================================
echo 1. Visit /test-session-set in your browser
echo 2. Then visit /test-session-check
echo 3. If session works, try logging in
echo 4. Check .env file has these settings:
echo    SESSION_DRIVER=file
echo    SESSION_SECURE_COOKIE=false
echo    SESSION_DOMAIN=
echo    SESSION_ENCRYPT=false
echo ========================================
pause
