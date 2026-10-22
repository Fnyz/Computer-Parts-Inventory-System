<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CategoryRepository
{
    /** All categories with how many active products each has. */
    public function allWithCounts(): array
    {
        return Database::connection()->query(
            'SELECT c.*, COUNT(p.id) AS product_count
             FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.archived_at IS NULL
             GROUP BY c.id, c.name, c.description, c.spec_fields, c.created_at, c.updated_at
             ORDER BY c.name',
        )->fetchAll();
    }

    /** @return array<int, string> id => name, for select boxes */
    public function options(): array
    {
        return Database::connection()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /** @return array<int, list<string>> id => spec field names, for the product form */
    public function specFieldMap(): array
    {
        $map = [];
        foreach (Database::connection()->query('SELECT id, spec_fields FROM categories')->fetchAll() as $row) {
            $map[(int) $row['id']] = self::fields($row['spec_fields']);
        }

        return $map;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function nameTaken(string $name, int $exceptId = 0): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM categories WHERE name = ? AND id <> ?');
        $stmt->execute([$name, $exceptId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $name, string $description, array $specFields): int
    {
        Database::connection()->prepare(
            'INSERT INTO categories (name, description, spec_fields, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
        )->execute([$name, $description ?: null, json_encode(array_values($specFields)), now(), now()]);

        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, string $name, string $description, array $specFields): void
    {
        Database::connection()->prepare(
            'UPDATE categories SET name = ?, description = ?, spec_fields = ?, updated_at = ? WHERE id = ?',
        )->execute([$name, $description ?: null, json_encode(array_values($specFields)), now(), $id]);
    }

    public function productCount(int $id): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    /** @return list<string> */
    public static function fields(?string $json): array
    {
        $fields = json_decode((string) $json, true);

        return is_array($fields) ? array_values(array_filter(array_map('strval', $fields), static fn (string $f) => $f !== '')) : [];
    }

    /** "Socket, Cores / threads" or one per line -> clean unique list */
    public static function parseFields(string $text): array
    {
        $parts = preg_split('/[\r\n,]+/', $text) ?: [];
        $fields = [];
        foreach ($parts as $part) {
            $part = mb_substr(trim($part), 0, 40);
            if ($part !== '' && !in_array(mb_strtolower($part), array_map('mb_strtolower', $fields), true)) {
                $fields[] = $part;
            }
        }

        return array_slice($fields, 0, 12);
    }
}
