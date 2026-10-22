<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Repositories\UserRepository;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    /** The signed-in, active user, or null. */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            $user = is_int($id) ? (new UserRepository())->find($id) : null;
            // A deactivated account is signed out on its next request
            self::$user = $user !== null && (int) $user['is_active'] === 1 ? $user : null;
            if ($user !== null && self::$user === null) {
                Session::forget('user_id');
            }
        }

        return self::$user;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function role(): string
    {
        return (string) (self::user()['role'] ?? '');
    }

    public static function can(string $ability): bool
    {
        $user = self::user();

        return $user !== null && Permissions::allows($user['role'], $ability);
    }

    /** Checks the password and signs the user in. Returns false for wrong or inactive accounts. */
    public static function attempt(string $username, string $password): bool
    {
        $users = new UserRepository();
        $user = $users->findByUsername($username);
        if ($user === null || (int) $user['is_active'] !== 1 || $password === '' || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $users->setPasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        $users->touchLogin((int) $user['id']);
        self::login($user);

        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::forgetCache();
    }

    /** Clears the per-request cache (used by tests that simulate several requests). */
    public static function forgetCache(): void
    {
        self::$user = null;
        self::$loaded = false;
    }
}
