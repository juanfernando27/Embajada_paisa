<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Los datos demo solo se permiten en los entornos local o testing.');
        }
        DB::transaction(function (): void {
            foreach ([
                ['Administrador demo', 'admin@embajada.test', 'administrador'],
                ['Recepción demo', 'trabajador@embajada.test', 'trabajador'],
                ['Servicio uno demo', 'servicio1@embajada.test', 'trabajador'],
                ['Servicio dos demo', 'servicio2@embajada.test', 'trabajador'],
                ['Servicio tres demo', 'servicio3@embajada.test', 'trabajador'],
                ['Servicio cuatro demo', 'servicio4@embajada.test', 'trabajador'],
                ['Camila Demo', 'cliente@embajada.test', 'cliente'],
            ] as [$name, $email, $rol]) {
                User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'Embajada2026!', 'rol' => $rol, 'activo' => true]);
            }
            foreach ([['Camila', 'Rojas'], ['Andrés', 'Molina'], ['Mariana', 'Torres'], ['Julián', 'Pérez'], ['Valentina', 'López'], ['Daniel', 'Gómez'], ['Sofía', 'Martínez'], ['Mateo', 'Castro'], ['Lucía', 'Vargas'], ['Nicolás', 'Ríos'], ['Isabela', 'Ortiz'], ['Santiago', 'Ruiz']] as $i => [$nombre,$apellido]) {
                Cliente::firstOrCreate(['numero_documento' => 'DEMO'.(1001 + $i)], [
                    'tipo_documento' => 'CC', 'nombre' => $nombre, 'apellido' => $apellido,
                    'email' => 'persona'.($i + 1).'@example.test', 'telefono' => '300000'.str_pad((string) ($i + 101), 4, '0', STR_PAD_LEFT),
                ]);
            }
            $account = User::where('email', 'cliente@embajada.test')->firstOrFail();
            if ($account->rol === 'cliente' && ! $account->cliente) {
                Cliente::where('numero_documento', 'DEMO1001')->whereNull('user_id')->update(['user_id' => $account->id]);
            }
            foreach ([['Salón principal', 4], ['Salón principal', 4], ['Salón principal', 2], ['Salón principal', 6], ['Terraza', 4], ['Terraza', 2], ['Salón familiar', 8], ['Salón familiar', 6]] as $i => [$ubicacion,$capacidad]) {
                Mesa::firstOrCreate(['numero' => $i + 1], ['capacidad' => $capacidad, 'ubicacion' => $ubicacion, 'habilitada' => $i !== 7, 'descripcion' => $i === 7 ? 'Temporalmente fuera de servicio.' : null]);
            }
        });
        $this->call(PosDemoSeeder::class);
        $this->command?->info('Demostración lista. Todos los registros son ficticios. Las cuentas existentes se conservan.');
    }
}
