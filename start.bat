@echo off
cd /d "%~dp0public"
echo Starting MAHA Fastfood with PHP...
php -S localhost:8000 router.php
pause
