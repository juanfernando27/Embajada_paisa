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
                ['Empanadas de la casa', 'Tres empanadas de carne y papa con ají casero.', 12000],
                ['Arepitas con hogao', 'Arepas doradas de maíz con hogao de tomate y cebolla.', 14000],
                ['Patacones para compartir', 'Patacones crujientes con hogao y guacamole.', 16000],
            ],

            'Platos fuertes' => [
                ['Bandeja paisa', 'Fríjoles, arroz, carne molida, chicharrón, chorizo, huevo, aguacate, tajada y arepa.', 42000],
                ['Cazuela de fríjoles', 'Fríjoles con chicharrón, maíz, aguacate y hogao, acompañados de arroz.', 32000],
                ['Pollo de la casa', 'Pechuga a la plancha con arroz, tajada de maduro y ensalada.', 34000],
                ['Trucha al ajillo', 'Trucha con ajo y limón, acompañada de patacón y ensalada.', 43000],
            ],

            'Sopas' => [
                ['Sancocho campesino', 'Caldo de pollo con yuca, papa, plátano y cilantro, servido con arroz.', 28000],
                ['Ajiaco santafereño', 'Sopa de tres papas, pollo, mazorca y guascas, con crema y alcaparras.', 33000],
                ['Mondongo antioqueño', 'Sopa de mondongo, papa, verduras y cilantro, acompañada de arroz.', 29000],
            ],

            'Asados' => [
                ['Punta de anca', 'Corte de res a la parrilla con papa salada y chimichurri.', 49000],
                ['Costillas en salsa BBQ', 'Costillas de cerdo con salsa BBQ, papas y ensalada.', 46000],
            ],

            'Acompañamientos' => [
                ['Porción de fríjoles', 'Fríjoles de cocción lenta con hogao.', 8000],
                ['Tajadas de plátano maduro', 'Porción de plátano maduro dorado.', 6500],
            ],

            'Bebidas' => [
                ['Limonada natural', 'Limonada de limón fresco preparada al momento.', 8000],
                ['Limonada de coco', 'Bebida fría de limón con leche de coco.', 12000],
                ['Jugo de lulo', 'Jugo de lulo preparado en agua y servido con hielo.', 9000],
                ['Café colombiano', 'Taza de café colombiano recién preparado.', 6000],
            ],

            'Postres' => [
                ['Mazamorra paisa', 'Maíz cocido servido con leche y bocadillo.', 11000],
                ['Arroz con leche', 'Arroz cocido en leche con canela.', 10000],
            ],
        ];

        foreach ($data as $nombreCategoria => $platos) {
            $categoria = Categoria::firstOrCreate(
                ['nombre' => $nombreCategoria],
                ['activa' => true],
            );

            foreach ($platos as [$nombre, $descripcion, $precio]) {
                Producto::firstOrCreate(
                    [
                        'nombre' => $nombre,
                        'categoria_id' => $categoria->id,
                    ],
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