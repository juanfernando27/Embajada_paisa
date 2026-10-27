<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_locks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('version')->default(0);
        });
        DB::table('pos_locks')->insert(['id' => 1, 'version' => 0]);
        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 80)->unique();
            $t->boolean('activa')->default(true);
            $t->timestamps();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $t->string('nombre', 120);
            $t->text('descripcion');
            $t->string('alergenos', 500)->nullable();
            $t->unsignedBigInteger('precio_centavos');
            $t->unsignedInteger('stock')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $t->index(['activo', 'categoria_id']);
        });
        Schema::create('carrito_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $t->unsignedSmallInteger('cantidad');
            $t->timestamps();
            $t->unique(['user_id', 'producto_id']);
        });
        Schema::create('metodos_pago', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 30)->unique();
            $t->string('nombre', 60);
            $t->boolean('activo')->default(true);
        });
        DB::table('metodos_pago')->insert([['codigo' => 'efectivo', 'nombre' => 'Efectivo', 'activo' => 1], ['codigo' => 'tarjeta', 'nombre' => 'Tarjeta', 'activo' => 1], ['codigo' => 'transferencia', 'nombre' => 'Transferencia', 'activo' => 1]]);
        Schema::create('pedidos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('cliente_id')->nullable()->constrained('clientes')->restrictOnDelete();
            $t->foreignId('mesa_id')->nullable()->constrained('mesas')->restrictOnDelete();
            $t->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $t->uuid('token');
            $t->string('estado', 30)->default('pendiente');
            $t->string('estado_pago', 20)->default('pendiente');
            $t->unsignedBigInteger('subtotal_centavos');
            $t->unsignedBigInteger('descuento_centavos')->default(0);
            $t->unsignedBigInteger('total_centavos');
            $t->string('notas', 500)->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'token']);
            $t->index(['estado', 'created_at']);
        });
        Schema::create('pedido_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $t->string('nombre', 120);
            $t->unsignedSmallInteger('cantidad');
            $t->unsignedBigInteger('precio_centavos');
            $t->unsignedBigInteger('total_centavos');
        });
        Schema::create('pedido_estados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('anterior', 30)->nullable();
            $t->string('nuevo', 30);
            $t->string('motivo', 500)->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('reservas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $t->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $t->dateTime('inicio');
            $t->dateTime('fin');
            $t->unsignedSmallInteger('comensales');
            $t->string('estado', 30)->default('confirmada');
            $t->string('notas', 500)->nullable();
            $t->timestamps();
            $t->index(['mesa_id', 'inicio', 'fin']);
        });
        Schema::create('caja_turnos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('cerrado_por')->nullable()->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('inicial_centavos');
            $t->unsignedBigInteger('esperado_centavos')->nullable();
            $t->unsignedBigInteger('contado_centavos')->nullable();
            $t->bigInteger('diferencia_centavos')->nullable();
            $t->timestamp('abierto_at');
            $t->timestamp('cerrado_at')->nullable();
            $t->string('notas', 500)->nullable();
        });
        Schema::create('pagos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->unique()->constrained('pedidos')->restrictOnDelete();
            $t->foreignId('caja_turno_id')->constrained('caja_turnos')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('metodo_pago_id')->constrained('metodos_pago')->restrictOnDelete();
            $t->unsignedBigInteger('monto_centavos');
            $t->unsignedBigInteger('recibido_centavos');
            $t->unsignedBigInteger('cambio_centavos');
            $t->string('referencia', 100)->nullable();
            $t->timestamps();
        });
        Schema::create('auditorias', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('accion', 80);
            $t->string('entidad', 80);
            $t->unsignedBigInteger('entidad_id');
            $t->json('antes')->nullable();
            $t->json('despues')->nullable();
            $t->string('motivo', 500)->nullable();
            $t->timestamp('created_at');
            $t->index(['entidad', 'entidad_id']);
        });
        Schema::create('restaurante_config', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120);
            $t->string('ciudad', 100);
            $t->string('direccion', 200)->nullable();
            $t->string('telefono', 30)->nullable();
            $t->string('horario', 300)->nullable();
            $t->text('descripcion')->nullable();
            $t->decimal('latitud', 10, 7)->nullable();
            $t->decimal('longitud', 10, 7)->nullable();
            $t->timestamps();
        });
        DB::table('restaurante_config')->insert(['id' => 1, 'nombre' => 'La Embajada Paisa', 'ciudad' => 'Pasto, Nariño', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        foreach (['restaurante_config', 'auditorias', 'pagos', 'caja_turnos', 'reservas', 'pedido_estados', 'pedido_items', 'pedidos', 'metodos_pago', 'carrito_items', 'productos', 'categorias', 'pos_locks'] as $n) {
            Schema::dropIfExists($n);
        }
    }
};
