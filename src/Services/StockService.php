<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\Auth;
use App\Core\Database;

/**
 * The only way stock changes. Each change locks the product row, checks the result can't go
 * below zero, updates qty_on_hand and writes a ledger row — all in one transaction.
 */
final class StockService
{
    public const TYPES = [
        'opening' => 'Opening stock',
        'purchase' => 'Received (PO)',
        'sale' => 'Sale',
        'void' => 'Sale voided',
        'adjustment' => 'Adjustment',
        'return' => 'Customer return',
    ];

    /** Adjustment reasons offered on the form. */
    public const REASONS = ['Stock count', 'Damaged', 'Lost / stolen', 'Found', 'Supplier return', 'Customer return', 'Other'];

    /**
     * @return int the new quantity on hand
     * @throws StockException when the change would leave less than zero
     */
    public function move(int $productId, int $qtyChange, string $type, ?string $referenceType = null, ?int $referenceId = null, ?string $note = null, ?int $unitCostCents = null): int
    {
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException("Unknown stock movement type {$type}");
        }

        return Database::transaction(function (\PDO $pdo) use ($productId, $qtyChange, $type, $referenceType, $referenceId, $note, $unitCostCents): int {
            $stmt = $pdo->prepare('SELECT id, sku, name, qty_on_hand FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if ($product === false) {
                throw new StockException('That product no longer exists.');
            }
            $balance = (int) $product['qty_on_hand'] + $qtyChange;
            if ($balance < 0) {
                throw new StockException(sprintf(
                    'Not enough stock for %s (%s): %d left, %d needed.',
                    $product['name'],
                    $product['sku'],
                    (int) $product['qty_on_hand'],
                    -$qtyChange,
                ));
            }

            $pdo->prepare('UPDATE products SET qty_on_hand = ?, updated_at = ? WHERE id = ?')->execute([$balance, now(), $productId]);
            $pdo->prepare(
                'INSERT INTO stock_movements (product_id, type, qty_change, balance_after, unit_cost, reference_type, reference_id, user_id, note, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            )->execute([
                $productId, $type, $qtyChange, $balance,
                $unitCostCents !== null ? db_money($unitCostCents) : null,
                $referenceType, $referenceId, Auth::id() ?: null,
                $note !== null && $note !== '' ? mb_substr($note, 0, 255) : null,
                now(),
            ]);

            return $balance;
        });
    }

    /**
     * Manual correction from the Adjust stock form.
     * mode: add | remove | set (set = counted quantity after a stock count)
     */
    public function adjust(int $productId, string $mode, int $qty, string $reason, string $note = ''): int
    {
        return Database::transaction(function (\PDO $pdo) use ($productId, $mode, $qty, $reason, $note): int {
            $stmt = $pdo->prepare('SELECT qty_on_hand FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$productId]);
            $current = (int) $stmt->fetchColumn();
            $change = match ($mode) {
                'add' => $qty,
                'remove' => -$qty,
                'set' => $qty - $current,
                default => throw new \InvalidArgumentException("Unknown adjustment mode {$mode}"),
            };
            if ($change === 0) {
                return $current;
            }
            $type = $reason === 'Customer return' && $change > 0 ? 'return' : 'adjustment';

            return $this->move($productId, $change, $type, null, null, trim($reason . ($note !== '' ? ': ' . $note : '')));
        });
    }
}
