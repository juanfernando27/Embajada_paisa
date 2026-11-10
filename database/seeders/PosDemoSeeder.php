<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) && ! env('RUN_CATALOG_SEEDER', false)) {
            throw new \RuntimeException('La carga del catálogo requiere RUN_CATALOG_SEEDER=true.');
        }

        $data = [
            'Entradas' => [
                ['Empanadas de la casa', 'Tres empanadas de carne y papa acompañadas de ají casero.', 12000],
                ['Arepitas con hogao', 'Arepas doradas de maíz servidas con hogao de tomate y cebolla.', 14000],
                ['Patacones para compartir', 'Patacones crujientes con hogao y guacamole.', 16000],
                ['Chorizo antioqueño con arepa', 'Chorizo a la plancha con arepa blanca y limón.', 16000],
                ['Chicharrón en trozos', 'Chicharrón crocante acompañado de arepa y limón.', 19000],
                ['Yuca frita con ají', 'Bastones de yuca dorada servidos con ají de la casa.', 11000],
            ],

            'Platos fuertes' => [
                ['Bandeja paisa', 'Fríjoles, arroz, carne molida, chicharrón, chorizo, huevo, aguacate, tajada y arepa.', 42000],
                ['Cazuela de fríjoles', 'Fríjoles con chicharrón, maíz, aguacate y hogao, acompañados de arroz.', 32000],
                ['Carne a la parrilla', 'Carne de res a la parrilla con papa y ensalada de temporada.', 39000],
                ['Pollo de la casa', 'Pechuga a la plancha con arroz, tajada de maduro y ensalada.', 34000],
                ['Calentado paisa', 'Arroz y fríjoles salteados con hogao, huevo frito, arepa y aguacate.', 26000],
                ['Arroz con pollo', 'Arroz con pollo desmechado y verduras, acompañado de papas y ensalada.', 29000],
                ['Lengua en salsa criolla', 'Lengua de res en salsa de tomate y cebolla, con arroz y papa.', 39000],
                ['Trucha al ajillo', 'Trucha con ajo y limón, acompañada de patacón y ensalada.', 43000],
            ],

            'Sopas' => [
                ['Sancocho campesino', 'Caldo de pollo con yuca, papa, plátano y cilantro, servido con arroz.', 28000],
                ['Ajiaco santafereño', 'Sopa de tres papas, pollo, mazorca y guascas, con crema y alcaparras.', 33000],
                ['Mondongo antioqueño', 'Sopa de mondongo, papa, verduras y cilantro, acompañada de arroz.', 29000],
                ['Sopa de verduras', 'Caldo de papa, zanahoria, arveja y verduras de temporada.', 18000],
                ['Caldo de costilla', 'Caldo de res con papa, cebolla y cilantro, acompañado de arepa.', 24000],
            ],

            'Asados' => [
                ['Punta de anca', 'Corte de res a la parrilla con papa salada y chimichurri.', 49000],
                ['Costillas en salsa BBQ', 'Costillas de cerdo con salsa BBQ, papas y ensalada.', 46000],
                ['Lomo de cerdo a la parrilla', 'Lomo de cerdo con papas doradas y ensalada fresca.', 38000],
                ['Parrillada personal', 'Res, cerdo, pollo y chorizo con papa y arepa.', 54000],
                ['Churrasco de res', 'Corte de res a la parrilla con papas, ensalada y chimichurri.', 48000],
            ],

            'Acompañamientos' => [
                ['Porción de arroz blanco', 'Arroz blanco para acompañar el plato principal.', 5000],
                ['Porción de fríjoles', 'Fríjoles de cocción lenta con hogao.', 8000],
                ['Tajadas de plátano maduro', 'Porción de plátano maduro dorado.', 6500],
                ['Papas a la francesa', 'Porción de papas fritas con sal.', 8000],
                ['Ensalada de aguacate', 'Aguacate, tomate y cebolla con limón.', 10000],
            ],

            'Bebidas' => [
                ['Limonada natural', 'Limonada de limón fresco preparada al momento.', 8000],
                ['Jugo de temporada', 'Jugo de fruta disponible en agua. Consulta los sabores del día.', 9000],
                ['Café colombiano', 'Taza de café colombiano recién preparado.', 6000],
                ['Limonada de coco', 'Bebida fría de limón con leche de coco.', 12000],
                ['Jugo de lulo', 'Jugo de lulo preparado en agua y servido con hielo.', 9000],
                ['Aguapanela con limón', 'Bebida de panela y limón, fría o caliente.', 6500],
                ['Chocolate caliente', 'Chocolate de mesa preparado en leche.', 7500],
            ],

            'Postres' => [
                ['Postre de la casa', 'Consulta el postre preparado para el día.', 14000],
                ['Dulce tradicional', 'Porción del dulce tradicional disponible en el día.', 11000],
                ['Mazamorra paisa', 'Maíz cocido servido con leche y bocadillo.', 11000],
                ['Arroz con leche', 'Arroz cocido en leche con canela.', 10000],
                ['Brevas con arequipe', 'Brevas en almíbar acompañadas de arequipe.', 12000],
                ['Flan de caramelo', 'Flan de leche y huevo con caramelo.', 12000],
            ],
        ];

        foreach ($data as $nombreCategoria => $platos) {
            $categoria = Categoria::firstOrCreate(
                ['nombre' => $nombreCategoria],
                ['activa' => true],
            );

            foreach ($platos as [$nombre, $descripcion, $precio]) {
                Producto::firstOrCreate(
                    ['nombre' => $nombre, 'categoria_id' => $categoria->id],
                    [
                        'descripcion' => $descripcion,
                        'precio_centavos' => $precio * 100,
                        'stock' => null,
                        'activo' => true,
                    ],
                );
            }
        }
    }
}