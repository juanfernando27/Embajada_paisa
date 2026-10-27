<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Actualización local: conserva .env y datos; primero crea una copia consistente de SQLite.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(__DIR__);
if (! is_file('.env') || ! is_file('vendor/autoload.php')) {
    fwrite(STDERR, "Falta .env o vendor. Para una instalación nueva usa php preparar.php --demo.\n");
    exit(1);
}
$run = function (array $args): void {
    passthru(escapeshellarg(PHP_BINARY).' artisan '.implode(' ', array_map('escapeshellarg', $args)), $status);
    if ($status !== 0) {
        exit($status);
    }
};
$run(['config:clear']);
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
try {
    if (config('database.default') !== 'sqlite') {
        throw new RuntimeException('Este actualizador conserva SQLite. La conexión configurada usa otro motor; respáldala antes de ejecutar artisan migrate.');
    }
    $pdo = DB::connection()->getPdo();
    $folder = __DIR__.'/backups/'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
    if (! mkdir($folder, 0700, true)) {
        throw new RuntimeException('No se pudo crear el respaldo. No se aplicó la actualización.');
    }
    if (! copy('.env', $folder.'/.env')) {
        throw new RuntimeException('No se pudo respaldar .env. No se aplicó la actualización.');
    }
    chmod($folder.'/.env', 0600);
    $pdo->exec('VACUUM INTO '.$pdo->quote($folder.'/database.sqlite'));
    echo 'Respaldo consistente creado en backups/'.basename($folder).".\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
$run(['migrate', '--no-interaction']);
$run(['view:clear']);
if (in_array('--demo', $argv, true)) {
    $run(['db:seed', '--class=PosDemoSeeder', '--no-interaction']);
}
echo "Actualización lista. La clave, usuarios y datos anteriores se conservan.\n";
