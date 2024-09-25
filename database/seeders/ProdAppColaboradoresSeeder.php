<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProdAppColaboradoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('Simmons01.prod_app_colaboradores_tb')->insert([
            ['colID' => 1, 'col_nombre' => 'Ajila Caluña Aida Paulina', 'col_estado' => 'A', 'col_nombre_corto' => 'Ajila Aida'],
            ['colID' => 2, 'col_nombre' => 'Anchundia Piguave César Gabriel', 'col_estado' => 'A', 'col_nombre_corto' => 'Anchundia Piguave César Gabriel'],
            ['colID' => 3, 'col_nombre' => 'Bonilla García Rosario Noemi', 'col_estado' => 'A', 'col_nombre_corto' => 'Bonilla García Rosario Noemi'],
            ['colID' => 4, 'col_nombre' => 'Briones Carriel Víctor Antonio', 'col_estado' => 'A', 'col_nombre_corto' => 'Briones Carriel Víctor Antonio'],
            ['colID' => 5, 'col_nombre' => 'Caiche Guzmán Isabel del Carmen', 'col_estado' => 'A', 'col_nombre_corto' => 'Caiche Guzmán Isabel del Carmen'],
            ['colID' => 6, 'col_nombre' => 'Campozano Zúñiga Washington Guillermo', 'col_estado' => 'A', 'col_nombre_corto' => 'Campozano Zúñiga Washington Guillermo'],
            ['colID' => 7, 'col_nombre' => 'Cando Villacreses Diana Kathiuska', 'col_estado' => 'A', 'col_nombre_corto' => 'Cando Villacreses Diana Kathiuska'],
            ['colID' => 8, 'col_nombre' => 'Cruz Peña Paúl Alexander', 'col_estado' => 'A', 'col_nombre_corto' => 'Cruz Peña Paúl Alexander'],
            ['colID' => 9, 'col_nombre' => 'Cruz Suárez Jessica Rafaela', 'col_estado' => 'A', 'col_nombre_corto' => 'Cruz Suárez Jessica Rafaela'],
            ['colID' => 10, 'col_nombre' => 'Del Pezo Catuto José Jacinto', 'col_estado' => 'A', 'col_nombre_corto' => 'Del Pezo Catuto José Jacinto'],
            ['colID' => 11, 'col_nombre' => 'Demera Lucas Jessica Amarilis', 'col_estado' => 'A', 'col_nombre_corto' => 'Demera Lucas Jessica Amarilis'],
            ['colID' => 12, 'col_nombre' => 'Flores Pinela María José', 'col_estado' => 'A', 'col_nombre_corto' => 'Flores Pinela María José'],
            ['colID' => 13, 'col_nombre' => 'Galarza Pihuave Cesar Juvencio', 'col_estado' => 'A', 'col_nombre_corto' => 'Galarza Pihuave Cesar Juvencio'],
            ['colID' => 14, 'col_nombre' => 'García Reyes Sergio Wilfrido', 'col_estado' => 'A', 'col_nombre_corto' => 'García Reyes Sergio Wilfrido'],
            ['colID' => 15, 'col_nombre' => 'Garzón Burbano Alisson Noemi', 'col_estado' => 'A', 'col_nombre_corto' => 'Garzón Burbano Alisson Noemi'],
            ['colID' => 16, 'col_nombre' => 'González Borbor Karen Setriz', 'col_estado' => 'A', 'col_nombre_corto' => 'González Borbor Karen Setriz'],
            ['colID' => 17, 'col_nombre' => 'Guaranda Jimenez Miriam Estefania', 'col_estado' => 'A', 'col_nombre_corto' => 'Guaranda Jimenez Miriam Estefania'],
            ['colID' => 18, 'col_nombre' => 'Guerra Álava Freddy Vicente', 'col_estado' => 'A', 'col_nombre_corto' => 'Guerra Álava Freddy Vicente'],
            ['colID' => 19, 'col_nombre' => 'Guerrero Peñafiel Martha Marisol', 'col_estado' => 'A', 'col_nombre_corto' => 'Guerrero Peñafiel Martha Marisol'],
            ['colID' => 20, 'col_nombre' => 'Junco Reyes Stalin Bautista', 'col_estado' => 'A', 'col_nombre_corto' => 'Junco Reyes Stalin Bautista'],
            ['colID' => 21, 'col_nombre' => 'Maigua Reinoso Nubia Nohemi', 'col_estado' => 'A', 'col_nombre_corto' => 'Maigua Reinoso Nubia Nohemi'],
            ['colID' => 22, 'col_nombre' => 'Maldonado Montoya Grace Lorena', 'col_estado' => 'A', 'col_nombre_corto' => 'Maldonado Montoya Grace Lorena'],
            ['colID' => 23, 'col_nombre' => 'Mora Marín Luis Humberto', 'col_estado' => 'A', 'col_nombre_corto' => 'Mora Marín Luis Humberto'],
            ['colID' => 24, 'col_nombre' => 'Moreira García Gregorio Jerónimo', 'col_estado' => 'A', 'col_nombre_corto' => 'Moreira García Gregorio Jerónimo'],
            ['colID' => 25, 'col_nombre' => 'Núñez Morán Ángel Marlon', 'col_estado' => 'A', 'col_nombre_corto' => 'Núñez Morán Ángel Marlon'],
            ['colID' => 26, 'col_nombre' => 'Orellana Vasquez Anthony Mosies', 'col_estado' => 'I', 'col_nombre_corto' => 'Orellana Vasquez Anthony Mosies'],
            ['colID' => 27, 'col_nombre' => 'Peña Villacís Johanna Isabel', 'col_estado' => 'I', 'col_nombre_corto' => 'Peña Villacís Johanna Isabel'],
            ['colID' => 28, 'col_nombre' => 'Pilay Briones Mildred Estefania', 'col_estado' => 'I', 'col_nombre_corto' => 'Pilay Briones Mildred Estefania'],
            ['colID' => 29, 'col_nombre' => 'Ponguillo Zambrano Jimmy Roberto', 'col_estado' => 'I', 'col_nombre_corto' => 'Ponguillo Zambrano Jimmy Roberto'],
            ['colID' => 30, 'col_nombre' => 'Quimí Santos Anthony Steeven', 'col_estado' => 'I', 'col_nombre_corto' => 'Quimí Santos Anthony Steeven'],
            ['colID' => 31, 'col_nombre' => 'Reyes Garrido Sugey de los Ángeles', 'col_estado' => 'I', 'col_nombre_corto' => 'Reyes Garrido Sugey de los Ángeles'],
            ['colID' => 32, 'col_nombre' => 'Ruiz Vera Jhonny Israel', 'col_estado' => 'I', 'col_nombre_corto' => 'Ruiz Vera Jhonny Israel'],
            ['colID' => 33, 'col_nombre' => 'Santos Quinde Jonathan Andres', 'col_estado' => 'I', 'col_nombre_corto' => 'Santos Quinde Jonathan Andres'],
            ['colID' => 34, 'col_nombre' => 'Silva Urgiles Kenneth Edgar', 'col_estado' => 'I', 'col_nombre_corto' => 'Silva Urgiles Kenneth Edgar'],
            ['colID' => 35, 'col_nombre' => 'Tello Murrugarra Daniel Francisco', 'col_estado' => 'I', 'col_nombre_corto' => 'Tello Murrugarra Daniel Francisco'],
            ['colID' => 36, 'col_nombre' => 'Torres Bustamante Peter Jose', 'col_estado' => 'I', 'col_nombre_corto' => 'Torres Bustamante Peter Jose'],
            ['colID' => 37, 'col_nombre' => 'Vera Vera Daniel Vicente', 'col_estado' => 'I', 'col_nombre_corto' => 'Vera Vera Daniel Vicente'],
            ['colID' => 38, 'col_nombre' => 'Vernaza Reasco Joe Johan', 'col_estado' => 'I', 'col_nombre_corto' => 'Vernaza Reasco Joe Johan'],
            ['colID' => 39, 'col_nombre' => 'Vila Morante Víctor Johan', 'col_estado' => 'I', 'col_nombre_corto' => 'Vila Morante Víctor Johan'],
            ['colID' => 40, 'col_nombre' => 'Villavicencio Holguín María Xiomara', 'col_estado' => 'A', 'col_nombre_corto' => 'Villavicencio Holguín María Xiomara'],
            ['colID' => 41, 'col_nombre' => 'Zamora Landazuri Tony Rafael', 'col_estado' => 'A', 'col_nombre_corto' => 'Zamora Landazuri Tony Rafael'],
            ['colID' => 42, 'col_nombre' => 'GREGORIO PRUEBAS', 'col_estado' => 'A', 'col_nombre_corto' => 'GREGORIO PRUEBAS'],
        ]);
    }
}
