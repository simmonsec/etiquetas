<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProdAppSeccionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('Simmons01.prod_app_secciones_tb')->insert([
            ['secID' => 'PSE001', 'sec_descripcion' => 'ACCESORIO', 'sec_grupo' => 'ACCESORIO'],
            ['secID' => 'PSE002', 'sec_descripcion' => 'ALMOHADA NUCLEO', 'sec_grupo' => 'ALMOHADA'],
            ['secID' => 'PSE003', 'sec_descripcion' => 'ALMOHADA FIBRA', 'sec_grupo' => 'ALMOHADA'],
            ['secID' => 'PSE004', 'sec_descripcion' => 'BASE', 'sec_grupo' => 'BASE'],
            ['secID' => 'PSE005', 'sec_descripcion' => 'ARMADO COLCHON', 'sec_grupo' => 'COLCHON'],
            ['secID' => 'PSE006', 'sec_descripcion' => 'ARMADO BONNELL', 'sec_grupo' => 'COLCHON'],
            ['secID' => 'PSE007', 'sec_descripcion' => 'CERRADO COLCHON', 'sec_grupo' => 'COLCHON'],
            ['secID' => 'PSE008', 'sec_descripcion' => 'EMPAQUE', 'sec_grupo' => 'COLCHON'],
            ['secID' => 'PSE009', 'sec_descripcion' => 'ACOLCHADO', 'sec_grupo' => 'ACOLCHADO'],
            ['secID' => 'PSE010', 'sec_descripcion' => 'BANDA', 'sec_grupo' => 'BANDA'],
            ['secID' => 'PSE011', 'sec_descripcion' => 'TAPA', 'sec_grupo' => 'TAPA'],
            ['secID' => 'PSE012', 'sec_descripcion' => 'TAPA REPARACION', 'sec_grupo' => 'TAPA'],
            ['secID' => 'PSE013', 'sec_descripcion' => 'CAMBRELA EMPAQ', 'sec_grupo' => 'EMPAQUE'],
            ['secID' => 'PSE014', 'sec_descripcion' => 'MADERA', 'sec_grupo' => 'MADERA'],
            ['secID' => 'PSE015', 'sec_descripcion' => 'ESPUMA', 'sec_grupo' => 'ESPUMA'],
            ['secID' => 'PSE016', 'sec_descripcion' => 'FORRO ALMOHADA', 'sec_grupo' => 'CONFECCION'],
            ['secID' => 'PSE017', 'sec_descripcion' => 'FORRO ACCESORIO', 'sec_grupo' => 'CONFECCION'],
            ['secID' => 'PSE018', 'sec_descripcion' => 'FORRO BASE', 'sec_grupo' => 'CONFECCION'],
            ['secID' => 'PSE019', 'sec_descripcion' => 'POCKET', 'sec_grupo' => 'RESORTE'],
            ['secID' => 'PSE020', 'sec_descripcion' => 'PANEL', 'sec_grupo' => 'RESORTE'],
            ['secID' => 'PSE021', 'sec_descripcion' => 'CORTE', 'sec_grupo' => 'CORTE'],
            ['secID' => 'PSE022', 'sec_descripcion' => 'ALMOHADA HIBRIDA', 'sec_grupo' => 'ALMOHADA'],
            ['secID' => '0', 'sec_descripcion' => 'SIN SECCION', 'sec_grupo' => null],
        ]);
        
    }
}
