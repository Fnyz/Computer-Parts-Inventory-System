<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Copies suppliers and products from the old app's tables (supplier or customer, product), if they exist.
 * Products get SKU LEGACY-<old id>, keep their price and quantity, and an "opening" ledger row.
 * The old tables are left untouched.
 */
final class ImportLegacyInventory extends AbstractMigration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $oldSuppliers = $this->hasTable('supplier') ? 'supplier' : ($this->hasTable('customer') ? 'customer' : null);
        if ($oldSuppliers !== null && (int) $this->fetchRow('SELECT COUNT(*) AS n FROM suppliers')['n'] === 0) {
            foreach ($this->fetchAll("SELECT * FROM {$oldSuppliers} ORDER BY cus_id") as $old) {
                $this->table('suppliers')->insert([
                    'id' => (int) $old['cus_id'],
                    'name' => mb_substr(trim((string) $old['cus_fname']) ?: 'Supplier ' . $old['cus_id'], 0, 150),
                    // The old form used the "last name" column for the address
                    'address' => ($old['cus_lname'] ?? '') !== '' ? mb_substr((string) $old['cus_lname'], 0, 255) : null,
                    'phone' => ($old['cus_phone'] ?? '') !== '' ? (string) $old['cus_phone'] : null,
                    'created_at' => $old['cus_created_at'] ?? $now,
                    'updated_at' => $old['cus_updated_at'] ?? $now,
                ])->saveData();
            }
        }

        if (!$this->hasTable('product') || (int) $this->fetchRow('SELECT COUNT(*) AS n FROM products')['n'] > 0) {
            return;
        }
        $admin = $this->fetchRow("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
        foreach ($this->fetchAll('SELECT * FROM product ORDER BY prod_id') as $old) {
            $qty = max(0, (int) ($old['prod_quan'] ?? 0));
            $this->table('products')->insert([
                'id' => (int) $old['prod_id'],
                'sku' => 'LEGACY-' . $old['prod_id'],
                'name' => mb_substr(trim((string) $old['prod_name']) ?: 'Product ' . $old['prod_id'], 0, 150),
                'description' => $old['prod_desc'] ?? null,
                'selling_price' => (float) ($old['prod_price'] ?? 0),
                'cost_price' => 0,
                'qty_on_hand' => $qty,
                'created_at' => $old['prod_created_at'] ?? $now,
                'updated_at' => $old['prod_updated_at'] ?? $now,
            ])->saveData();
            if ($qty > 0) {
                $this->table('stock_movements')->insert([
                    'product_id' => (int) $old['prod_id'],
                    'type' => 'opening',
                    'qty_change' => $qty,
                    'balance_after' => $qty,
                    'user_id' => $admin['id'] ?? null,
                    'note' => 'Imported from the old system',
                    'created_at' => $now,
                ])->saveData();
            }
        }
    }

    public function down(): void
    {
        // Imported rows are ordinary records now; nothing to undo safely.
    }
}
