<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\StockException;
use App\Services\StockService;
use Tests\TestCase;

final class InventoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCategories();
    }

    private function productData(array $overrides = []): array
    {
        return $overrides + [
            'sku' => 'cpu-amd-r5-7600', 'name' => 'AMD Ryzen 5 7600', 'category_id' => '1', 'supplier_id' => '',
            'brand' => 'AMD', 'model' => '7600', 'description' => '', 'cost_price' => '10,400', 'selling_price' => '11950.00',
            'reorder_level' => '5', 'opening_qty' => '14', 'specs' => ['Socket' => 'AM5', 'TDP' => '65 W', 'Bogus' => ''],
        ];
    }

    private function createProduct(array $overrides = []): array
    {
        $this->post('/products', $this->productData($overrides));

        return (array) $this->row('SELECT * FROM products WHERE sku = ?', [strtoupper($overrides['sku'] ?? 'CPU-AMD-R5-7600')]);
    }

    public function test_staff_add_a_product_with_opening_stock_in_the_ledger(): void
    {
        $this->actingAs('staff');
        $product = $this->createProduct();

        $this->assertSame('CPU-AMD-R5-7600', $product['sku'], 'SKUs are stored in upper case');
        $this->assertSame('10400.00', $product['cost_price']);
        $this->assertSame('11950.00', $product['selling_price']);
        $this->assertSame(14, (int) $product['qty_on_hand']);
        $this->assertSame(['Socket' => 'AM5', 'TDP' => '65 W'], json_decode($product['specs'], true), 'Blank spec values are dropped');
        $move = $this->row('SELECT * FROM stock_movements WHERE product_id = ?', [$product['id']]);
        $this->assertSame(['opening', 14, 14], [$move['type'], (int) $move['qty_change'], (int) $move['balance_after']]);
    }

    public function test_product_validation(): void
    {
        $this->actingAs('admin');
        $this->createProduct();

        $this->post('/products', $this->productData(['name' => 'Duplicate']));
        $this->assertSame('Another product already uses this SKU.', $this->flashError());

        $this->post('/products', $this->productData(['sku' => 'NEW-1', 'selling_price' => '12.345']));
        $this->assertSame('Selling price must be an amount like 1250 or 1,250.50.', $this->flashError());

        $this->post('/products', $this->productData(['sku' => 'bad sku!']));
        $this->assertSame('SKU has an invalid format.', $this->flashError());
        $this->assertSame(1, (int) $this->value('SELECT COUNT(*) FROM products'));
    }

    public function test_employees_can_view_but_not_change_products(): void
    {
        $this->actingAs('admin');
        $product = $this->createProduct();
        $this->actingAs('employee');

        $this->assertSame(200, $this->get('/products')->status);
        $this->assertSame(200, $this->get('/products/' . $product['id'])->status);
        $this->assertSame(403, $this->get('/products/new')->status);
        $this->assertSame(403, $this->post('/products/' . $product['id'], $this->productData(['selling_price' => '1']))->status);
        $this->assertSame(403, $this->post('/products/' . $product['id'] . '/adjust', ['mode' => 'set', 'qty' => '0', 'reason' => 'Lost / stolen'])->status);
        $this->assertSame('11950.00', $this->value('SELECT selling_price FROM products WHERE id = ?', [$product['id']]));
    }

    public function test_editing_never_changes_quantity_directly(): void
    {
        $this->actingAs('admin');
        $product = $this->createProduct();

        $this->post('/products/' . $product['id'], $this->productData(['name' => 'Ryzen 5 7600 BOX', 'qty_on_hand' => '999']));

        $row = $this->row('SELECT name, qty_on_hand FROM products WHERE id = ?', [$product['id']]);
        $this->assertSame(['Ryzen 5 7600 BOX', 14], [$row['name'], (int) $row['qty_on_hand']]);
    }

    public function test_adjustments_are_logged_and_cannot_go_below_zero(): void
    {
        $this->actingAs('staff');
        $product = $this->createProduct();
        $url = '/products/' . $product['id'] . '/adjust';

        $this->post($url, ['mode' => 'remove', 'qty' => '20', 'reason' => 'Damaged']);
        $this->assertStringContainsString('Not enough stock', (string) $this->flashError());
        $this->assertSame(14, (int) $this->value('SELECT qty_on_hand FROM products WHERE id = ?', [$product['id']]));

        $this->post($url, ['mode' => 'set', 'qty' => '12', 'reason' => 'Stock count', 'note' => 'Monthly count']);
        $this->post($url, ['mode' => 'add', 'qty' => '1', 'reason' => 'Customer return']);

        $moves = \App\Core\Database::connection()->query('SELECT type, qty_change, balance_after, note FROM stock_movements ORDER BY id')->fetchAll();
        $this->assertSame(
            [['opening', 14, 14], ['adjustment', -2, 12], ['return', 1, 13]],
            array_map(static fn ($m) => [$m['type'], (int) $m['qty_change'], (int) $m['balance_after']], $moves),
        );
        $this->assertSame('Stock count: Monthly count', $moves[1]['note']);
        $this->assertSame(13, (int) $this->value('SELECT qty_on_hand FROM products WHERE id = ?', [$product['id']]));
    }

    public function test_stock_service_refuses_negative_balances(): void
    {
        $this->actingAs('admin');
        $product = $this->createProduct(['opening_qty' => '1']);

        $this->expectException(StockException::class);
        (new StockService())->move((int) $product['id'], -2, 'sale');
    }

    public function test_filters_and_low_stock_alert(): void
    {
        $this->actingAs('admin');
        $this->createProduct(['sku' => 'GPU-4060', 'name' => 'RTX 4060', 'category_id' => '2', 'opening_qty' => '3', 'reorder_level' => '4']);
        $this->createProduct(['sku' => 'RAM-32', 'name' => 'DDR5 32GB', 'category_id' => '3', 'opening_qty' => '0']);
        $this->createProduct(['sku' => 'SSD-1TB', 'name' => 'NVMe 1TB', 'category_id' => '4', 'opening_qty' => '27']);

        $low = $this->tableBody($this->get('/products', ['status' => 'low']));
        $this->assertStringContainsString('RTX 4060', $low);
        $this->assertStringContainsString('DDR5 32GB', $low);
        $this->assertStringNotContainsString('NVMe 1TB', $low);
        $this->assertSeeText('2 LOW', $this->get('/'));

        $search = $this->tableBody($this->get('/products', ['q' => 'ssd']));
        $this->assertStringContainsString('NVMe 1TB', $search);
        $this->assertStringNotContainsString('RTX 4060', $search);
    }

    public function test_csv_export_neutralises_formulas(): void
    {
        $this->actingAs('admin');
        $this->createProduct(['name' => '=HYPERLINK("http://evil")']);

        $csv = $this->get('/products', ['export' => 'csv']);

        $this->assertStringContainsString('text/csv', $csv->headers['Content-Type']);
        $this->assertStringContainsString("'=HYPERLINK", $csv->body);
    }

    public function test_archived_products_leave_the_list_but_keep_history(): void
    {
        $this->actingAs('admin');
        $product = $this->createProduct();
        $this->post('/products/' . $product['id'] . '/archive');

        $this->assertStringNotContainsString('AMD Ryzen 5 7600', $this->tableBody($this->get('/products')));
        $this->assertStringContainsString('AMD Ryzen 5 7600', $this->tableBody($this->get('/products', ['show' => 'archived'])));
        $this->assertSame(1, (int) $this->value('SELECT COUNT(*) FROM stock_movements WHERE product_id = ?', [$product['id']]));
    }

    public function test_image_upload_accepts_real_images_only(): void
    {
        $this->actingAs('admin');
        $png = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        $fake = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($fake, '<?php echo "pwned";');

        $this->post('/products', $this->productData(['sku' => 'FAKE-1']), files: ['image' => ['tmp_name' => $fake, 'name' => 'x.png', 'size' => 20, 'error' => UPLOAD_ERR_OK, 'type' => 'image/png']]);
        $this->assertSame('Use a JPG, PNG, WEBP or GIF image.', $this->flashError());

        $this->post('/products', $this->productData(['sku' => 'REAL-1']), files: ['image' => ['tmp_name' => $png, 'name' => 'evil.php', 'size' => 70, 'error' => UPLOAD_ERR_OK, 'type' => 'image/png']]);
        $url = (string) $this->value("SELECT image_url FROM products WHERE sku = 'REAL-1'");
        $this->assertMatchesRegularExpression('~^/uploads/products/[a-f0-9]{24}\.png$~', $url, 'Saved with a random name and the real extension');
        $this->assertFileExists(BASE_DIR . '/public' . $url);
        unlink(BASE_DIR . '/public' . $url);
    }

    public function test_categories_and_suppliers(): void
    {
        $this->actingAs('staff');

        $this->post('/categories', ['name' => 'Monitors', 'spec_fields' => "Size\nPanel, Refresh rate\nsize"]);
        $this->assertSame('["Size","Panel","Refresh rate"]', $this->value("SELECT spec_fields FROM categories WHERE name = 'Monitors'"));
        $this->post('/categories', ['name' => 'Monitors']);
        $this->assertSame('A category with that name already exists.', $this->flashError());

        $cpu = (int) $this->value("SELECT id FROM categories WHERE name = 'CPU'");
        $this->createProduct(['category_id' => (string) $cpu]);
        $this->post('/categories/' . $cpu . '/delete');
        $this->assertStringContainsString('still has 1 product', (string) $this->flashError());

        $this->post('/suppliers', ['name' => 'PC Hub Distributors', 'email' => 'not-an-email']);
        $this->assertSame('Enter a valid email address.', $this->flashError());
        $this->post('/suppliers', ['name' => 'PC Hub Distributors', 'phone' => '0917 000 0000']);
        $id = (int) $this->value("SELECT id FROM suppliers WHERE name = 'PC Hub Distributors'");
        $this->assertSame(200, $this->get('/suppliers/' . $id)->status);
        $this->post('/suppliers/' . $id . '/archive');
        $this->assertNotNull($this->value('SELECT archived_at FROM suppliers WHERE id = ?', [$id]));
    }
}
