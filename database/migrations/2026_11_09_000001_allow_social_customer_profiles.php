<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->setOptional(true);
    }

    public function down(): void
    {
        if (DB::table('clientes')->whereNull('tipo_documento')->orWhereNull('numero_documento')->orWhereNull('telefono')->exists()) {
            throw new RuntimeException('Completa los datos de las fichas sociales antes de revertir esta migración.');
        }
        $this->setOptional(false);
    }

    private function setOptional(bool $nullable): void
    {
        $postgres = DB::getDriverName() === 'pgsql';
        if ($postgres) {
            DB::statement('ALTER TABLE clientes ALTER COLUMN tipo_documento '.($nullable ? 'DROP' : 'SET').' NOT NULL');
        }
        Schema::table('clientes', function (Blueprint $table) use ($nullable, $postgres): void {
            if (! $postgres) {
                $table->enum('tipo_documento', ['CC', 'CE', 'TI', 'PAS'])->nullable($nullable)->change();
            }
            $table->string('numero_documento', 30)->nullable($nullable)->change();
            $table->string('telefono', 20)->nullable($nullable)->change();
        });
    }
};
