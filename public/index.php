<?php

declare(strict_types=1);

// Single entry point: every request goes through here.

if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

// PHP's built-in server (composer serve): let it serve real files such as CSS and images directly,
// but only files that really are inside public/
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && !str_ends_with($file, '.php') && !str_starts_with(basename($file), '.')) {
        return false;
    }
}

require BASE_DIR . '/vendor/autoload.php';

App\Core\App::bootstrap();
App\Core\App::create()->handle(App\Core\Request::fromGlobals())->send();
