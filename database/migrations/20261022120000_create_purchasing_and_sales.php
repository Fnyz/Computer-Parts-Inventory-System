<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePurchasingAndSales extends AbstractMigration
{
    public function up(): void
    {
        // Stock in: Draft -> Ordered -> Received (or Cancelled)
        $this->execute(<<<'SQL'
            CREATE TABLE purchase_orders (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                po_number    VARCHAR(20)   NOT NULL,
                supplier_id  INT UNSIGNED  NOT NULL,
                status       VARCHAR(12)   NOT NULL DEFAULT 'draft',
                notes        TEXT          NULL,
                expected_on  DATE          NULL,
                total        DECIMAL(12,2) NOT NULL DEFAULT 0,
                created_by   INT UNSIGNED  NULL,
                ordered_at   DATETIME      NULL,
                received_at  DATETIME      NULL,
                received_by  INT UNSIGNED  NULL,
                cancelled_at DATETIME      NULL,
                created_at   DATETIME      NOT NULL,
                updated_at   DATETIME      NOT NULL,
                UNIQUE KEY uq_po_number (po_number),
                KEY idx_po_status (status),
                CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id),
                CONSTRAINT fk_po_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
                CONSTRAINT fk_po_received_by FOREIGN KEY (received_by) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE purchase_order_items (
                id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                purchase_order_id INT UNSIGNED  NOT NULL,
                product_id        INT UNSIGNED  NOT NULL,
                qty_ordered       INT UNSIGNED  NOT NULL,
                qty_received      INT UNSIGNED  NOT NULL DEFAULT 0,
                unit_cost         DECIMAL(12,2) NOT NULL,
                line_total        DECIMAL(12,2) NOT NULL,
                CONSTRAINT fk_poi_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_poi_product FOREIGN KEY (product_id) REFERENCES products (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        // Stock out. Prices are copied onto each line so old receipts never change.
        $this->execute(<<<'SQL'
            CREATE TABLE sales (
                id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_number    VARCHAR(20)   NOT NULL,
                user_id        INT UNSIGNED  NULL,
                customer_name  VARCHAR(150)  NULL,
                payment_method VARCHAR(10)   NOT NULL DEFAULT 'cash',
                subtotal       DECIMAL(12,2) NOT NULL,
                discount       DECIMAL(12,2) NOT NULL DEFAULT 0,
                total          DECIMAL(12,2) NOT NULL,
                amount_paid    DECIMAL(12,2) NOT NULL,
                change_due     DECIMAL(12,2) NOT NULL DEFAULT 0,
                status         VARCHAR(10)   NOT NULL DEFAULT 'completed',
                voided_at      DATETIME      NULL,
                voided_by      INT UNSIGNED  NULL,
                void_reason    VARCHAR(255)  NULL,
                created_at     DATETIME      NOT NULL,
                UNIQUE KEY uq_sale_number (sale_number),
                KEY idx_sales_created (created_at),
                KEY idx_sales_status (status),
                CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
                CONSTRAINT fk_sales_voided_by FOREIGN KEY (voided_by) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE sale_items (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_id      INT UNSIGNED  NOT NULL,
                product_id   INT UNSIGNED  NOT NULL,
                sku          VARCHAR(64)   NOT NULL,
                product_name VARCHAR(150)  NOT NULL,
                qty          INT UNSIGNED  NOT NULL,
                unit_price   DECIMAL(12,2) NOT NULL,
                unit_cost    DECIMAL(12,2) NOT NULL DEFAULT 0,
                line_total   DECIMAL(12,2) NOT NULL,
                KEY idx_sale_items_product (product_id),
                CONSTRAINT fk_si_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE CASCADE,
                CONSTRAINT fk_si_product FOREIGN KEY (product_id) REFERENCES products (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS sale_items, sales, purchase_order_items, purchase_orders');
    }
}
