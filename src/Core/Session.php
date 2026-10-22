<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper over $_SESSION. In the CLI (tests) $_SESSION is a plain array,
 * so the same code runs without a real session.
 */
final class Session
{
    public static function start(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_name('partsdesk_session');
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => self::isHttps(),
            ]);
            session_start();
        }
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        // Flash data from the previous request becomes readable now and is cleared afterwards
        $_SESSION['_errors_now'] = $_SESSION['_errors'] ?? [];
        $_SESSION['_old_now'] = $_SESSION['_old'] ?? [];
        $_SESSION['_flash_now'] = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old'], $_SESSION['_flash']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** A message shown once on the next page (type: success | error | info). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    /** Flash messages for the current page, also including ones set during this request. */
    public static function flashes(): array
    {
        $messages = array_merge($_SESSION['_flash_now'] ?? [], $_SESSION['_flash'] ?? []);
        // Shown now, so don't show them again on the next page
        unset($_SESSION['_flash_now'], $_SESSION['_flash']);

        return $messages;
    }

    public static function flashErrors(array $errors, array $oldInput): void
    {
        unset($oldInput['_token'], $oldInput['password'], $oldInput['password_confirmation']);
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = $oldInput;
    }

    public static function regenerate(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            session_destroy();
        }
    }

    private static function isHttps(): bool
    {
        return ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
