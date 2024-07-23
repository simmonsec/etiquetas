@echo off
setlocal

:: Directorio de trabajo
cd /d C:\laragon\www\go\
echo "Entramos a C:\laragon\www\go\" >> inicio_servicios.log

:: Verificar si los servidores ya están corriendo
set "isPHPRunning=0"
tasklist /FI "WINDOWTITLE eq php artisan serve" | find /I "php.exe" >nul 2>&1
if %errorlevel% == 0 set "isPHPRunning=1"

set "isNPMRunning=0"
tasklist /FI "WINDOWTITLE eq npm run dev" | find /I "node.exe" >nul 2>&1
if %errorlevel% == 0 set "isNPMRunning=1"

:: Iniciar el servidor PHP con Laravel solo si no está corriendo
if %isPHPRunning%==0 (
    echo Iniciando el servidor PHP con Laravel... >> inicio_servicios.log
    start /min cmd /c "php artisan serve --host 192.168.0.187 --port 80 > laravel_server.log 2>&1"
) else (
    echo El servidor PHP ya está en ejecución. >> inicio_servicios.log
)

:: Iniciar npm run dev solo si no está corriendo
if %isNPMRunning%==0 (
    echo Iniciando npm run dev... >> inicio_servicios.log
    start /min cmd /c "npm run dev > npm_dev.log 2>&1"
) else (
    echo npm run dev ya está en ejecución. >> inicio_servicios.log
)

endlocal
