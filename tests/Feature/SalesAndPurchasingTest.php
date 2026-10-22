<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\SupplierRepository;
use App\Services\SequenceService;
use App\Services\StockService;
use Tests\TestCase;

final class SalesAndPurchasingTest extends TestCase
{
    private int $cpu;
    private int $ram;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCategories();
        $this->actingAs('admin');
        $this->cpu = $this->product('CPU-R5-7600', 'Ryzen 5 7600', '10400', '11950', 14);
        $this->ram = $this->product('RAM-DDR5-16', 'DDR5 16GB', '2600', '3050', 18);
    }

    private function product(string $sku, string $name, string $cost, string $price, int $qty): int
    {
        $this->post('/products', [
            'sku' => $sku, 'name' => $name, 'category_id' => '1', 'cost_price' => $cost, 'selling_price' => $price,
            'reorder_level' => '4', 'opening_qty' => (string) $qty,
        ]);

        return (int) $this->value('SELECT id FROM products WHERE sku = ?', [$sku]);
    }

    private function qty(int $productId): int
    {
        return (int) $this->value('SELECT qty_on_hand FROM products WHERE id = ?', [$productId]);
    }

    private function checkout(array $body): \App\Core\Response
    {
        return $this->postJson('/pos/checkout', $body + ['payment_method' => 'cash', 'discount' => '0', 'customer_name' => '']);
    }

    public function test_checkout_uses_database_prices_not_the_browsers(): void
    {
        $response = $this->checkout([
            'lines' => [['product_id' => $this->cpu, 'qty' => 1, 'price' => 1], ['product_id' => $this->ram, 'qty' => 2, 'unit_price' => 1]],
            'total' => '1.00',
            'amount_paid' => '20000',
        ]);

        $this->assertSame(201, $response->status, $response->body);
        $body = json_decode($response->body, true);
        $this->assertSame('SO-' . date('Y') . '-00001', $body['number']);
        $this->assertSame(1805000, $body['total'], '11,950 + 2 × 3,050');
        $this->assertSame(195000, $body['change']);
        $this->assertSame([13, 16], [$this->qty($this->cpu), $this->qty($this->ram)]);

        $sale = $this->row('SELECT * FROM sales WHERE id = ?', [$body['id']]);
        $this->assertSame(['18050.00', '20000.00', '1950.00'], [$sale['total'], $sale['amount_paid'], $sale['change_due']]);
        $this->assertSame('10400.00', $this->value('SELECT unit_cost FROM sale_items WHERE product_id = ?', [$this->cpu]), 'Cost is kept for profit reports');
        $this->assertSame(2, (int) $this->value("SELECT COUNT(*) FROM stock_movements WHERE type = 'sale' AND reference_id = ?", [$body['id']]));
    }

    public function test_checkout_rejects_overselling_and_short_payments(): void
    {
        $over = $this->checkout(['lines' => [['product_id' => $this->cpu, 'qty' => 15]], 'amount_paid' => '999999']);
        $this->assertSame(422, $over->status);
        $this->assertStringContainsString('Not enough stock for Ryzen 5 7600', json_decode($over->body, true)['error']);

        $short = $this->checkout(['lines' => [['product_id' => $this->cpu, 'qty' => 1]], 'amount_paid' => '100']);
        $this->assertSame(422, $short->status);
        $this->assertStringContainsString('less than the total of ₱11,950.00', json_decode($short->body, true)['error']);

        $this->assertSame(14, $this->qty($this->cpu), 'Nothing was sold');
        $this->assertSame(0, (int) $this->value('SELECT COUNT(*) FROM sales'));
        $this->assertSame('SO-' . date('Y') . '-00001', (new SequenceService())->next('SO'), 'Failed checkouts don’t use up receipt numbers');
    }

    public function test_the_same_part_twice_in_the_cart_is_checked_as_one_line(): void
    {
        $response = $this->checkout(['lines' => [['product_id' => $this->cpu, 'qty' => 10], ['product_id' => $this->cpu, 'qty' => 5]], 'amount_paid' => '999999']);

        $this->assertSame(422, $response->status, '15 in total but only 14 in stock');
    }

    public function test_gcash_is_paid_exactly_and_receipt_numbers_are_sequential(): void
    {
        $first = json_decode($this->checkout(['lines' => [['product_id' => $this->ram, 'qty' => 1]], 'payment_method' => 'gcash', 'amount_paid' => null])->body, true);
        $second = json_decode($this->checkout(['lines' => [['product_id' => $this->ram, 'qty' => 1]], 'amount_paid' => '3050'])->body, true);

        $this->assertSame(0, $first['change']);
        $this->assertSame('3050.00', $this->value('SELECT amount_paid FROM sales WHERE id = ?', [$first['id']]));
        $this->assertSame(['SO-' . date('Y') . '-00001', 'SO-' . date('Y') . '-00002'], [$first['number'], $second['number']]);
    }

    public function test_only_staff_and_admins_can_give_discounts(): void
    {
        $discounted = json_decode($this->checkout(['lines' => [['product_id' => $this->ram, 'qty' => 1]], 'discount' => '50', 'amount_paid' => '3000'])->body, true);
        $this->assertSame(300000, $discounted['total']);

        $this->actingAs('employee');
        $employee = json_decode($this->checkout(['lines' => [['product_id' => $this->ram, 'qty' => 1]], 'discount' => '3000', 'amount_paid' => '3050'])->body, true);
        $this->assertSame(305000, $employee['total'], 'An Employee’s discount is ignored');
    }

    public function test_voiding_a_sale_returns_the_stock_once(): void
    {
        $sale = json_decode($this->checkout(['lines' => [['product_id' => $this->cpu, 'qty' => 2]], 'amount_paid' => '23900'])->body, true);
        $this->assertSame(12, $this->qty($this->cpu));

        $this->post('/sales/' . $sale['id'] . '/void', ['reason' => '']);
        $this->assertSame('Enter why this sale is being voided.', $this->flashError());

        $this->post('/sales/' . $sale['id'] . '/void', ['reason' => 'Customer changed their mind']);
        $this->post('/sales/' . $sale['id'] . '/void', ['reason' => 'Again']);

        $this->assertSame(14, $this->qty($this->cpu), 'Stock comes back exactly once');
        $this->assertSame('voided', $this->value('SELECT status FROM sales WHERE id = ?', [$sale['id']]));
        $this->assertSame('This sale was already voided.', $this->flashError());
    }

    public function test_employees_sell_but_cannot_void_or_purchase(): void
    {
        $sale = json_decode($this->checkout(['lines' => [['product_id' => $this->cpu, 'qty' => 1]], 'amount_paid' => '11950'])->body, true);
        $this->actingAs('employee');

        $this->assertSame(200, $this->get('/pos')->status);
        $this->assertSame(200, $this->get('/sales/' . $sale['id'])->status);
        $this->assertSame(403, $this->post('/sales/' . $sale['id'] . '/void', ['reason' => 'x'])->status);
        $this->assertSame(403, $this->get('/purchase-orders')->status);
    }

    public function test_receipts_render_as_html_and_pdf(): void
    {
        $sale = json_decode($this->checkout(['lines' => [['product_id' => $this->cpu, 'qty' => 1]], 'amount_paid' => '12000', 'customer_name' => 'Juan <b>Dela Cruz</b>'])->body, true);

        $html = $this->get('/sales/' . $sale['id'] . '/receipt');
        $this->assertSeeText($sale['number'], $html);
        $this->assertStringContainsString('Juan &lt;b&gt;Dela Cruz&lt;/b&gt;', $html->body, 'Customer names are escaped');

        $pdf = $this->get('/sales/' . $sale['id'] . '/receipt.pdf');
        $this->assertSame('application/pdf', $pdf->headers['Content-Type']);
        $this->assertStringStartsWith('%PDF', $pdf->body);
    }

    public function test_purchase_order_flow_adds_stock_and_updates_cost(): void
    {
        $supplier = (new SupplierRepository())->create(['name' => 'PC Hub Distributors']);

        $this->post('/purchase-orders', ['supplier_id' => (string) $supplier, 'lines' => [
            ['product_id' => (string) $this->cpu, 'qty' => '10', 'unit_cost' => '10,200'],
            ['product_id' => '', 'qty' => '1', 'unit_cost' => ''],
            ['product_id' => (string) $this->ram, 'qty' => '5', 'unit_cost' => '2550.50'],
        ]]);
        $po = $this->row('SELECT * FROM purchase_orders');
        $this->assertSame(['PO-' . date('Y') . '-00001', 'draft', '114752.50'], [$po['po_number'], $po['status'], $po['total']]);
        $this->assertSame(14, $this->qty($this->cpu), 'Drafts don’t touch stock');

        $this->post('/purchase-orders/' . $po['id'] . '/receive', []);
        $this->assertSame('Mark the order as ordered before receiving it.', $this->flashError());

        $this->post('/purchase-orders/' . $po['id'] . '/order');
        $items = \App\Core\Database::connection()->query('SELECT id, product_id FROM purchase_order_items ORDER BY id')->fetchAll(\PDO::FETCH_KEY_PAIR);
        $received = array_combine(array_keys($items), [8, 5]); // only 8 of 10 CPUs arrived
        $this->post('/purchase-orders/' . $po['id'] . '/receive', ['received' => $received, 'update_costs' => '1']);

        $this->assertSame([22, 23], [$this->qty($this->cpu), $this->qty($this->ram)]);
        $this->assertSame('10200.00', $this->value('SELECT cost_price FROM products WHERE id = ?', [$this->cpu]));
        $this->assertSame('received', $this->value('SELECT status FROM purchase_orders WHERE id = ?', [$po['id']]));
        $this->assertSame(8, (int) $this->value('SELECT qty_received FROM purchase_order_items WHERE product_id = ?', [$this->cpu]));

        $this->post('/purchase-orders/' . $po['id'] . '/receive', ['received' => $received]);
        $this->assertSame('This order was already received.', $this->flashError());
        $this->assertSame(22, $this->qty($this->cpu), 'Receiving twice does not double the stock');
        $this->assertSame(2, (int) $this->value("SELECT COUNT(*) FROM stock_movements WHERE type = 'purchase'"));
    }

    public function test_purchase_order_validation_and_cancel(): void
    {
        $supplier = (new SupplierRepository())->create(['name' => 'PC Hub']);

        $this->post('/purchase-orders', ['supplier_id' => (string) $supplier, 'lines' => [['product_id' => '', 'qty' => '1', 'unit_cost' => '']]]);
        $this->assertSame('Add at least one part to the order.', $this->flashError());
        $this->post('/purchase-orders', ['supplier_id' => (string) $supplier, 'lines' => [['product_id' => (string) $this->cpu, 'qty' => '0', 'unit_cost' => '1']]]);
        $this->assertSame('Each line needs a quantity of 1 or more.', $this->flashError());

        $this->post('/purchase-orders', ['supplier_id' => (string) $supplier, 'lines' => [['product_id' => (string) $this->cpu, 'qty' => '2', 'unit_cost' => '1']]]);
        $id = (int) $this->value('SELECT id FROM purchase_orders');
        $this->post('/purchase-orders/' . $id . '/cancel');
        $this->assertSame('cancelled', $this->value('SELECT status FROM purchase_orders WHERE id = ?', [$id]));
        $this->post('/purchase-orders/' . $id . '/order');
        $this->assertSame('Only a draft can be marked as ordered.', $this->flashError());
    }

    public function test_stock_types_cover_every_movement(): void
    {
        $this->assertSame(['opening', 'purchase', 'sale', 'void', 'adjustment', 'return'], array_keys(StockService::TYPES));
    }
}
