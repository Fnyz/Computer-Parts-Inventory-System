<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);

        return $stmt->fetch() ?: null;
    }

    public function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countActiveAdmins(): int
    {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
    }

    public function all(): array
    {
        return Database::connection()->query('SELECT * FROM users ORDER BY is_active DESC, name, username')->fetchAll();
    }

    public function usernameTaken(string $username, int $exceptId = 0): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $stmt->execute([$username, $exceptId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (username, name, email, phone, password_hash, role, is_active, created_at, updated_at)
             VALUES (:username, :name, :email, :phone, :password_hash, :role, 1, :created_at, :updated_at)',
        );
        $stmt->execute([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** Updates profile fields; the password changes only when a new one is given. */
    public function update(int $id, array $data): void
    {
        $fields = ['username', 'name', 'email', 'phone', 'role', 'is_active'];
        $sets = [];
        $values = ['id' => $id, 'now' => now()];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $values[$field] = $data[$field] === '' && in_array($field, ['email', 'phone'], true) ? null : $data[$field];
            }
        }
        if (!empty($data['password'])) {
            $sets[] = 'password_hash = :password_hash';
            $values['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sets[] = 'updated_at = :now';
        Database::connection()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($values);
    }

    public function setPasswordHash(int $id, string $hash): void
    {
        Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    }

    public function setTheme(int $id, string $theme): void
    {
        Database::connection()->prepare('UPDATE users SET theme = ? WHERE id = ?')->execute([$theme, $id]);
    }

    public function touchLogin(int $id): void
    {
        Database::connection()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([now(), $id]);
    }
}
