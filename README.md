# Actualización con fotografías de productos

Esta entrega ya incluye los cambios: no necesitas pegar código manualmente. En la carpeta exterior puedes ejecutar `INICIAR_WINDOWS.bat`; también puedes usar los comandos indicados abajo.

Si conservas tu instalación anterior, copia su `.env`, `database/database.sqlite` y `storage/app/public` dentro de `embajada-paisa` antes de iniciar. Tus cuentas y contraseñas se mantienen.

# La Embajada Paisa · Restaurante y POS

Actualización del proyecto **Embajada_Paisa_Sprint_1_Laravel.zip**. Se conservan Laravel 12, PHP 8.2+, Blade, SQLite, autenticación, roles, clientes y mesas. Se integran los **22 requerimientos RF-01 a RF-22 de las capturas**, cocina, caja, reportes y auditoría.

## Actualizar tu proyecto existente

1. Detén el servidor anterior y conserva una copia completa de su carpeta.
2. Descomprime este ZIP en **otra carpeta**. Copia desde la anterior tu `.env` y tu `database/database.sqlite` a los mismos lugares en la carpeta nueva. Si configuraste otra ruta SQLite, conserva ese archivo y su ruta.
3. Abre la terminal en la carpeta nueva `embajada-paisa`, donde está `artisan`:

```bash
php actualizar.php
php artisan serve
```

El actualizador respalda SQLite y `.env` en `backups/` antes de migrar; no cambia la clave ni las contraseñas. No utiliza `migrate:fresh`. **Tus cuentas conservan sus contraseñas actuales.** Consulta [la guía de actualización](docs/ACTUALIZAR.md) para rutas personalizadas y restauración.

Si descargaste la **versión ligera**, ejecuta primero `composer install` para recuperar `vendor`. La versión completa ya incluye las dependencias de desarrollo. No necesitas Node, npm ni compilar el frontend.

## Instalación nueva para demostrar

```bash
php preparar.php --demo
php artisan serve
```

Abre http://127.0.0.1:8000. Se crean cuentas ficticias, clientes, 8 mesas, 4 categorías y 12 productos ilustrativos. No se crean pedidos ni cobros automáticamente.

| Rol | Correo demo | Contraseña demo |
|---|---|---|
| Administrador | admin@embajada.test | Embajada2026! |
| Personal / trabajador | trabajador@embajada.test | Embajada2026! |
| Cliente | cliente@embajada.test | Embajada2026! |

Estas credenciales solo corresponden a cuentas demo creadas por el instalador; no sustituyen las de una base existente. Para añadir únicamente el catálogo ficticio a una instalación actualizada: `php artisan db:seed --class=PosDemoSeeder`. Conserva productos ya existentes. Para una instalación sin demostración: `php preparar.php`, configura `EMB_ADMIN_EMAIL` y `EMB_ADMIN_PASSWORD` en `.env`, ejecuta `php artisan db:seed` y después retira esa contraseña del archivo.

En XAMPP puedes usar `C:\xampp\php\php.exe` en lugar de `php`. Habilita `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo` y `dom`. SQLite no necesita iniciar MySQL. La base activa se configura en **`.env`**; `.env.example` es solo la plantilla. Esta entrega mantiene SQLite como motor.

## Fotos de productos y mejora visual

Productos permite subir JPG, PNG y WEBP de hasta 2 MB, previsualizar, reemplazar o quitar la foto. La imagen se muestra en la carta, detalle, carrito y listado administrativo. Una migración añade `imagen` sin borrar registros. Las fotografías se sirven mediante una ruta pública acotada; funciona en Windows sin crear enlaces simbólicos.

Para actualizar, conserva también tu carpeta `storage/app/public` junto con `.env` y SQLite. Si ya creaste manualmente una migración de imagen, la nueva comprueba si existe la columna. Las dos imágenes gastronómicas incluidas son ilustrativas, generadas para esta interfaz; puedes sustituir las fotos de los productos desde Productos.

## Qué se agregó

- Registro público de clientes; menú, detalle, filtros y carrito persistente.
- Pedidos con precios calculados por servidor, existencias opcionales, seguimiento e historial de estados; panel de cocina.
- Reservas con disponibilidad por fecha/hora, capacidad, detección de cruces, edición y cancelación.
- Gestión de productos/categorías, clientes, usuarios y mesas; permisos en servidor.
- Caja con apertura, registro de cobros, cambio, cierre y diferencias; descuentos y anulaciones con motivo.
- Reportes por período, exportación CSV, comprobante interno imprimible y auditoría.
- Información pública y ubicación del restaurante, configurables por administrador.

Los cobros en efectivo, tarjeta y transferencia se **registran** después de recibir/verificar el pago; no se conecta una pasarela bancaria. El comprobante es interno, no una factura electrónica. Configura dirección, teléfono, horarios y coordenadas reales en **Configuración**; no se inventaron esos datos.

## Tema vinotinto

Los tokens están al inicio de `public/css/app.css`: primario `#4A0E1C`, secundario `#7B1E2B`, acento `#C9A227`, fondo oscuro `#1A0A0F`, fondo claro `#FAF7F5` y textos `#F5F0EC` / `#2B1B1F`. Todos los módulos comparten esos tokens y el layout Blade. Playfair Display e Inter se sirven desde `public/fonts/` con sus licencias, sin depender de Google Fonts al ejecutar. Incluye navegación móvil, foco de teclado y reducción de animaciones según la preferencia del sistema.

## Cómo comprobarlo

```bash
php artisan test
php vendor/bin/pint --test
php artisan view:cache
```

La suite usa SQLite en memoria separada de tus datos. Resultado de entrega: **96 pruebas, 420 aserciones**, comprobación de actualización sobre el ZIP original y revisión en Chromium a 1440, 834 y 390 px.

Documentación: [checklist RF-01–22](docs/CHECKLIST_REQUERIMIENTOS.md), [recorrido de demostración](docs/GUIA_DEMOSTRACION.md), [arquitectura y API](docs/ARQUITECTURA_Y_API.md), [verificación y límites](docs/VERIFICACION.md).
