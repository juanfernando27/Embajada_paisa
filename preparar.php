<?php

/** Instalación local sin sobrescribir archivos .env ni vaciar bases de datos. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(__DIR__);
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    fwrite(STDERR, "Necesitas PHP 8.2 o superior.\n");
    exit(1);
}
foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'fileinfo', 'dom'] as $ext) {
    if (! extension_loaded($ext)) {
        fwrite(STDERR, "Activa la extensión PHP $ext en php.ini.\n");
        exit(1);
    }
}
if (! is_file('vendor/autoload.php')) {
    fwrite(STDERR, "Faltan dependencias. Ejecuta composer install y repite.\n");
    exit(1);
}
$demo = in_array('--demo', $argv, true);
if ($demo && is_file('.env') && preg_match('/^APP_ENV\s*=\s*["\']?production/m', file_get_contents('.env'))) {
    fwrite(STDERR, "La demostración no se instala en producción.\n");
    exit(1);
}
if (! is_file('.env')) {
    copy('.env.example', '.env');
}
foreach (['bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'database'] as $dir) {
    if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
        fwrite(STDERR, "No se pudo crear $dir.\n");
        exit(1);
    }
}
$env = file_get_contents('.env');
$isSqlite = ! preg_match('/^DB_CONNECTION\s*=\s*["\']?mysql/m', $env);
if ($isSqlite && ! extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "Activa pdo_sqlite para la instalación predeterminada.\n");
    exit(1);
}
if ($isSqlite && ! preg_match('/^DB_DATABASE\s*=\s*[^\r\n]+/m', $env) && ! is_file('database/database.sqlite')) {
    touch('database/database.sqlite');
}
$run = function (array $args): void {
    $cmd = escapeshellarg(PHP_BINARY).' artisan '.implode(' ', array_map('escapeshellarg', $args));
    passthru($cmd, $status);
    if ($status !== 0) {
        exit($status);
    }
};
$run(['config:clear']);
if (! preg_match('/^APP_KEY\s*=\s*["\']?base64:/m', $env)) {
    $run(['key:generate', '--ansi']);
}
$run(['migrate', '--no-interaction']);
if ($demo) {
    $run(['db:seed', '--class=DemoSeeder', '--no-interaction']);
}
echo "\nPreparación terminada. Ejecuta: php artisan serve\n";
if ($demo) {
    echo "Demo: admin@embajada.test / Embajada2026! (solo entorno local).\n";
} else {
    echo "Configura EMB_ADMIN_EMAIL y EMB_ADMIN_PASSWORD en .env y ejecuta php artisan db:seed.\n";
}
