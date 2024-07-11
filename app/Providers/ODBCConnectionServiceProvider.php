<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ODBCConnectionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Configurar la conexión ODBC
        $this->app->singleton('odbc', function ($app) {
            // Datos para la conexión
            $dsn = 'MBAPruebas'; // DSN de 4D configurado en ODBC
            $user = env('DB_4D_USERNAME', '');
            $password = env('DB_4D_PASSWORD', '');

            // Conectar usando ODBC
            $conn = odbc_connect($dsn, $user, $password);

            if (!$conn) {
                die("Error al conectar a la base de datos.");
            }

            // Debugging para verificar si se ejecuta correctamente
            \Log::info("Conexión ODBC establecida.");

            // Retornar la conexión para ser usada por Laravel
            return $conn;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
