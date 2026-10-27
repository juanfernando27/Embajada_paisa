<?php

namespace App\Observers;

use App\Services\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class LegacyAuditObserver
{
    private function safe(array $d): array
    {
        return Arr::except($d, ['password', 'remember_token', 'email_verified_at']);
    }

    public function created(Model $m): void
    {
        Audit::record('crear', $m, null, $this->safe($m->getAttributes()));
    }

    public function updated(Model $m): void
    {
        Audit::record('actualizar', $m, $this->safe($m->getRawOriginal()), $this->safe($m->getAttributes()), $m->wasChanged('password') ? 'Contraseña restablecida' : null);
    }

    public function deleted(Model $m): void
    {
        Audit::record('eliminar', $m, $this->safe($m->getAttributes()));
    }
}
