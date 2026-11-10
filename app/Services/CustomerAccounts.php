<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class CustomerAccounts
{
    public function createVerifiedCustomer(string $name, string $email): User
    {
        // El controlador agrupa el alta, la ficha y la identidad externa en una transacción.
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Str::random(64),
            'rol' => 'cliente',
            'activo' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $names = preg_split('/\s+/u', trim($name), 2);
        $user->cliente()->create([
            'nombre' => $names[0],
            'apellido' => $names[1] ?? '',
        ]);

        return $user;
    }
}
