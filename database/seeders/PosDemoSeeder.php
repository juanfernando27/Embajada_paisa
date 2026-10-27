<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Datos demo solo para desarrollo.');
        }
        $data = [
            'Entradas' => [['Empanadas de la casa', 'Crujientes empanadas rellenas, acompañadas de ají de la casa.', 12000], ['Arepitas con hogao', 'Arepas doradas servidas con un hogao de sabor tradicional.', 14000], ['Patacones para compartir', 'Patacones crujientes para acompañar el inicio de la visita.', 16000]],
            'Platos fuertes' => [['Bandeja paisa', 'Una selección generosa de sabores tradicionales en nuestra bandeja.', 42000], ['Cazuela de fríjoles', 'Fríjoles de cocción lenta con acompañamientos de la casa.', 32000], ['Carne a la parrilla', 'Carne a la parrilla con guarnición y ensalada de temporada.', 39000], ['Pollo de la casa', 'Pollo preparado al momento con los acompañamientos del día.', 34000]],
            'Bebidas' => [['Limonada natural', 'Limonada preparada al momento, refrescante y equilibrada.', 8000], ['Jugo de temporada', 'Consulta al personal las frutas disponibles del día.', 9000], ['Café colombiano', 'Una taza de café para disfrutar sin afán.', 6000]],
            'Postres' => [['Postre de la casa', 'Un cierre dulce para compartir después de la comida.', 14000], ['Dulce tradicional', 'Nuestra selección de dulce para acompañar un buen café.', 11000]],
        ];
        foreach ($data as $category => $rows) {
            $c = Categoria::firstOrCreate(['nombre' => $category], ['activa' => true]);
            foreach ($rows as [$name,$desc,$price]) {
                Producto::firstOrCreate(['nombre' => $name, 'categoria_id' => $c->id], ['descripcion' => $desc, 'precio_centavos' => $price * 100, 'stock' => null, 'activo' => true]);
            }
        }
    }
}
