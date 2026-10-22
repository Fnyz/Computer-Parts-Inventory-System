<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/** One shared PDO connection per request. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect();
        }

        return self::$pdo;
    }

    /** Lets tests swap in their own connection. */
    public static function setConnection(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Runs $work inside a DB transaction; rolls back if it throws.
     *
     * @template T
     * @param callable(PDO): T $work
     * @return T
     */
    public static function transaction(callable $work): mixed
    {
        $pdo = self::connection();
        if ($pdo->inTransaction()) {
            return $work($pdo);
        }
        $pdo->beginTransaction();
        try {
            $result = $work($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function connect(?string $database = null): PDO
    {
        $socket = env('DB_SOCKET');
        $dsn = $socket
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $socket, $database ?? env('DB_NAME', 'partsdesk'))
            : sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'),
                env('DB_PORT', '3306'),
                $database ?? env('DB_NAME', 'partsdesk'),
            );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        // Managed MySQL (e.g. Aiven) requires TLS with the provider's CA certificate
        $ca = self::caFile();
        if ($ca !== null) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
        }

        $pdo = new PDO($dsn, (string) env('DB_USER', 'root'), (string) env('DB_PASS', ''), $options);
        $pdo->exec("SET time_zone = '" . date('P') . "'");

        return $pdo;
    }

    /**
     * DB_SSL_CA may be a file path, or the certificate text itself
     * (easier to paste into a hosting dashboard). Text is written to a temp file.
     */
    private static function caFile(): ?string
    {
        $ca = env('DB_SSL_CA');
        if ($ca === null) {
            return null;
        }
        if (!str_contains($ca, 'BEGIN CERTIFICATE')) {
            return $ca;
        }
        $path = sys_get_temp_dir() . '/partsdesk-db-ca-' . md5($ca) . '.pem';
        if (!is_file($path)) {
            file_put_contents($path, str_replace('\n', "\n", $ca));
        }

        return $path;
    }
}
