<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Figures for the dashboard and reports. */
final class InsightRepository
{
    /** @return array{skus: int, units: int, cost_value: int, retail_value: int, low: int, out: int} */
    public function stockTotals(): array
    {
        $row = (array) Database::connection()->query(
            'SELECT COUNT(*) AS skus, COALESCE(SUM(qty_on_hand), 0) AS units,
                    COALESCE(SUM(qty_on_hand * cost_price), 0) AS cost_value, COALESCE(SUM(qty_on_hand * selling_price), 0) AS retail_value,
                    COALESCE(SUM(qty_on_hand > 0 AND qty_on_hand <= reorder_level), 0) AS low, COALESCE(SUM(qty_on_hand <= 0), 0) AS out_count
             FROM products WHERE archived_at IS NULL',
        )->fetch();

        return [
            'skus' => (int) $row['skus'], 'units' => (int) $row['units'],
            'cost_value' => db_cents($row['cost_value']), 'retail_value' => db_cents($row['retail_value']),
            'low' => (int) $row['low'], 'out' => (int) $row['out_count'],
        ];
    }

    /** Per category: SKUs, units, value, and the share of SKUs above their reorder level. */
    public function categories(): array
    {
        $rows = Database::connection()->query(
            "SELECT COALESCE(c.name, 'Uncategorized') AS name, COUNT(p.id) AS skus, COALESCE(SUM(p.qty_on_hand), 0) AS units,
                    COALESCE(SUM(p.qty_on_hand * p.cost_price), 0) AS cost_value, COALESCE(SUM(p.qty_on_hand * p.selling_price), 0) AS retail_value,
                    COALESCE(SUM(p.qty_on_hand > p.reorder_level), 0) AS healthy
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.archived_at IS NULL
             GROUP BY c.id, c.name ORDER BY cost_value DESC",
        )->fetchAll();

        return array_map(static fn (array $r) => [
            'name' => $r['name'],
            'skus' => (int) $r['skus'],
            'units' => (int) $r['units'],
            'cost_value' => db_cents($r['cost_value']),
            'retail_value' => db_cents($r['retail_value']),
            'health' => (int) $r['skus'] > 0 ? (int) round((int) $r['healthy'] / (int) $r['skus'] * 100) : 0,
        ], $rows);
    }

    /** @return array{receipts: int, revenue: int, profit: int, discounts: int} completed sales in a date range */
    public function salesTotals(string $from, string $to): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS receipts, COALESCE(SUM(s.total), 0) AS revenue, COALESCE(SUM(s.discount), 0) AS discounts,
                    COALESCE(SUM(s.total), 0) - COALESCE(SUM(c.cost), 0) AS profit
             FROM sales s LEFT JOIN (SELECT sale_id, SUM(qty * unit_cost) AS cost FROM sale_items GROUP BY sale_id) c ON c.sale_id = s.id
             WHERE s.status = 'completed' AND s.created_at BETWEEN ? AND ?",
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
        $row = (array) $stmt->fetch();

        return ['receipts' => (int) $row['receipts'], 'revenue' => db_cents($row['revenue']), 'profit' => db_cents($row['profit']), 'discounts' => db_cents($row['discounts'])];
    }

    /** @return array<string, array{receipts: int, revenue: int}> by payment method */
    public function paymentMethods(string $from, string $to): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT payment_method, COUNT(*) AS receipts, SUM(total) AS revenue FROM sales
             WHERE status = 'completed' AND created_at BETWEEN ? AND ? GROUP BY payment_method",
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
        $methods = [];
        foreach ($stmt->fetchAll() as $row) {
            $methods[(string) $row['payment_method']] = ['receipts' => (int) $row['receipts'], 'revenue' => db_cents($row['revenue'])];
        }

        return $methods;
    }
}
