<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('embajada:estado', function (): void {
    $this->info('La Embajada Paisa · Sprint 1 · Autenticación, roles, clientes, mesas y usuarios.');
})->purpose('Mostrar el alcance del incremento');
