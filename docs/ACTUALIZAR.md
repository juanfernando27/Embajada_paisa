# Actualizar conservando tu proyecto

## Qué conservar

El archivo real de configuración es **`.env`**, no `.env.example`. En SQLite la base predeterminada es **`database/database.sqlite`** dentro de la carpeta del proyecto. Si `.env` contiene `DB_DATABASE`, verifica esa ruta: puede apuntar a otro archivo. Conserva ese archivo y cualquier archivo complementario `-wal`/`-shm` al hacer una copia en reposo; el respaldo automático posterior usa la conexión SQLite y genera una copia consistente.

No se incluye una base con operaciones de prueba ni un `.env` en el ZIP nuevo, para que esos archivos no sustituyan los tuyos.

## Procedimiento recomendado

1. Cierra el servidor `php artisan serve` anterior con Ctrl+C. Evita que otras personas usen esa instalación durante la actualización.
2. Copia **toda la carpeta anterior** a un respaldo fuera de la carpeta nueva.
3. Descomprime el ZIP actualizado en una ubicación diferente; no combines carpetas que contengan vistas compiladas o caché antigua.
4. Copia tu `.env`, tu archivo SQLite y la carpeta `storage/app/public` al proyecto nuevo. Mantén la misma `APP_KEY`. Si usabas una ruta SQLite absoluta y quieres trabajar sobre una copia, cambia solo `DB_DATABASE` para que apunte a la copia correcta.
5. Comprueba que `DB_CONNECTION=sqlite`. Si tu configuración tiene otro motor, detén el procedimiento: este actualizador comprueba SQLite y no cambia de motor.
6. Si elegiste el ZIP ligero, ejecuta `composer install` desde la carpeta con `composer.json`.
7. Ejecuta:

```bash
php actualizar.php
php artisan serve
```

`actualizar.php` limpia la caché de configuración, abre la conexión actual, copia `.env` y genera `backups/FECHA_ALEATORIO/database.sqlite` mediante `VACUUM INTO`. **Solo continúa si el respaldo se creó correctamente.** Después ejecuta `artisan migrate` y limpia las vistas compiladas. No genera otra clave, no cambia contraseñas, no elimina filas anteriores y no recrea tablas existentes. Repetirlo no duplica migraciones.

Comprueba que sigan visibles tus clientes, mesas y usuarios. Usa tus contraseñas actuales. Entra como administrador y crea categorías/productos, o añade solo la carta de demostración:

```bash
php artisan db:seed --class=PosDemoSeeder
```

Este seeder no crea usuarios, no modifica contraseñas y no cambia precios ni stock de productos ya existentes con el mismo nombre. Solo funciona en local/testing. Para actualizar sin catálogo de ejemplo basta `php actualizar.php`.

## Respaldo y restauración

`backups/` contiene datos y configuración privados. Mantén esa carpeta fuera del acceso web; el servidor debe exponer únicamente `public/`. Conserva también la copia completa del código anterior hecha al inicio.

Si necesitas volver atrás, detén el servidor, vuelve a la copia del código anterior y restaura su `.env` junto con la base SQLite respaldada. Usa un respaldo anterior a la ampliación. No mezcles una base restaurada con movimientos nuevos ni ejecutes `migrate:fresh`, `db:wipe` o borrados manuales para actualizar.

## Problemas frecuentes

- **Faltan tablas nuevas:** ejecuta los comandos desde la carpeta nueva, comprueba `.env`, corre `php artisan config:clear` y `php artisan migrate:status`. El actualizador debe terminar sin errores.
- **Could not find driver:** activa `pdo_sqlite` y `sqlite3` en el `php.ini` mostrado por `php --ini`.
- **VACUUM INTO no disponible:** se necesita SQLite 3.27 o posterior. No se migra sin respaldo; actualiza SQLite/PHP y repite.
- **Database is locked:** detén otras conexiones/escrituras y repite; revisa que el archivo esté en almacenamiento local con permisos de escritura.
- **Cuenta demo no entra:** si ya existía, su clave se conserva. El administrador puede restablecerla en Usuarios y roles.
- **Error 419:** recarga el formulario e inicia sesión otra vez. Comprueba `APP_URL=http://127.0.0.1:8000` sin formato Markdown y no cambies `APP_KEY`.

La actualización fue probada sobre una instalación creada con el ZIP original: contenido de todas sus tablas, contraseñas, archivo `.env` y migraciones anteriores sin cambios; respaldo íntegro y segunda ejecución correcta.
