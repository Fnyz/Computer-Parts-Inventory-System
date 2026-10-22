<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\Auth;
use App\Core\Database;

/** Purchase orders: Draft -> Ordered -> Received, or Cancelled. Receiving adds stock. */
final class PurchaseService
{
    public const MAX_QTY = 100000;

    /**
     * Creates a draft, or replaces a draft's supplier and lines.
     *
     * @param list<array{product_id: int, qty: int, unit_cost: int}> $lines unit_cost in centavos
     * @return int purchase order id
     */
    public function saveDraft(?int $poId, int $supplierId, array $lines, string $notes, ?string $expectedOn): int
    {
        $lines = $this->cleanLines($lines);

        return Database::transaction(function (\PDO $pdo) use ($poId, $supplierId, $lines, $notes, $expectedOn): int {
            $supplier = $pdo->prepare('SELECT archived_at FROM suppliers WHERE id = ?');
            $supplier->execute([$supplierId]);
            $row = $supplier->fetch();
            if ($row === false || $row['archived_at'] !== null) {
                throw new StockException('Choose an active supplier.');
            }
            $this->checkProducts($pdo, array_column($lines, 'product_id'));
            $total = array_sum(array_map(static fn ($l) => $l['qty'] * $l['unit_cost'], $lines));

            if ($poId === null) {
                $number = (new SequenceService())->next('PO');
                $pdo->prepare(
                    'INSERT INTO purchase_orders (po_number, supplier_id, status, notes, expected_on, total, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                )->execute([$number, $supplierId, 'draft', $notes ?: null, $expectedOn, db_money($total), Auth::id() ?: null, now(), now()]);
                $poId = (int) $pdo->lastInsertId();
            } else {
                $po = $this->lock($pdo, $poId);
                if ($po['status'] !== 'draft') {
                    throw new StockException('Only draft purchase orders can be edited.');
                }
                $pdo->prepare('UPDATE purchase_orders SET supplier_id = ?, notes = ?, expected_on = ?, total = ?, updated_at = ? WHERE id = ?')
                    ->execute([$supplierId, $notes ?: null, $expectedOn, db_money($total), now(), $poId]);
                $pdo->prepare('DELETE FROM purchase_order_items WHERE purchase_order_id = ?')->execute([$poId]);
            }

            $insert = $pdo->prepare('INSERT INTO purchase_order_items (purchase_order_id, product_id, qty_ordered, unit_cost, line_total) VALUES (?, ?, ?, ?, ?)');
            foreach ($lines as $line) {
                $insert->execute([$poId, $line['product_id'], $line['qty'], db_money($line['unit_cost']), db_money($line['qty'] * $line['unit_cost'])]);
            }

            return $poId;
        });
    }

    public function markOrdered(int $poId): void
    {
        Database::transaction(function (\PDO $pdo) use ($poId): void {
            $po = $this->lock($pdo, $poId);
            if ($po['status'] !== 'draft') {
                throw new StockException('Only a draft can be marked as ordered.');
            }
            $pdo->prepare("UPDATE purchase_orders SET status = 'ordered', ordered_at = ?, updated_at = ? WHERE id = ?")->execute([now(), now(), $poId]);
        });
    }

    /**
     * Books the delivery: adds stock for each line and closes the order.
     *
     * @param array<int, int> $received purchase_order_items.id => quantity that arrived
     */
    public function receive(int $poId, array $received, bool $updateCosts = true): int
    {
        return Database::transaction(function (\PDO $pdo) use ($poId, $received, $updateCosts): int {
            $po = $this->lock($pdo, $poId);
            if ($po['status'] !== 'ordered') {
                throw new StockException($po['status'] === 'received' ? 'This order was already received.' : 'Mark the order as ordered before receiving it.');
            }
            $items = $pdo->prepare('SELECT * FROM purchase_order_items WHERE purchase_order_id = ? ORDER BY id');
            $items->execute([$poId]);
            $stock = new StockService();
            $units = 0;
            foreach ($items->fetchAll() as $item) {
                $qty = (int) ($received[(int) $item['id']] ?? $item['qty_ordered']);
                if ($qty < 0 || $qty > (int) $item['qty_ordered']) {
                    throw new StockException('Received quantities must be between 0 and the quantity ordered.');
                }
                $pdo->prepare('UPDATE purchase_order_items SET qty_received = ? WHERE id = ?')->execute([$qty, $item['id']]);
                if ($qty === 0) {
                    continue;
                }
                $cost = db_cents($item['unit_cost']);
                $stock->move((int) $item['product_id'], $qty, 'purchase', 'purchase_order', $poId, $po['po_number'], $cost);
                if ($updateCosts) {
                    $pdo->prepare('UPDATE products SET cost_price = ? WHERE id = ?')->execute([db_money($cost), $item['product_id']]);
                }
                $units += $qty;
            }
            if ($units === 0) {
                throw new StockException('Enter at least one received unit.');
            }
            $pdo->prepare("UPDATE purchase_orders SET status = 'received', received_at = ?, received_by = ?, updated_at = ? WHERE id = ?")
                ->execute([now(), Auth::id() ?: null, now(), $poId]);

            return $units;
        });
    }

    public function cancel(int $poId): void
    {
        Database::transaction(function (\PDO $pdo) use ($poId): void {
            $po = $this->lock($pdo, $poId);
            if (!in_array($po['status'], ['draft', 'ordered'], true)) {
                throw new StockException('Received or cancelled orders can’t be cancelled.');
            }
            $pdo->prepare("UPDATE purchase_orders SET status = 'cancelled', cancelled_at = ?, updated_at = ? WHERE id = ?")->execute([now(), now(), $poId]);
        });
    }

    /** Drops empty rows, checks quantities and costs, merges repeated products. */
    private function cleanLines(array $lines): array
    {
        $clean = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId === 0) {
                continue;
            }
            $qty = (int) ($line['qty'] ?? 0);
            $cost = (int) ($line['unit_cost'] ?? -1);
            if ($qty < 1 || $qty > self::MAX_QTY) {
                throw new StockException('Each line needs a quantity of 1 or more.');
            }
            if ($cost < 0) {
                throw new StockException('Each line needs a unit cost of 0 or more.');
            }
            if (isset($clean[$productId])) {
                $clean[$productId]['qty'] += $qty;
                $clean[$productId]['unit_cost'] = $cost;
            } else {
                $clean[$productId] = ['product_id' => $productId, 'qty' => $qty, 'unit_cost' => $cost];
            }
        }
        if ($clean === []) {
            throw new StockException('Add at least one part to the order.');
        }

        return array_values($clean);
    }

    private function checkProducts(\PDO $pdo, array $ids): void
    {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id IN ({$marks}) AND archived_at IS NULL");
        $stmt->execute($ids);
        if ((int) $stmt->fetchColumn() !== count($ids)) {
            throw new StockException('One of the parts no longer exists or was archived.');
        }
    }

    private function lock(\PDO $pdo, int $poId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$poId]);
        $po = $stmt->fetch();
        if ($po === false) {
            throw new StockException('That purchase order no longer exists.');
        }

        return $po;
    }
}
