@echo off
setlocal enabledelayedexpansion

:: Directorio de trabajo
cd /d C:\laragon\www\etiquetas\
echo "Entramos a C:\laragon\www\etiquetas\"

:: Obtener la dirección IP de la máquina
for /f "tokens=17 delims= " %%A in ('ipconfig ^| findstr /R "Dirección IPv4" ^| findstr /R "192\.168\."') do (
    set IP=%%A
)

if "%IP%"=="" (
    for /f "tokens=14 delims= " %%A in ('ipconfig ^| findstr /R "IPv4 Address" ^| findstr /R "192\.168\."') do (
    set IP=%%A
    echo "Entro a validar de otra manera la IP: %IP%"
    )
)

:: Mostrar la IP obtenida
echo La direccion IP es: %IP%

:: Verificar si se obtuvo la IP correctamente
if "%IP%"=="" (
    echo No se pudo obtener la direccion IP.
    pause
    exit /b 1
)

:: Iniciar el servidor PHP con Laravel
echo Iniciando el servidor PHP con Laravel en %IP%...
start /B cmd /c "php artisan serve --host %IP% --port 80 > laravel_server.log 2>&1"

:: Iniciar npm run dev
echo Iniciando npm run dev...
start /B cmd /c "npm run dev > npm_dev.log 2>&1"

:: Esperar a que ambos comandos terminen (esto no sucederá a menos que los procesos sean detenidos manualmente)
pause
