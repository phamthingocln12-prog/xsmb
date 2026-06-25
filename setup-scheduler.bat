@echo off
chcp 65001 >nul
title XSMB - Cai dat tu dong
color 0B

:: ============================================
:: DUONG DAN LARAGON (CHINH XAC THEO MAY)
:: ============================================
set "PHP_PATH=D:\PhanMemXSMB\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
set "PROJECT_PATH=D:\PhanMemXSMB\www\xsmb"

echo.
echo   ========================================================
echo   ^|   CAI DAT TU DONG CHO HE THONG XSMB                  ^|
echo   ^|   Chi can chay 1 lan duy nhat (Run as Admin)          ^|
echo   ========================================================
echo.

:: Kiem tra PHP
if not exist "%PHP_PATH%" (
    echo [LOI] Khong tim thay PHP tai: %PHP_PATH%
    pause
    exit /b 1
)
echo [OK] PHP: %PHP_PATH%
echo [OK] Du an: %PROJECT_PATH%
echo.

:: ============================================
:: 1. Tao file scheduler.bat
:: ============================================
echo @echo off > "%PROJECT_PATH%\scheduler.bat"
echo cd /d "%PROJECT_PATH%" >> "%PROJECT_PATH%\scheduler.bat"
echo "%PHP_PATH%" artisan schedule:run ^>^> NUL 2^>^&1 >> "%PROJECT_PATH%\scheduler.bat"
echo [OK] Da tao scheduler.bat
echo.

:: ============================================
:: 2. Tao file scheduler.vbs (chay an, khong hien CMD)
:: ============================================
echo Set WshShell = CreateObject("WScript.Shell") > "%PROJECT_PATH%\scheduler.vbs"
echo WshShell.Run """%PROJECT_PATH%\scheduler.bat""", 0, False >> "%PROJECT_PATH%\scheduler.vbs"
echo Set WshShell = Nothing >> "%PROJECT_PATH%\scheduler.vbs"
echo [OK] Da tao scheduler.vbs (chay an khong hien cua so)
echo.

:: ============================================
:: 3. Dang ky Windows Task Scheduler
::    Chay moi 1 phut, hoan toan an
:: ============================================
echo [..] Dang dang ky Windows Task Scheduler...
schtasks /delete /tn "XSMB_LaravelScheduler" /f >nul 2>&1
schtasks /create /tn "XSMB_LaravelScheduler" /tr "wscript.exe \"%PROJECT_PATH%\scheduler.vbs\"" /sc minute /mo 1 /f

if %errorlevel%==0 (
    echo [OK] Da dang ky thanh cong!
    echo     - Ten task: XSMB_LaravelScheduler
    echo     - Tan suat: Moi 1 phut
    echo     - Che do: Chay an (khong hien CMD)
) else (
    echo [LOI] Khong the dang ky Task Scheduler.
    echo       HAY CHAY FILE NAY BANG QUYEN ADMIN:
    echo       Click phai ^> Run as administrator
    echo.
    pause
    exit /b 1
)

:: ============================================
:: 4. Cap nhat du lieu ban dau
:: ============================================
echo.
echo [..] Dang cap nhat du lieu phan tich...
cd /d "%PROJECT_PATH%"
"%PHP_PATH%" artisan xsmb:extract-analysis >nul 2>&1
"%PHP_PATH%" artisan stat:calculate >nul 2>&1
echo [OK] Du lieu phan tich da cap nhat!

echo.
echo   ========================================================
echo   HOAN TAT! He thong se TU DONG chay hang ngay:
echo.
echo   - Moi 1 phut: Kiem tra lich trinh
echo   - 18:08-23:59: Tu dong cao du lieu + tuong thuat
echo   - 18:55: Tu dong tinh thong ke + phan tich
echo   - Khong can mo terminal, chay hoan toan AN
echo.
echo   LUU Y: Can Laragon dang chay (MySQL + Redis + Apache)
echo          Nen bat "Start with Windows" trong Laragon
echo   ========================================================
echo.
pause
