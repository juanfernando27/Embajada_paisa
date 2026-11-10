<?php

namespace App\Listeners;

use App\Mail\LoginSucceeded;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

class SendLoginNotification
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        if ($event->guard !== 'web' || ! $user instanceof User || ! $user->activo) {
            return;
        }

        $mail = new LoginSucceeded($user->name, now('America/Bogota')->format('d/m/Y H:i').' (Colombia)');
        $email = $user->email;

        defer(function () use ($email, $mail): void {
            try {
                Mail::to($email)->send($mail);
            } catch (Throwable $error) {
                Log::warning('No fue posible enviar el aviso de inicio de sesión.', ['type' => $error::class]);
            }
        });
    }
}
