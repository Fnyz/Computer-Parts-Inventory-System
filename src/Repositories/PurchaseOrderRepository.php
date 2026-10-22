<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Paginator;

final class PurchaseOrderRepository
{
    public const STATUSES = ['draft' => 'Draft', 'ordered' => 'Ordered', 'received' => 'Received', 'cancelled' => 'Cancelled'];

    private const SELECT = 'SELECT po.*, s.name AS supplier_name, u.name AS created_by_name, r.name AS received_by_name,
        (SELECT COUNT(*) FROM purchase_order_items i WHERE i.purchase_order_id = po.id) AS line_count
        FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id
        LEFT JOIN users u ON u.id = po.created_by LEFT JOIN users r ON r.id = po.received_by';
    private const FROM = 'FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id';

    public function paginate(array $filters, int $page): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'po.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['supplier'])) {
            $where[] = 'po.supplier_id = ?';
            $params[] = (int) $filters['supplier'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(po.po_number LIKE ? OR s.name LIKE ?)';
            array_push($params, '%' . $filters['q'] . '%', '%' . $filters['q'] . '%');
        }

        return Paginator::run(self::SELECT, self::FROM, implode(' AND ', $where), $params, 'po.id DESC', $page);
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE po.id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function items(int $id): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT i.*, p.sku, p.name AS product_name, p.qty_on_hand
             FROM purchase_order_items i JOIN products p ON p.id = i.product_id
             WHERE i.purchase_order_id = ? ORDER BY i.id',
        );
        $stmt->execute([$id]);

        return $stmt->fetchAll();
    }

    public function forSupplier(int $supplierId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE po.supplier_id = ? ORDER BY po.id DESC LIMIT ' . max(1, $limit));
        $stmt->execute([$supplierId]);

        return $stmt->fetchAll();
    }

    public function countByStatus(): array
    {
        return Database::connection()->query('SELECT status, COUNT(*) FROM purchase_orders GROUP BY status')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /** @return array<string, int> Y-m-d => centavos of stock received that day */
    public function receivedByDay(string $from, string $to): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT DATE(m.created_at) AS day, SUM(m.qty_change * m.unit_cost) AS value
             FROM stock_movements m WHERE m.type = 'purchase' AND m.created_at BETWEEN ? AND ?
             GROUP BY DATE(m.created_at)",
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
        $days = [];
        foreach ($stmt->fetchAll() as $row) {
            $days[(string) $row['day']] = db_cents($row['value']);
        }

        return $days;
    }
}
