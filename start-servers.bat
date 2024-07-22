@echo off
setlocal enabledelayedexpansion

:: Obtener la dirección IP de la máquina
for /f "tokens=17 delims= " %%A in ('ipconfig ^| findstr /R "Dirección IPv4" ^| findstr /R "192\.168\."') do (
    set IP=%%A
)

:: Mostrar la IP obtenida
echo La dirección IP es: %IP%

:: Verificar si se obtuvo la IP correctamente
if "%IP%"=="" (
    echo No se pudo obtener la dirección IP.
    pause
    exit /b 1
)

:: Iniciar el servidor PHP con Laravel
echo Iniciando el servidor PHP con Laravel en %IP%...
start /B php artisan serve --host %IP% --port 80

:: Iniciar npm run dev
echo Iniciando npm run dev...
start /B npm run dev

:: Esperar a que ambos comandos terminen (esto no sucederá a menos que los procesos sean detenidos manualmente)
pause
