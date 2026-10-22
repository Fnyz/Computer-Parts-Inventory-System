<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;

/** Blocks sign-in for a username + IP after too many wrong passwords in a short time. */
final class LoginThrottle
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_MINUTES = 15;

    public function tooManyAttempts(string $username, string $ip): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE username = ? AND ip = ? AND attempted_at > ?',
        );
        $stmt->execute([$username, $ip, date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60)]);

        return (int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    public function recordFailure(string $username, string $ip): void
    {
        Database::connection()
            ->prepare('INSERT INTO login_attempts (username, ip, attempted_at) VALUES (?, ?, ?)')
            ->execute([$username, $ip, now()]);
    }

    public function clear(string $username, string $ip): void
    {
        Database::connection()->prepare('DELETE FROM login_attempts WHERE username = ? AND ip = ?')->execute([$username, $ip]);
    }
}
