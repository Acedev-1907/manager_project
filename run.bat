@echo off

start cmd /k "php artisan serve"
start cmd /k "npm run dev"
start cmd /k "php artisan queue:work"
start cmd /k "maildev --web 1081 --smtp 2525"
start cmd /k "php artisan reverb:start"

pause
