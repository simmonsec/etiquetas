<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Conexion4k;
use App\Services\ConexionPostgres;
use App\Services\LoggerPersonalizado;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Conexion4k::class, function ($app) {
            return new Conexion4k();
        });

        $this->app->singleton(ConexionPostgres::class, function ($app) {
            return new ConexionPostgres();
        });

        $this->app->bind(LoggerPersonalizado::class, function ($app, $params) {
            return new LoggerPersonalizado($params['nombreAplicacion']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
