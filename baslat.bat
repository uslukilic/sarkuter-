@echo off
REM Siteyi yerelde acar: http://localhost:8000  (XAMPP icindeki PHP kullanilir)
cd /d "%~dp0"
if not exist inc\ayar.php (
  echo inc\ayar.php yok. inc\ayar.ornek.php dosyasini inc\ayar.php adiyla kopyalayip doldurun. README.md dosyasina bakin.
  pause
  exit /b 1
)
set PHP=C:\xampp\php\php.exe
if not exist "%PHP%" set PHP=php
start "" http://localhost:8000
"%PHP%" -S localhost:8000 router.php
