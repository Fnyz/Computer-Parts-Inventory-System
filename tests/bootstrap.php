<?php

declare(strict_types=1);

// Recreates the test database and runs all migrations once per test run.

require dirname(__DIR__) . '/vendor/autoload.php';

App\Core\App::bootstrap();

$name = (string) env('DB_NAME');
if (!str_contains($name, 'test')) {
    fwrite(STDERR, "Refusing to run: DB_NAME '{$name}' does not look like a test database.\n");
    exit(1);
}

$server = App\Core\Database::connect('');
$server->exec("DROP DATABASE IF EXISTS `{$name}`");
$server->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
unset($server);

$output = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_DIR . '/vendor/bin/phinx') . ' migrate -c ' . escapeshellarg(BASE_DIR . '/phinx.php') . ' 2>&1', $output, $code);
if ($code !== 0) {
    fwrite(STDERR, "Migrations failed:\n" . implode("\n", $output) . "\n");
    exit(1);
}
