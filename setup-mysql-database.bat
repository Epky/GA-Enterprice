@echo off
echo ========================================
echo   MySQL Database Setup for Beauty Store
echo ========================================
echo.

REM Set MySQL path
set MYSQL_PATH=C:\xampp\mysql\bin

echo Step 1: Starting XAMPP MySQL...
echo Please make sure XAMPP Control Panel shows MySQL as running
echo.
pause

echo.
echo Step 2: Creating database...
echo.

REM Create database
"%MYSQL_PATH%\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS beauty_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Database 'beauty_store' created successfully!
) else (
    echo [ERROR] Failed to create database. Make sure MySQL is running in XAMPP.
    pause
    exit /b 1
)

echo.
echo Step 3: Verifying database...
"%MYSQL_PATH%\mysql.exe" -u root -e "SHOW DATABASES LIKE 'beauty_store';"

echo.
echo ========================================
echo   Database Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Run: php artisan config:clear
echo 2. Run: php artisan migrate:fresh
echo 3. Run: php artisan db:seed
echo 4. Run: php artisan serve
echo.
pause
