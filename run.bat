@echo off
title Vishal Web Studio - Web Platform
echo ========================================================
echo  Vishal Web Studio - Multi-Client Website Platform
echo ========================================================
echo.

set PHP_PATH=C:\xampp\php\php.exe
if not exist "%PHP_PATH%" (
    where php >nul 2>nul
    if %errorlevel% equ 0 (
        set PHP_PATH=php
    ) else (
        echo [ERROR] PHP executable not found at C:\xampp\php\php.exe or in PATH.
        echo Please install XAMPP or add PHP to your PATH.
        pause
        exit /b 1
    )
)

echo [*] Initializing Database & Seed Data...
"%PHP_PATH%" "%~dp0database\installer.php"

echo.
echo [*] Starting PHP Built-in Web Server at http://localhost:8000 ...
echo [i] Open your browser and navigate to: http://localhost:8000
echo.
echo [i] Default Super Admin: admin@vishalwebstudio.com / admin123
echo [i] Default Demo Client: client@sharmarestaurant.com / client123
echo.
echo Press Ctrl+C to stop the server anytime.
echo.

"%PHP_PATH%" -S localhost:8000 -t "%~dp0" "%~dp0router.php"
pause
