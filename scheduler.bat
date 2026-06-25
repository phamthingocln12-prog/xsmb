@echo off
cd /d "D:\PhanMemXSMB\www\xsmb"
"D:\PhanMemXSMB\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan schedule:run >> NUL 2>&1
