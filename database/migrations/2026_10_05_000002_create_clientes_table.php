<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->enum('tipo_documento', ['CC', 'CE', 'TI', 'PAS']);
            $table->string('numero_documento', 30)->unique();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 150)->nullable()->unique();
            $table->string('telefono', 20);
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['apellido', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
