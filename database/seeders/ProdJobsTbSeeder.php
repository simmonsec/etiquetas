<?php
namespace Database\Seeders; 
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;  
 
class ProdJobsTbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('Simmons01.gnl_tareas_tb')->insert([
            [
                'gtar_estadistica' => 'DespachosMov01',
                'gtar_descripcion' => 'Acumula Estadísticas de despachos',
                'gtar_activo' => 'S',
                'gpar_valor_tipo' => 'postgres',
                'gtar_intervalo_segundos' => 600,
                'gtar_proxima_ejecucion' => '2024-09-05 10:53:00',
                'gtar_inicio_anterior' => '2024-09-05 10:42:00',
                'gtar_fin_anterior' => '2024-09-05 10:43:00',
                'gtar_duracion_anterior' => '00:01:17',
            ],
            [
                'gtar_estadistica' => 'RefrescamientosVarios01',
                'gtar_descripcion' => 'Incluye Clientes SimmonsGO',
                'gtar_activo' => 'S',
                'gpar_valor_tipo' => 'postgres',
                'gtar_intervalo_segundos' => 600,
                'gtar_proxima_ejecucion' => '2024-09-05 10:53:00',
                'gtar_inicio_anterior' => '2024-09-05 10:43:00',
                'gtar_fin_anterior' => '2024-09-05 10:43:00',
                'gtar_duracion_anterior' => '00:00:03',
            ],
            [
                'gtar_estadistica' => 'ExistenciasERP2WEB01',
                'gtar_descripcion' => 'Cargar los saldos del ERP a la WEB',
                'gtar_activo' => 'S',
                'gpar_valor_tipo' => 'postgres',
                'gtar_intervalo_segundos' => 1800,
                'gtar_proxima_ejecucion' => '2024-09-05 10:55:00',
                'gtar_inicio_anterior' => '2024-09-05 10:18:00',
                'gtar_fin_anterior' => '2024-09-05 10:25:00',
                'gtar_duracion_anterior' => '00:06:59',
            ],
            [
                'gtar_estadistica' => 'DimZonaGeografica01',
                'gtar_descripcion' => 'Distribucion geografica',
                'gtar_activo' => 'S',
                'gpar_valor_tipo' => 'postgres',
                'gtar_intervalo_segundos' => 1800,
                'gtar_proxima_ejecucion' => '2024-09-05 10:55:00',
                'gtar_inicio_anterior' => '2024-09-05 10:25:00',
                'gtar_fin_anterior' => '2024-09-05 10:25:00',
                'gtar_duracion_anterior' => '00:00:04',
            ],
            [
                'gtar_estadistica' => 'DimTransporte01',
                'gtar_descripcion' => 'Refresca info de transportistas',
                'gtar_activo' => 'S',
                'gpar_valor_tipo' => 'postgres',
                'gtar_intervalo_segundos' => 1800,
                'gtar_proxima_ejecucion' => '2024-09-05 10:56:00',
                'gtar_inicio_anterior' => '2024-09-05 10:26:00',
                'gtar_fin_anterior' => '2024-09-05 10:26:00',
                'gtar_duracion_anterior' => '00:00:07',
            ],
            [
                'gtar_estadistica' => 'inventario:terceros',
                'gtar_descripcion' => 'Proceso que ejecuta el inventario de los envios de terceros',
                'gtar_activo' => 'S',
                'gpar_valor_tipo' => 'php',
                'gtar_intervalo_segundos' => 600,
                'gtar_proxima_ejecucion' => '2024-09-05 10:56:00',
                'gtar_inicio_anterior' => '2024-09-05 10:26:00',
                'gtar_fin_anterior' => '2024-09-05 10:26:00',
                'gtar_duracion_anterior' => '00:00:07',
            ],
            [
                'gtar_estadistica' => 'sincronizar:produccionEventos',
                'gtar_descripcion' => 'Proceso de php que permite realizar la sincronización de los datos de la aplicación del producción a los',
                'gtar_activo' => 'S',
                'gpar_valor_tipo' => 'php',
                'gtar_intervalo_segundos' => 600,
                'gtar_proxima_ejecucion' => '2024-09-05 10:56:00',
                'gtar_inicio_anterior' => '2024-09-05 10:26:00',
                'gtar_fin_anterior' => '2024-09-05 10:26:00',
                'gtar_duracion_anterior' => '00:00:07',
            ],
            // Agregar más entradas para el resto de los datos...
        ]);
    }
}
