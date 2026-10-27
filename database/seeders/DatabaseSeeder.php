<?php

namespace Database\Seeders;

use App\Models\User;
use App\Rules\BcryptPasswordLength;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Evita sobreescribir contraseñas al ejecutar el seeder de nuevo.
        if (User::where('rol', 'administrador')->where('activo', true)->exists()) {
            $this->command?->info('Ya existe un administrador activo; no se modifica.');

            return;
        }
        $data = Validator::make([
            'name' => env('EMB_ADMIN_NAME', 'Administrador'),
            'email' => mb_strtolower(trim((string) env('EMB_ADMIN_EMAIL'))),
            'password' => env('EMB_ADMIN_PASSWORD'),
        ], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'max:72', Password::min(10)->mixedCase()->numbers(), new BcryptPasswordLength],
        ])->validate();
        User::create([...$data, 'rol' => 'administrador', 'activo' => true]);
        $this->command?->info('Administrador inicial creado. Retira EMB_ADMIN_PASSWORD del archivo .env.');
    }
}
