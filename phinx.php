<?php

declare(strict_types=1);

// Phinx (database migrations) configuration. Uses the same .env / environment variables as the app,
// including Aiven's SSL certificate, by sharing the app's PDO connection.

require __DIR__ . '/vendor/autoload.php';

App\Core\App::bootstrap();

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinx_migrations',
        'default_environment' => 'app',
        'app' => [
            'name' => env('DB_NAME', 'partsdesk'),
            'connection' => App\Core\Database::connection(),
        ],
    ],
    'version_order' => 'creation',
];
