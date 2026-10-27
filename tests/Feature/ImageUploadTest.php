<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private function photo()
    {
        return UploadedFile::fake()->createWithContent('plato.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0ioAAAAASUVORK5CYII='));
    }

    public function test_image_create_replace_keep_and_validate()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['rol' => 'administrador']);
        $c = Categoria::create(['nombre' => 'Prueba']);
        $d = ['nombre' => 'Plato', 'descripcion' => 'Detalle', 'categoria_id' => $c->id, 'precio' => '12000', 'stock' => 5, 'activo' => 1];
        $this->actingAs($admin)->postJson('/productos', [...$d, 'imagen' => $this->photo()])->assertCreated();
        $p = Producto::firstOrFail();
        $first = $p->imagen;
        Storage::disk('public')->assertExists($first);
        $this->putJson('/productos/'.$p->id, [...$d, 'nombre' => 'Cambio'])->assertOk();
        $this->assertSame($first, $p->fresh()->imagen);
        $this->putJson('/productos/'.$p->id, [...$d, 'imagen' => $this->photo()])->assertOk();
        $second = $p->fresh()->imagen;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $this->get('/menu')->assertOk()->assertSee('imagenes-productos/'.basename($second));
        $this->get('/menu/'.$p->id)->assertOk()->assertSee('imagenes-productos/'.basename($second));
        $this->putJson('/productos/'.$p->id, [...$d, 'imagen' => UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')])->assertUnprocessable()->assertJsonValidationErrors('imagen');
        $this->assertSame($second, $p->fresh()->imagen);
        $this->get('/imagenes-productos/'.basename($second))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs(User::factory()->create(['rol' => 'cliente']))->postJson('/productos', [...$d, 'imagen' => $this->photo()])->assertForbidden();
        $this->actingAs($admin)->putJson('/productos/'.$p->id, [...$d, 'quitar_imagen' => 1])->assertOk();
        $this->assertNull($p->fresh()->imagen);
        Storage::disk('public')->assertMissing($second);
        $this->get('/imagenes-productos/'.basename($second))->assertNotFound();

    }
}
