<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInventoryTables extends AbstractMigration
{
    /** Spec fields shown on the product form for each part type. Admins can change them later. */
    private const CATEGORIES = [
        'CPU' => ['Socket', 'Cores / threads', 'Base / boost clock', 'TDP'],
        'GPU' => ['Memory', 'Interface', 'TDP', 'Outputs'],
        'RAM' => ['Type', 'Capacity', 'Speed', 'Latency'],
        'Storage' => ['Type', 'Capacity', 'Interface', 'Read speed'],
        'Motherboard' => ['Socket', 'Chipset', 'Form factor', 'Memory type'],
        'PSU' => ['Wattage', 'Efficiency', 'Modular'],
        'Cooling' => ['Type', 'Size', 'Socket support'],
        'Case' => ['Form factor', 'Fans included', 'Colour'],
        'Peripherals' => ['Type', 'Connection'],
    ];

    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE categories (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(80)  NOT NULL,
                description VARCHAR(255) NULL,
                spec_fields TEXT         NULL,
                created_at  DATETIME     NOT NULL,
                updated_at  DATETIME     NOT NULL,
                UNIQUE KEY uq_categories_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE suppliers (
                id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name           VARCHAR(150) NOT NULL,
                contact_person VARCHAR(150) NULL,
                phone          VARCHAR(30)  NULL,
                email          VARCHAR(150) NULL,
                address        VARCHAR(255) NULL,
                notes          TEXT         NULL,
                archived_at    DATETIME     NULL,
                created_at     DATETIME     NOT NULL,
                updated_at     DATETIME     NOT NULL,
                KEY idx_suppliers_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE products (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sku           VARCHAR(64)   NOT NULL,
                name          VARCHAR(150)  NOT NULL,
                category_id   INT UNSIGNED  NULL,
                supplier_id   INT UNSIGNED  NULL,
                brand         VARCHAR(80)   NULL,
                model         VARCHAR(80)   NULL,
                description   TEXT          NULL,
                specs         TEXT          NULL,
                image_url     VARCHAR(500)  NULL,
                cost_price    DECIMAL(12,2) NOT NULL DEFAULT 0,
                selling_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                reorder_level INT UNSIGNED  NOT NULL DEFAULT 5,
                qty_on_hand   INT           NOT NULL DEFAULT 0,
                archived_at   DATETIME      NULL,
                created_at    DATETIME      NOT NULL,
                updated_at    DATETIME      NOT NULL,
                UNIQUE KEY uq_products_sku (sku),
                KEY idx_products_name (name),
                CONSTRAINT chk_products_qty CHECK (qty_on_hand >= 0),
                CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
                CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        // The ledger: every change to stock is one row. products.qty_on_hand always equals the latest balance_after.
        $this->execute(<<<'SQL'
            CREATE TABLE stock_movements (
                id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id     INT UNSIGNED  NOT NULL,
                type           VARCHAR(20)   NOT NULL,
                qty_change     INT           NOT NULL,
                balance_after  INT           NOT NULL,
                unit_cost      DECIMAL(12,2) NULL,
                reference_type VARCHAR(30)   NULL,
                reference_id   INT UNSIGNED  NULL,
                user_id        INT UNSIGNED  NULL,
                note           VARCHAR(255)  NULL,
                created_at     DATETIME      NOT NULL,
                KEY idx_moves_product (product_id, created_at),
                KEY idx_moves_reference (reference_type, reference_id),
                KEY idx_moves_created (created_at),
                CONSTRAINT fk_moves_product FOREIGN KEY (product_id) REFERENCES products (id),
                CONSTRAINT fk_moves_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $now = date('Y-m-d H:i:s');
        foreach (self::CATEGORIES as $name => $fields) {
            $this->table('categories')->insert([
                'name' => $name,
                'spec_fields' => json_encode($fields),
                'created_at' => $now,
                'updated_at' => $now,
            ])->saveData();
        }
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS stock_movements, products, suppliers, categories');
    }
}
