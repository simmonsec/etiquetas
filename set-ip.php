<?php

// Función para obtener la IP de la máquina
function getServerIp()
{
    // Obtener la IP a través de diferentes métodos, dependiendo de tu configuración
    // Aquí se muestra un ejemplo simple usando gethostbyname
    return gethostbyname(gethostname());
}

// Obtener la IP
$ip = getServerIp();

// Leer el contenido del archivo .env
$envFile = file_get_contents(__DIR__ . '/.env');

// Verificar si la variable IP_DESARROLLO ya existe
if (strpos($envFile, 'IP_DESARROLLO=') !== false) {
    // Reemplazar la línea existente
    $envFile = preg_replace('/^IP_DESARROLLO=.*$/m', "IP_DESARROLLO=$ip", $envFile);
} else {
    // Agregar la línea al final del archivo
    $envFile .= "\nIP_DESARROLLO=$ip\n";
}

// Escribir los cambios de nuevo al archivo .env
file_put_contents(__DIR__ . '/.env', $envFile);

echo "IP_DESARROLLO set to $ip in .env file.\n";
