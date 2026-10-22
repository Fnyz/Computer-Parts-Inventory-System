<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Paginator;

final class ProductRepository
{
    private const SELECT = 'SELECT p.*, c.name AS category_name, s.name AS supplier_name
        FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN suppliers s ON s.id = p.supplier_id';
    private const FROM = 'FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN suppliers s ON s.id = p.supplier_id';

    public const SORTS = [
        'name' => 'p.name ASC',
        'sku' => 'p.sku ASC',
        'stock' => 'p.qty_on_hand ASC, p.name ASC',
        'price' => 'p.selling_price DESC, p.name ASC',
        'newest' => 'p.created_at DESC',
    ];

    /**
     * @param array{q?: string, category?: int, supplier?: int, status?: string, archived?: bool, sort?: string} $filters
     */
    public function paginate(array $filters, int $page, int $perPage = Paginator::PER_PAGE): array
    {
        [$where, $params] = $this->where($filters);

        return Paginator::run(self::SELECT, self::FROM, $where, $params, self::SORTS[$filters['sort'] ?? 'name'] ?? self::SORTS['name'], $page, $perPage);
    }

    /** Same filters, no paging (for CSV export). */
    public function all(array $filters = []): array
    {
        [$where, $params] = $this->where($filters);
        $stmt = Database::connection()->prepare(self::SELECT . ($where ? ' WHERE ' . $where : '') . ' ORDER BY ' . (self::SORTS[$filters['sort'] ?? 'name'] ?? self::SORTS['name']));
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE p.id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function findBySku(string $sku): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE p.sku = ?');
        $stmt->execute([$sku]);

        return $stmt->fetch() ?: null;
    }

    public function skuTaken(string $sku, int $exceptId = 0): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM products WHERE sku = ? AND id <> ?');
        $stmt->execute([$sku, $exceptId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Active products for the POS screen and purchase-order lines. */
    public function sellable(): array
    {
        return Database::connection()->query(
            'SELECT p.id, p.sku, p.name, p.brand, p.selling_price, p.cost_price, p.qty_on_hand, p.reorder_level, p.image_url, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.archived_at IS NULL ORDER BY p.name',
        )->fetchAll();
    }

    /** Active products at or below their reorder level. */
    public function lowStock(int $limit = 50): array
    {
        $stmt = Database::connection()->prepare(
            self::SELECT . ' WHERE p.archived_at IS NULL AND p.qty_on_hand <= p.reorder_level
             ORDER BY (p.qty_on_hand > 0), CAST(p.qty_on_hand AS SIGNED) - CAST(p.reorder_level AS SIGNED), p.name LIMIT ' . max(1, $limit),
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function lowStockCount(): int
    {
        return (int) Database::connection()->query(
            'SELECT COUNT(*) FROM products WHERE archived_at IS NULL AND qty_on_hand <= reorder_level',
        )->fetchColumn();
    }

    public function create(array $data): int
    {
        $values = $this->values($data);
        $columns = array_keys($values);
        Database::connection()->prepare(
            'INSERT INTO products (' . implode(', ', $columns) . ', qty_on_hand, created_at, updated_at)
             VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ', 0, ?, ?)',
        )->execute([...array_values($values), now(), now()]);

        return (int) Database::connection()->lastInsertId();
    }

    /** Updates details. Quantity is never set here: it changes only through StockService. */
    public function update(int $id, array $data): void
    {
        $values = $this->values($data);
        $sets = implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($values)));
        Database::connection()->prepare("UPDATE products SET {$sets}, updated_at = ? WHERE id = ?")
            ->execute([...array_values($values), now(), $id]);
    }

    public function setImage(int $id, ?string $url): void
    {
        Database::connection()->prepare('UPDATE products SET image_url = ?, updated_at = ? WHERE id = ?')->execute([$url, now(), $id]);
    }

    public function setCost(int $id, int $costCents): void
    {
        Database::connection()->prepare('UPDATE products SET cost_price = ?, updated_at = ? WHERE id = ?')->execute([db_money($costCents), now(), $id]);
    }

    public function setArchived(int $id, bool $archived): void
    {
        Database::connection()->prepare('UPDATE products SET archived_at = ?, updated_at = ? WHERE id = ?')
            ->execute([$archived ? now() : null, now(), $id]);
    }

    /** @return array<string, string> */
    public static function specs(?string $json): array
    {
        $specs = json_decode((string) $json, true);

        return is_array($specs) ? array_filter(array_map('strval', $specs), static fn (string $v) => $v !== '') : [];
    }

    private function where(array $filters): array
    {
        $where = [!empty($filters['archived']) ? 'p.archived_at IS NOT NULL' : 'p.archived_at IS NULL'];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.sku LIKE ? OR p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)';
            array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
        }
        if (!empty($filters['category'])) {
            $where[] = 'p.category_id = ?';
            $params[] = (int) $filters['category'];
        }
        if (!empty($filters['supplier'])) {
            $where[] = 'p.supplier_id = ?';
            $params[] = (int) $filters['supplier'];
        }
        $where[] = match ($filters['status'] ?? '') {
            'ok' => 'p.qty_on_hand > p.reorder_level',
            'low' => 'p.qty_on_hand <= p.reorder_level',
            'out' => 'p.qty_on_hand <= 0',
            default => '1 = 1',
        };

        return [implode(' AND ', $where), $params];
    }

    private function values(array $data): array
    {
        return [
            'sku' => strtoupper(trim((string) $data['sku'])),
            'name' => trim((string) $data['name']),
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'supplier_id' => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            'brand' => trim((string) ($data['brand'] ?? '')) ?: null,
            'model' => trim((string) ($data['model'] ?? '')) ?: null,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'specs' => !empty($data['specs']) ? json_encode($data['specs'], JSON_UNESCAPED_UNICODE) : null,
            'cost_price' => db_money((int) $data['cost_cents']),
            'selling_price' => db_money((int) $data['price_cents']),
            'reorder_level' => max(0, (int) $data['reorder_level']),
        ];
    }
}
