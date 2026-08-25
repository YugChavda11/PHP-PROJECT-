@echo off
setlocal enabledelayedexpansion
title Smart Expense Tracker - Database Setup & Auto-Seeder
color 0B

echo ======================================================================
echo           Smart Expense Tracker - MySQL Database Importer & Seeder
echo                     (XAMPP / WAMP / Laragon / CLI)
echo ======================================================================
echo.

set "PHP_BIN="

:: Check System PATH
where php >nul 2>nul
if %ERRORLEVEL% equ 0 (
    for /f "delims=" %%i in ('where php') do set "PHP_BIN=%%i"
    goto RUN_SEED
)

:: Check XAMPP
if exist "C:\xampp\php\php.exe" (
    set "PHP_BIN=C:\xampp\php\php.exe"
    goto RUN_SEED
)

:: Check Laragon
if exist "C:\laragon\bin\php" (
    for /d %%d in ("C:\laragon\bin\php\php*") do (
        if exist "%%d\php.exe" (
            set "PHP_BIN=%%d\php.exe"
            goto RUN_SEED
        )
    )
)

:: Check WAMP64
if exist "C:\wamp64\bin\php" (
    for /d %%d in ("C:\wamp64\bin\php\php*") do (
        if exist "%%d\php.exe" (
            set "PHP_BIN=%%d\php.exe"
            goto RUN_SEED
        )
    )
)

:RUN_SEED
if "%PHP_BIN%"=="" (
    echo [ERROR] PHP executable was not found!
    echo Please install XAMPP, WAMP, or Laragon, or ensure PHP is on your PATH.
    pause
    exit /b 1
)

echo Running Database Auto-migration and Seed script using "%PHP_BIN%"...
echo ----------------------------------------------------------------------
"%PHP_BIN%" "%~dp0seed.php"
echo ----------------------------------------------------------------------
echo.
echo Database initialization completed successfully!
pause
