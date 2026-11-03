#!/bin/sh
# Se ejecuta cada vez que arranca el contenedor.
set -e

# Render indica el puerto en la variable PORT (por defecto 10000): Apache debe escuchar ahí.
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Si no se configuró APP_KEY, se genera una (cambia en cada arranque; ver README).
if [ -z "$APP_KEY" ]; then
    export APP_KEY="$(php artisan key:generate --show)"
fi

# Con SQLite, el archivo de la base de datos se crea aquí.
if [ "$DB_CONNECTION" = "sqlite" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
    chown www-data:www-data "$DB_DATABASE" "$(dirname "$DB_DATABASE")"
fi

# Enlace para los archivos subidos (si ya existe, no pasa nada)
php artisan storage:link >/dev/null 2>&1 || true

# Crea las tablas que falten. Para no hacerlo, defina RUN_MIGRATIONS=false.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Datos iniciales (seeders): solo si define RUN_SEEDERS=true. Si falla, la aplicación arranca igual
# (el error queda en los logs). Ojo: los seeders que usan Faker/factories necesitan Faker en "require".
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force || echo "AVISO: los seeders fallaron (revise el error de arriba)."
fi

exec apache2-foreground
