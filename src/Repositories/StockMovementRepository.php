<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Paginator;

final class StockMovementRepository
{
    private const SELECT = 'SELECT m.*, p.sku, p.name AS product_name, u.name AS user_name
        FROM stock_movements m JOIN products p ON p.id = m.product_id LEFT JOIN users u ON u.id = m.user_id';
    private const FROM = 'FROM stock_movements m JOIN products p ON p.id = m.product_id LEFT JOIN users u ON u.id = m.user_id';

    /** @param array{product?: int, type?: string, from?: string, to?: string, q?: string} $filters */
    public function paginate(array $filters, int $page, int $perPage = Paginator::PER_PAGE): array
    {
        [$where, $params] = $this->where($filters);

        return Paginator::run(self::SELECT, self::FROM, $where, $params, 'm.id DESC', $page, $perPage);
    }

    public function all(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $stmt = Database::connection()->prepare(self::SELECT . ($where ? ' WHERE ' . $where : '') . ' ORDER BY m.id DESC LIMIT 20000');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function forProduct(int $productId, int $limit = 10): array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE m.product_id = ? ORDER BY m.id DESC LIMIT ' . max(1, $limit));
        $stmt->execute([$productId]);

        return $stmt->fetchAll();
    }

    public function recent(int $limit = 8): array
    {
        return Database::connection()->query(self::SELECT . ' ORDER BY m.id DESC LIMIT ' . max(1, $limit))->fetchAll();
    }

    /** Link and label for the document a movement came from. */
    public static function reference(array $move): ?array
    {
        return match ($move['reference_type'] ?? null) {
            'sale' => ['/sales/' . $move['reference_id'], 'Sale #' . $move['reference_id']],
            'purchase_order' => ['/purchase-orders/' . $move['reference_id'], 'PO #' . $move['reference_id']],
            default => null,
        };
    }

    private function where(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['product'])) {
            $where[] = 'm.product_id = ?';
            $params[] = (int) $filters['product'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'm.type = ?';
            $params[] = (string) $filters['type'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'm.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'm.created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if (!empty($filters['q'])) {
            $where[] = '(p.sku LIKE ? OR p.name LIKE ? OR m.note LIKE ?)';
            array_push($params, ...array_fill(0, 3, '%' . $filters['q'] . '%'));
        }

        return [implode(' AND ', $where), $params];
    }
}
