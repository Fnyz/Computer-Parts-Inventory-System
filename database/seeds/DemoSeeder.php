<?php

declare(strict_types=1);

use App\Auth\Auth;
use App\Core\Database;
use App\Repositories\ProductRepository;
use App\Repositories\SupplierRepository;
use App\Services\CheckoutService;
use App\Services\PurchaseService;
use App\Services\StockService;
use Phinx\Seed\AbstractSeed;

/**
 * Sample computer-parts data for a demo or a fresh database: suppliers, parts with specs,
 * two weeks of purchase orders and sales. Run with:  composer seed
 * It refuses to run when products already exist, so it never mixes into real data.
 */
final class DemoSeeder extends AbstractSeed
{
    private const SUPPLIERS = [
        ['PC Hub Distributors', 'Ana Reyes', '0917 555 0101', 'orders@pchub.example', 'Banilad, Cebu City'],
        ['Silicon Valley Trading', 'Mark Lim', '0918 555 0202', 'sales@svtrading.example', 'Gilmore, Quezon City'],
        ['CoolTech Supply', 'Joy Santos', '0922 555 0303', 'hello@cooltech.example', 'Ortigas, Pasig City'],
    ];

    /** sku, name, category, supplier #, brand, model, cost, price, reorder, opening stock, specs */
    private const PRODUCTS = [
        ['CPU-AMD-R5-7600', 'AMD Ryzen 5 7600', 'CPU', 0, 'AMD', '7600', 10400, 11950, 5, 16, ['Socket' => 'AM5', 'Cores / threads' => '6 / 12', 'Base / boost clock' => '3.8 / 5.1 GHz', 'TDP' => '65 W']],
        ['CPU-AMD-R7-7800X3D', 'AMD Ryzen 7 7800X3D', 'CPU', 0, 'AMD', '7800X3D', 21500, 24500, 3, 6, ['Socket' => 'AM5', 'Cores / threads' => '8 / 16', 'Base / boost clock' => '4.2 / 5.0 GHz', 'TDP' => '120 W']],
        ['CPU-INT-I5-14400F', 'Intel Core i5-14400F', 'CPU', 1, 'Intel', 'i5-14400F', 9300, 10750, 4, 5, ['Socket' => 'LGA1700', 'Cores / threads' => '10 / 16', 'TDP' => '65 W']],
        ['GPU-NV-RTX4060-8G', 'GeForce RTX 4060 8GB', 'GPU', 1, 'NVIDIA', 'RTX 4060', 16200, 18500, 4, 6, ['Memory' => '8 GB GDDR6', 'Interface' => 'PCIe 4.0 x8', 'TDP' => '115 W', 'Outputs' => '3× DP, 1× HDMI']],
        ['GPU-NV-RTX4070S-12G', 'GeForce RTX 4070 Super 12GB', 'GPU', 1, 'NVIDIA', 'RTX 4070 Super', 33800, 37900, 2, 3, ['Memory' => '12 GB GDDR6X', 'Interface' => 'PCIe 4.0 x16', 'TDP' => '220 W']],
        ['GPU-AMD-RX7600-8G', 'Radeon RX 7600 8GB', 'GPU', 1, 'AMD', 'RX 7600', 14100, 15990, 3, 5, ['Memory' => '8 GB GDDR6', 'Interface' => 'PCIe 4.0 x8', 'TDP' => '165 W']],
        ['RAM-DDR5-16-5600', 'DDR5 16GB 5600', 'RAM', 0, 'Kingston', 'Fury Beast', 2600, 3050, 8, 24, ['Type' => 'DDR5', 'Capacity' => '16 GB', 'Speed' => '5600 MT/s', 'Latency' => 'CL36']],
        ['RAM-DDR5-32-6000', 'DDR5 32GB (2×16) 6000', 'RAM', 0, 'G.Skill', 'Flare X5', 5100, 5850, 6, 9, ['Type' => 'DDR5', 'Capacity' => '32 GB (2×16)', 'Speed' => '6000 MT/s', 'Latency' => 'CL30']],
        ['RAM-DDR4-16-3200', 'DDR4 16GB 3200', 'RAM', 0, 'Crucial', 'Ballistix', 1650, 1990, 6, 14, ['Type' => 'DDR4', 'Capacity' => '16 GB', 'Speed' => '3200 MT/s']],
        ['SSD-NVME-1TB-G4', 'NVMe SSD 1TB Gen4', 'Storage', 0, 'Samsung', '980 Pro', 3350, 3990, 8, 30, ['Type' => 'NVMe M.2 2280', 'Capacity' => '1 TB', 'Interface' => 'PCIe 4.0', 'Read speed' => '7,000 MB/s']],
        ['SSD-NVME-2TB-G4', 'NVMe SSD 2TB Gen4', 'Storage', 0, 'WD', 'SN850X', 6200, 7290, 4, 8, ['Type' => 'NVMe M.2 2280', 'Capacity' => '2 TB', 'Interface' => 'PCIe 4.0', 'Read speed' => '7,300 MB/s']],
        ['HDD-2TB-7200', 'HDD 2TB 7200 rpm', 'Storage', 0, 'Seagate', 'BarraCuda', 2550, 2990, 4, 7, ['Type' => '3.5" SATA', 'Capacity' => '2 TB']],
        ['MB-B650M-WIFI', 'B650M WiFi Motherboard', 'Motherboard', 0, 'MSI', 'B650M Gaming WiFi', 7100, 8200, 4, 10, ['Socket' => 'AM5', 'Chipset' => 'B650', 'Form factor' => 'Micro-ATX', 'Memory type' => 'DDR5']],
        ['MB-B760M-DDR5', 'B760M DDR5 Motherboard', 'Motherboard', 1, 'ASUS', 'Prime B760M-A', 6400, 7350, 3, 4, ['Socket' => 'LGA1700', 'Chipset' => 'B760', 'Form factor' => 'Micro-ATX', 'Memory type' => 'DDR5']],
        ['PSU-650W-GOLD', '650W 80+ Gold Modular', 'PSU', 2, 'Corsair', 'RM650e', 3800, 4450, 5, 12, ['Wattage' => '650 W', 'Efficiency' => '80+ Gold', 'Modular' => 'Fully']],
        ['PSU-850W-GOLD', '850W 80+ Gold Modular', 'PSU', 2, 'Seasonic', 'Focus GX-850', 5900, 6850, 3, 4, ['Wattage' => '850 W', 'Efficiency' => '80+ Gold', 'Modular' => 'Fully']],
        ['FAN-120-ARGB-3P', '120mm ARGB Fan (3-pack)', 'Cooling', 2, 'Lian Li', 'UNI FAN SL', 1250, 1690, 6, 36, ['Type' => 'Case fan', 'Size' => '120 mm']],
        ['COOL-AIO-240', '240mm AIO Liquid Cooler', 'Cooling', 2, 'DeepCool', 'LE520', 3100, 3690, 3, 5, ['Type' => 'All-in-one liquid', 'Size' => '240 mm', 'Socket support' => 'AM5, LGA1700']],
        ['CASE-MATX-MESH', 'Micro-ATX Mesh Case', 'Case', 2, 'Montech', 'Air 100', 2200, 2690, 3, 6, ['Form factor' => 'Micro-ATX', 'Fans included' => '4', 'Colour' => 'Black']],
        ['KB-MECH-TKL', 'Mechanical Keyboard TKL', 'Peripherals', 2, 'Royal Kludge', 'RK87', 1900, 2390, 4, 0, ['Type' => 'Keyboard', 'Connection' => 'USB-C / Bluetooth']],
    ];

    public function run(): void
    {
        if ((int) $this->fetchRow('SELECT COUNT(*) AS n FROM products')['n'] > 0 && getenv('SEED_FORCE') !== '1') {
            $this->getOutput()->writeln('<comment>Products already exist, so no demo data was added. (Set SEED_FORCE=1 to add it anyway.)</comment>');

            return;
        }
        $admin = $this->fetchRow("SELECT * FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
        if (!$admin) {
            $this->getOutput()->writeln('<error>Create the first Admin (open /setup) before adding demo data.</error>');

            return;
        }
        $_SESSION = ['user_id' => (int) $admin['id']];
        Auth::forgetCache();
        mt_srand(7600);

        $categories = Database::connection()->query('SELECT name, id FROM categories')->fetchAll(\PDO::FETCH_KEY_PAIR);
        $suppliers = new SupplierRepository();
        $supplierIds = [];
        foreach (self::SUPPLIERS as [$name, $contact, $phone, $email, $address]) {
            $supplierIds[] = $suppliers->create(['name' => $name, 'contact_person' => $contact, 'phone' => $phone, 'email' => $email, 'address' => $address]);
        }

        $products = new ProductRepository();
        $stock = new StockService();
        $ids = [];
        $lastMove = (int) $this->fetchRow('SELECT COALESCE(MAX(id), 0) AS id FROM stock_movements')['id'];
        foreach (self::PRODUCTS as [$sku, $name, $category, $supplier, $brand, $model, $cost, $price, $reorder, $opening, $specs]) {
            $id = $products->create([
                'sku' => $sku, 'name' => $name, 'category_id' => $categories[$category] ?? null, 'supplier_id' => $supplierIds[$supplier],
                'brand' => $brand, 'model' => $model, 'description' => '', 'specs' => $specs,
                'cost_cents' => $cost * 100, 'price_cents' => $price * 100, 'reorder_level' => $reorder,
            ]);
            if ($opening > 0) {
                $stock->move($id, $opening, 'opening', null, null, 'Opening stock', $cost * 100);
            }
            $ids[$sku] = $id;
        }
        // Opening stock dated 15 days ago; only the rows added just now
        Database::connection()->prepare('UPDATE stock_movements SET created_at = ? WHERE id > ?')
            ->execute([date('Y-m-d 08:00:00', strtotime('-15 days')), $lastMove]);

        // One received order (with a short delivery) and one still on order
        $purchases = new PurchaseService();
        $po = $purchases->saveDraft(null, $supplierIds[0], [
            ['product_id' => $ids['SSD-NVME-1TB-G4'], 'qty' => 10, 'unit_cost' => 335000],
            ['product_id' => $ids['RAM-DDR5-16-5600'], 'qty' => 12, 'unit_cost' => 260000],
        ], 'Monthly restock', null);
        $purchases->markOrdered($po);
        $purchases->receive($po, []);
        $this->moveTo('purchase_orders', $po, 9);
        $open = $purchases->saveDraft(null, $supplierIds[1], [
            ['product_id' => $ids['GPU-NV-RTX4060-8G'], 'qty' => 6, 'unit_cost' => 1620000],
            ['product_id' => $ids['CPU-INT-I5-14400F'], 'qty' => 4, 'unit_cost' => 930000],
        ], 'Please deliver before Friday', date('Y-m-d', strtotime('+3 days')));
        $purchases->markOrdered($open);

        // Two weeks of counter sales
        $checkout = new CheckoutService();
        $popular = ['RAM-DDR5-16-5600', 'SSD-NVME-1TB-G4', 'FAN-120-ARGB-3P', 'CPU-AMD-R5-7600', 'MB-B650M-WIFI', 'PSU-650W-GOLD', 'RAM-DDR4-16-3200', 'HDD-2TB-7200'];
        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            for ($n = 0, $count = mt_rand(1, 4); $n < $count; $n++) {
                $lines = [];
                foreach (array_rand(array_flip($popular), mt_rand(1, 3)) as $sku) {
                    $lines[] = ['product_id' => $ids[$sku], 'qty' => mt_rand(1, 2)];
                }
                try {
                    $sale = $checkout->checkout($lines, 0, ['cash', 'cash', 'gcash', 'card'][mt_rand(0, 3)], 99999999, '');
                    $this->moveTo('sales', $sale['id'], $daysAgo, mt_rand(9, 19));
                } catch (\App\Services\StockException) {
                    // ran out of something: skip this sale
                }
            }
        }
        $this->getOutput()->writeln('<info>Demo data added: ' . count(self::SUPPLIERS) . ' suppliers, ' . count(self::PRODUCTS) . ' parts, 2 purchase orders and two weeks of sales.</info>');
    }

    /** Moves a record (and its stock movements) to N days ago, so charts have history. */
    private function moveTo(string $table, int $id, int $daysAgo, int $hour = 10): void
    {
        $when = date('Y-m-d', strtotime("-{$daysAgo} days")) . sprintf(' %02d:%02d:00', $hour, mt_rand(0, 59));
        $column = 'created_at';
        Database::connection()->prepare("UPDATE {$table} SET {$column} = ? WHERE id = ?")->execute([$when, $id]);
        if ($table === 'purchase_orders') {
            Database::connection()->prepare('UPDATE purchase_orders SET ordered_at = ?, received_at = ? WHERE id = ?')->execute([$when, $when, $id]);
        }
        $type = $table === 'sales' ? 'sale' : 'purchase_order';
        Database::connection()->prepare('UPDATE stock_movements SET created_at = ? WHERE reference_type = ? AND reference_id = ?')->execute([$when, $type, $id]);
    }

}
