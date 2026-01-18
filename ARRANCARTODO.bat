@echo off
setlocal

cd /d C:\Users\coque\Documents\MEGA\Web\Dominios\chollopañales.com\laravel

start cmd /k "php artisan serve"
start cmd /k "npm run dev"
start "" "C:\xampp\xampp-control.exe"
