@echo off
chcp 65001 >nul
title XSMB - He thong Tuong thuat Truc tiep
color 0A

:: ============================================
:: DUONG DAN LARAGON (DA TINH THEO MAY CUA BAN)
:: ============================================
set "PHP_PATH=D:\PhanMemXSMB\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
set "PROJECT_PATH=D:\PhanMemXSMB\www\xsmb"
set "MYSQL_PATH=D:\PhanMemXSMB\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
set "MYSQL_INI=D:\PhanMemXSMB\bin\mysql\mysql-8.4.3-winx64\my.ini"
set "REDIS_PATH=D:\PhanMemXSMB\bin\redis\redis-x64-5.0.14.1\redis-server.exe"
set "APACHE_PATH=D:\PhanMemXSMB\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"

echo.
echo   ========================================================
echo   ^|   XSMB - HE THONG TUONG THUAT TRUC TIEP              ^|
echo   ^|   Double-click de chay tat ca tu dong!                ^|
echo   ========================================================
echo.

:: ============================================
:: BUOC 1: Kiem tra PHP
:: ============================================
if not exist "%PHP_PATH%" (
    echo [LOI] Khong tim thay PHP tai: %PHP_PATH%
    pause
    exit /b 1
)
echo [OK] PHP san sang.

:: ============================================
:: BUOC 2: Khoi dong MySQL (neu chua chay)
:: ============================================
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
if %errorlevel% NEQ 0 (
    echo [..] MySQL chua chay, dang khoi dong...
    if exist "%MYSQL_PATH%" (
        if exist "%MYSQL_INI%" (
            start "" /B "%MYSQL_PATH%" --defaults-file="%MYSQL_INI%"
        ) else (
            start "" /B "%MYSQL_PATH%"
        )
        timeout /t 4 /nobreak >nul
        echo [OK] MySQL da khoi dong!
    ) else (
        echo [CANH BAO] Khong tim thay MySQL. Hay mo Laragon truoc.
    )
) else (
    echo [OK] MySQL dang chay.
)

:: ============================================
:: BUOC 3: Khoi dong Redis (can cho Queue)
:: ============================================
tasklist /FI "IMAGENAME eq redis-server.exe" 2>NUL | find /I "redis-server.exe" >NUL
if %errorlevel% NEQ 0 (
    echo [..] Redis chua chay, dang khoi dong...
    if exist "%REDIS_PATH%" (
        start "" /B "%REDIS_PATH%"
        timeout /t 2 /nobreak >nul
        echo [OK] Redis da khoi dong!
    ) else (
        echo [CANH BAO] Khong tim thay Redis.
    )
) else (
    echo [OK] Redis dang chay.
)

:: ============================================
:: BUOC 4: Khoi dong Apache (neu chua chay)
:: ============================================
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if %errorlevel% NEQ 0 (
    echo [..] Apache chua chay, dang khoi dong...
    if exist "%APACHE_PATH%" (
        start "" /B "%APACHE_PATH%"
        timeout /t 2 /nobreak >nul
        echo [OK] Apache da khoi dong!
    ) else (
        echo [CANH BAO] Khong tim thay Apache.
    )
) else (
    echo [OK] Apache dang chay.
)

echo.
echo   ------------------------------------------------
echo   BUOC 5: Cap nhat du lieu (Backfill ngay thieu)
echo   ------------------------------------------------

cd /d "%PROJECT_PATH%"

:: Chay backfill
echo [..] Dang kiem tra va cao du lieu cac ngay thieu...
"%PHP_PATH%" artisan crawl:today
echo.

:: Chay queue worker de xu ly cac job vua dispatch
echo [..] Dang xu ly hang doi (queue:work)...
start "" /B "%PHP_PATH%" artisan queue:work --stop-when-empty --timeout=120 --tries=3

:: Cho queue xu ly (toi da 30 giay)
echo [..] Cho queue xu ly...
timeout /t 15 /nobreak >nul

:: Tinh thong ke
echo [..] Dang tinh thong ke...
"%PHP_PATH%" artisan stat:calculate >nul 2>&1
"%PHP_PATH%" artisan xsmb:extract-analysis >nul 2>&1
echo [OK] Cap nhat du lieu hoan tat!

echo.
echo   ------------------------------------------------
echo   BUOC 6: Tuong thuat Truc tiep XSMB
echo   ------------------------------------------------

:: Lay gio hien tai
for /f "tokens=1-2 delims=:" %%a in ("%time: =0%") do (
    set "HOUR=%%a"
    set "MINUTE=%%b"
)
set /a HOUR=%HOUR%
set /a MINUTE=%MINUTE%
set /a NOW_MIN=%HOUR% * 60 + %MINUTE%

:: Khung gio quay: 18:00 - 18:50
set /a DRAW_START=1080
set /a DRAW_END=1130

if %NOW_MIN% GEQ %DRAW_START% if %NOW_MIN% LEQ %DRAW_END% (
    echo.
    echo   *** DANG TRONG KHUNG GIO QUAY! BAT DAU TUONG THUAT ***
    echo.
    "%PHP_PATH%" artisan xsmb:live-watch --force --timeout=35
    goto :DONE
)

if %NOW_MIN% LSS %DRAW_START% (
    set /a WAIT=%DRAW_START% - %NOW_MIN%
    echo [INFO] Hien tai: %HOUR%:%MINUTE%
    echo [INFO] Gio quay: 18:00
    echo [INFO] Con khoang !WAIT! phut nua.
    echo.
    echo He thong se TU DONG cho den 18:00 roi bat dau tuong thuat.
    echo ^(De nguyen cua so nay, khong can lam gi ca^)
    echo.
    
    :: Lenh nay se TU DONG cho den 18:10 roi chay
    "%PHP_PATH%" artisan xsmb:live-watch --timeout=35
    goto :DONE
)

:: Da qua gio quay
echo [INFO] Da qua khung gio tuong thuat hom nay (18:00 - 18:50).
echo        Du lieu da duoc cap nhat xong. Khong can tuong thuat.

:DONE
echo.
echo   ========================================================
echo   HOAN TAT!
echo   - Du lieu: Da cap nhat day du
echo   - Web: http://localhost:8081/xsmb/public/
echo   ========================================================
echo.
pause
