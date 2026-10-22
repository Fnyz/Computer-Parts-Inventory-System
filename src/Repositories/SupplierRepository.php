<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Paginator;

final class SupplierRepository
{
    public const FIELDS = ['name', 'contact_person', 'phone', 'email', 'address', 'notes'];

    public function paginate(string $search, bool $archived, int $page): array
    {
        $where = [$archived ? 's.archived_at IS NOT NULL' : 's.archived_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = '(s.name LIKE ? OR s.contact_person LIKE ? OR s.phone LIKE ? OR s.email LIKE ?)';
            array_push($params, ...array_fill(0, 4, '%' . $search . '%'));
        }

        return Paginator::run(
            'SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.id AND p.archived_at IS NULL) AS product_count FROM suppliers s',
            'FROM suppliers s',
            implode(' AND ', $where),
            $params,
            's.name',
            $page,
        );
    }

    /** @return array<int, string> active suppliers for select boxes */
    public function options(): array
    {
        return Database::connection()->query('SELECT id, name FROM suppliers WHERE archived_at IS NULL ORDER BY name')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $values = $this->values($data);
        Database::connection()->prepare(
            'INSERT INTO suppliers (name, contact_person, phone, email, address, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        )->execute([...array_values($values), now(), now()]);

        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $values = $this->values($data);
        Database::connection()->prepare(
            'UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?, notes = ?, updated_at = ? WHERE id = ?',
        )->execute([...array_values($values), now(), $id]);
    }

    public function setArchived(int $id, bool $archived): void
    {
        Database::connection()->prepare('UPDATE suppliers SET archived_at = ?, updated_at = ? WHERE id = ?')
            ->execute([$archived ? now() : null, now(), $id]);
    }

    private function values(array $data): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $values[$field] = $value === '' && $field !== 'name' ? null : $value;
        }

        return $values;
    }
}
