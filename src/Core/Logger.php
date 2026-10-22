<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;

final class Logger
{
    private static ?Monolog $logger = null;

    public static function get(): Monolog
    {
        if (self::$logger === null) {
            // On Render, logs go to stderr and show in the dashboard; locally they go to storage/logs
            $target = env('LOG_CHANNEL') === 'stderr' ? 'php://stderr' : BASE_DIR . '/storage/logs/app.log';
            self::$logger = new Monolog('partsdesk');
            self::$logger->pushHandler(new StreamHandler($target, env('APP_DEBUG') === 'true' ? Level::Debug : Level::Info));
        }

        return self::$logger;
    }
}
