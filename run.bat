@echo off
setlocal enabledelayedexpansion
title Smart Expense Tracker Launcher (XAMPP / WAMP / Laragon / System)
color 0A

echo ======================================================================
echo           Smart Expense Tracker - Multi-Stack 1-Click Launcher
echo                     (XAMPP / WAMP / Laragon / CLI)
echo ======================================================================
echo.

set "PHP_BIN="
set "MYSQLD_BIN="
set "STACK_NAME=Unknown"

:: ----------------------------------------------------------------------
:: STEP 1: Locate PHP Executable
:: ----------------------------------------------------------------------
echo [1/3] Detecting PHP Environment...

:: Check System PATH
where php >nul 2>nul
if %ERRORLEVEL% equ 0 (
    for /f "delims=" %%i in ('where php') do set "PHP_BIN=%%i"
    set "STACK_NAME=System PATH (CLI)"
    goto PHP_FOUND
)

:: Check XAMPP
if exist "C:\xampp\php\php.exe" (
    set "PHP_BIN=C:\xampp\php\php.exe"
    set "STACK_NAME=XAMPP"
    goto PHP_FOUND
)

:: Check Laragon
if exist "C:\laragon\bin\php" (
    for /d %%d in ("C:\laragon\bin\php\php*") do (
        if exist "%%d\php.exe" (
            set "PHP_BIN=%%d\php.exe"
            set "STACK_NAME=Laragon"
            goto PHP_FOUND
        )
    )
)

:: Check WAMP64
if exist "C:\wamp64\bin\php" (
    for /d %%d in ("C:\wamp64\bin\php\php*") do (
        if exist "%%d\php.exe" (
            set "PHP_BIN=%%d\php.exe"
            set "STACK_NAME=WampServer64"
            goto PHP_FOUND
        )
    )
)

:: Check WAMP32
if exist "C:\wamp\bin\php" (
    for /d %%d in ("C:\wamp\bin\php\php*") do (
        if exist "%%d\php.exe" (
            set "PHP_BIN=%%d\php.exe"
            set "STACK_NAME=WampServer"
            goto PHP_FOUND
        )
    )
)

:PHP_FOUND
if "%PHP_BIN%"=="" (
    echo [ERROR] PHP executable was not found!
    echo Please install XAMPP, WAMP, or Laragon, or add PHP to your System PATH.
    pause
    exit /b 1
)

echo        Found PHP via %STACK_NAME%: "%PHP_BIN%"

:: ----------------------------------------------------------------------
:: STEP 2: Detect & Start MySQL / MariaDB Server
:: ----------------------------------------------------------------------
echo [2/3] Checking MySQL / MariaDB Server Status...

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo        MySQL Server is already running.
    goto MYSQL_DONE
)

tasklist /FI "IMAGENAME eq mariadbd.exe" 2>NUL | find /I /N "mariadbd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo        MariaDB Server is already running.
    goto MYSQL_DONE
)

:: Search for MySQL Daemon to start
if exist "C:\xampp\mysql\bin\mysqld.exe" (
    set "MYSQLD_BIN=C:\xampp\mysql\bin\mysqld.exe"
) else if exist "C:\laragon\bin\mysql" (
    for /d %%d in ("C:\laragon\bin\mysql\mysql*") do (
        if exist "%%d\bin\mysqld.exe" set "MYSQLD_BIN=%%d\bin\mysqld.exe"
    )
) else if exist "C:\wamp64\bin\mysql" (
    for /d %%d in ("C:\wamp64\bin\mysql\mysql*") do (
        if exist "%%d\bin\mysqld.exe" set "MYSQLD_BIN=%%d\bin\mysqld.exe"
    )
)

if not "%MYSQLD_BIN%"=="" (
    echo        Starting MySQL Server daemon...
    start /b "" "%MYSQLD_BIN%" --standalone
    timeout /t 2 /nobreak >nul
) else (
    echo        [NOTICE] Couldn't auto-start mysqld.exe daemon.
    echo        If MySQL fails to connect, please start MySQL from your Control Panel (XAMPP / WAMP / Laragon).
)

:MYSQL_DONE

:: ----------------------------------------------------------------------
:: STEP 3: Launch Web Server & Application
:: ----------------------------------------------------------------------
echo [3/3] Launching Application Server...

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" goto LAUNCH_LARAGON

goto LAUNCH_CLI

:LAUNCH_LARAGON
echo        Detected Apache Local Server running (Laragon/XAMPP).
echo        Server Address: http://localhost/smart-expense/
echo.
start http://localhost/smart-expense/
echo ======================================================================
echo  SUCCESS: Smart Expense Tracker is live at http://localhost/smart-expense/
echo  phpMyAdmin: http://localhost/phpmyadmin
echo  Stack: %STACK_NAME% (Apache Localhost)
echo ======================================================================
timeout /t 3 >nul
exit /b 0

:LAUNCH_CLI

:: Fallback to PHP Built-in CLI Server
echo        Starting PHP Built-in Server on http://127.0.0.1:8000 ...
echo.

start /b "" "%PHP_BIN%" -S 127.0.0.1:8000 -t "%~dp0."
timeout /t 2 /nobreak >nul

start http://127.0.0.1:8000

echo ======================================================================
echo  SUCCESS: Smart Expense Tracker is live at http://127.0.0.1:8000
echo  phpMyAdmin: http://localhost/phpmyadmin
echo  Stack: %STACK_NAME%
echo.
echo  Keep this terminal window open while using the app.
echo  Press Ctrl+C or close this window to stop the server.
echo ======================================================================
pause >nul
