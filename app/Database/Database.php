<?php
require_once __DIR__ . '/../../config.php';

class Database
{
    // One shared connection per request instead of a new one for every query
    private static $pdo = null;

    public function getConnect()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', 'localhost'),
            env('DB_PORT', '3306'),
            env('DB_NAME', 'inventorymanagementsystem')
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        // Managed MySQL (e.g. Aiven) requires TLS with the provider's CA certificate
        $ca = env('DB_SSL_CA');
        if ($ca) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
        }

        try {
            self::$pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('The system is temporarily unavailable. Please try again later.');
        }

        return self::$pdo;
    }
}
