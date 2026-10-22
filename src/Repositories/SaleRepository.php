<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Paginator;

final class SaleRepository
{
    public const METHODS = ['cash' => 'Cash', 'gcash' => 'GCash', 'card' => 'Card'];

    private const SELECT = 'SELECT s.*, u.name AS cashier_name, v.name AS voided_by_name,
        (SELECT COALESCE(SUM(i.qty), 0) FROM sale_items i WHERE i.sale_id = s.id) AS item_count
        FROM sales s LEFT JOIN users u ON u.id = s.user_id LEFT JOIN users v ON v.id = s.voided_by';
    private const FROM = 'FROM sales s LEFT JOIN users u ON u.id = s.user_id';

    /** @param array{from?: string, to?: string, user?: int, status?: string, q?: string, method?: string} $filters */
    public function paginate(array $filters, int $page): array
    {
        [$where, $params] = $this->where($filters);

        return Paginator::run(self::SELECT, self::FROM, $where, $params, 's.id DESC', $page);
    }

    public function all(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $stmt = Database::connection()->prepare(self::SELECT . ($where ? ' WHERE ' . $where : '') . ' ORDER BY s.id DESC LIMIT 20000');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Totals of completed sales for the same filters. */
    public function summary(array $filters): array
    {
        $filters['status'] = 'completed';
        [$where, $params] = $this->where($filters);
        $stmt = Database::connection()->prepare('SELECT COUNT(*) AS receipts, COALESCE(SUM(s.total), 0) AS revenue, COALESCE(SUM(s.discount), 0) AS discounts ' . self::FROM . ' WHERE ' . $where);
        $stmt->execute($params);
        $row = (array) $stmt->fetch();

        return ['receipts' => (int) $row['receipts'], 'revenue' => db_cents($row['revenue']), 'discounts' => db_cents($row['discounts'])];
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT . ' WHERE s.id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function items(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id');
        $stmt->execute([$id]);

        return $stmt->fetchAll();
    }

    /** Completed sales per day. @return array<string, array{revenue: int, receipts: int, profit: int}> */
    public function byDay(string $from, string $to): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT DATE(s.created_at) AS day, COUNT(*) AS receipts, SUM(s.total) AS revenue,
                    SUM(s.total) - SUM(COALESCE(c.cost, 0)) AS profit
             FROM sales s
             LEFT JOIN (SELECT sale_id, SUM(qty * unit_cost) AS cost FROM sale_items GROUP BY sale_id) c ON c.sale_id = s.id
             WHERE s.status = 'completed' AND s.created_at BETWEEN ? AND ?
             GROUP BY DATE(s.created_at) ORDER BY day",
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
        $days = [];
        foreach ($stmt->fetchAll() as $row) {
            $days[(string) $row['day']] = ['revenue' => db_cents($row['revenue']), 'receipts' => (int) $row['receipts'], 'profit' => db_cents($row['profit'])];
        }

        return $days;
    }

    /** Best-selling parts in a date range. */
    public function topProducts(string $from, string $to, int $limit = 10): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT i.product_id, i.sku, MAX(i.product_name) AS product_name, SUM(i.qty) AS qty, SUM(i.line_total) AS revenue,
                    SUM(i.line_total) - SUM(i.qty * i.unit_cost) AS profit
             FROM sale_items i JOIN sales s ON s.id = i.sale_id
             WHERE s.status = 'completed' AND s.created_at BETWEEN ? AND ?
             GROUP BY i.product_id, i.sku ORDER BY revenue DESC LIMIT " . max(1, $limit),
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);

        return $stmt->fetchAll();
    }

    /** Sales per cashier in a date range. */
    public function byCashier(string $from, string $to): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(u.name, 'Deleted user') AS cashier, COUNT(*) AS receipts, SUM(s.total) AS revenue
             FROM sales s LEFT JOIN users u ON u.id = s.user_id
             WHERE s.status = 'completed' AND s.created_at BETWEEN ? AND ?
             GROUP BY s.user_id, u.name ORDER BY revenue DESC",
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);

        return $stmt->fetchAll();
    }

    /** @return array<int, string> id => name of users who have made sales */
    public function cashiers(): array
    {
        return Database::connection()->query(
            'SELECT DISTINCT u.id, u.name FROM sales s JOIN users u ON u.id = s.user_id ORDER BY u.name',
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function where(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filters['from'])) {
            $where[] = 's.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 's.created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if (!empty($filters['user'])) {
            $where[] = 's.user_id = ?';
            $params[] = (int) $filters['user'];
        }
        if (!empty($filters['status'])) {
            $where[] = 's.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['method'])) {
            $where[] = 's.payment_method = ?';
            $params[] = $filters['method'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(s.sale_number LIKE ? OR s.customer_name LIKE ?)';
            array_push($params, '%' . $filters['q'] . '%', '%' . $filters['q'] . '%');
        }

        return [implode(' AND ', $where), $params];
    }
}
