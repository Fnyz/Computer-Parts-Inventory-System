<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\Auth;
use App\Core\Database;
use App\Repositories\SaleRepository;

/**
 * Point-of-sale checkout. The browser only says which parts and how many;
 * prices, totals and change are worked out here from the database.
 */
final class CheckoutService
{
    public const MAX_QTY_PER_LINE = 999;

    /**
     * @param array<mixed> $lines from the browser: [{product_id, qty}, …]; anything else is ignored
     * @return array{id: int, number: string, total: int, change: int}
     * @throws StockException for anything the cashier can fix (out of stock, short payment…)
     */
    public function checkout(array $lines, int $discount, string $method, ?int $amountPaid, string $customerName = ''): array
    {
        $quantities = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }
            $id = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['qty'] ?? 0);
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
            if ($quantities[$id] > self::MAX_QTY_PER_LINE) {
                throw new StockException('A single line can have at most ' . self::MAX_QTY_PER_LINE . ' units.');
            }
        }
        if ($quantities === []) {
            throw new StockException('Add at least one part to the sale.');
        }
        if (!isset(SaleRepository::METHODS[$method])) {
            throw new StockException('Choose a payment method.');
        }
        if ($discount < 0) {
            throw new StockException('The discount can’t be negative.');
        }
        ksort($quantities); // lock rows in id order so two checkouts can't deadlock

        return Database::transaction(function (\PDO $pdo) use ($quantities, $discount, $method, $amountPaid, $customerName): array {
            $marks = implode(',', array_fill(0, count($quantities), '?'));
            $stmt = $pdo->prepare("SELECT id, sku, name, selling_price, cost_price, qty_on_hand, archived_at FROM products WHERE id IN ({$marks}) ORDER BY id FOR UPDATE");
            $stmt->execute(array_keys($quantities));
            $products = [];
            foreach ($stmt->fetchAll() as $p) {
                $products[(int) $p['id']] = $p;
            }

            $subtotal = 0;
            foreach ($quantities as $id => $qty) {
                $p = $products[$id] ?? null;
                if ($p === null || $p['archived_at'] !== null) {
                    throw new StockException('One of the parts in the cart is no longer sold. Remove it and try again.');
                }
                if ($qty > (int) $p['qty_on_hand']) {
                    throw new StockException("Not enough stock for {$p['name']} ({$p['sku']}): {$p['qty_on_hand']} left, {$qty} in the cart.");
                }
                $subtotal += db_cents($p['selling_price']) * $qty;
            }
            if ($discount > $subtotal) {
                throw new StockException('The discount can’t be more than the subtotal.');
            }
            $total = $subtotal - $discount;
            if ($method === 'cash') {
                if ($amountPaid === null || $amountPaid < $total) {
                    throw new StockException('The cash received is less than the total of ' . money($total) . '.');
                }
                $paid = $amountPaid;
            } else {
                $paid = $total; // GCash and card are paid exactly
            }
            $change = $paid - $total;

            $number = (new SequenceService())->next('SO');
            $pdo->prepare(
                'INSERT INTO sales (sale_number, user_id, customer_name, payment_method, subtotal, discount, total, amount_paid, change_due, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            )->execute([
                $number, Auth::id() ?: null, mb_substr(trim($customerName), 0, 150) ?: null, $method,
                db_money($subtotal), db_money($discount), db_money($total), db_money($paid), db_money($change), 'completed', now(),
            ]);
            $saleId = (int) $pdo->lastInsertId();

            $item = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, sku, product_name, qty, unit_price, unit_cost, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stock = new StockService();
            foreach ($quantities as $id => $qty) {
                $p = $products[$id];
                $price = db_cents($p['selling_price']);
                $item->execute([$saleId, $id, $p['sku'], $p['name'], $qty, db_money($price), $p['cost_price'], db_money($price * $qty)]);
                $stock->move($id, -$qty, 'sale', 'sale', $saleId, $number);
            }

            return ['id' => $saleId, 'number' => $number, 'total' => $total, 'change' => $change];
        });
    }

    /** Cancels a completed sale and puts its stock back. */
    public function void(int $saleId, string $reason): void
    {
        Database::transaction(function (\PDO $pdo) use ($saleId, $reason): void {
            $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ? FOR UPDATE');
            $stmt->execute([$saleId]);
            $sale = $stmt->fetch();
            if ($sale === false) {
                throw new StockException('That sale no longer exists.');
            }
            if ($sale['status'] !== 'completed') {
                throw new StockException('This sale was already voided.');
            }
            $pdo->prepare("UPDATE sales SET status = 'voided', voided_at = ?, voided_by = ?, void_reason = ? WHERE id = ?")
                ->execute([now(), Auth::id() ?: null, mb_substr($reason, 0, 255), $saleId]);

            $items = $pdo->prepare('SELECT product_id, qty FROM sale_items WHERE sale_id = ? ORDER BY product_id');
            $items->execute([$saleId]);
            $stock = new StockService();
            foreach ($items->fetchAll() as $item) {
                $stock->move((int) $item['product_id'], (int) $item['qty'], 'void', 'sale', $saleId, 'Voided ' . $sale['sale_number']);
            }
        });
    }
}
