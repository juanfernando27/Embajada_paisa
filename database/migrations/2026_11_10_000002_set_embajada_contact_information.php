<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $restaurant = DB::table('restaurante_config')->where('id', 1);
        $current = $restaurant->first();

        if (! $current) {
            return;
        }

        $defaults = [
            'direccion' => 'Cl 15 #26-70',
            'telefono' => '+57 301 768 3405',
            'horario' => "Lunes a sábado: 8:00 AM – 6:00 PM\nDomingo: 8:00 AM – 4:00 PM",
        ];
        $missing = array_filter($defaults, fn ($value, $field) => blank($current->{$field}), ARRAY_FILTER_USE_BOTH);

        if ($missing !== []) {
            $restaurant->update([...$missing, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Los datos de contacto editados por el administrador se conservan.
    }
};
