@echo off
REM Setup Manajemen Produksi untuk Windows (XAMPP / Laragon). Jalankan dari folder project.
REM Pastikan Apache & MySQL sudah menyala di XAMPP Control Panel.
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 (
  if exist "C:\xampp\php\php.exe" (set PHP=C:\xampp\php\php.exe) else (echo PHP tidak ditemukan. Jalankan dari "Shell" XAMPP. & pause & exit /b 1)
) else (set PHP=php)
if not exist "vendor\autoload.php" (
  echo Menginstal dependency dengan Composer...
  call composer install --no-dev --optimize-autoloader --no-interaction
  if errorlevel 1 (echo Composer gagal / belum terpasang. Unduh di https://getcomposer.org & pause & exit /b 1)
)
for %%D in (storage\framework\sessions storage\framework\views storage\framework\cache\data storage\logs bootstrap\cache) do if not exist "%%D" mkdir "%%D"
if not exist ".env" copy ".env.example" ".env" >nul
%PHP% artisan key:generate --force
REM migrate pertama membuat database produksi bila belum ada
%PHP% artisan migrate --force
%PHP% artisan migrate:fresh --seed --force
%PHP% artisan optimize:clear
echo.
echo Selesai. Buka http://localhost/produksi
echo   Admin    : admin@produksi.test / admin123
echo   PPIC     : ppic@produksi.test / ppic123
echo   Gudang   : gudang@produksi.test / gudang123
echo   Operator : operator@produksi.test / operator123
pause
