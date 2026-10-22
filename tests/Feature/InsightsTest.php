<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class InsightsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCategories();
        $this->actingAs('admin');
        $this->post('/products', ['sku' => 'SSD-1TB', 'name' => 'NVMe 1TB', 'category_id' => '4', 'cost_price' => '3350', 'selling_price' => '3990', 'reorder_level' => '8', 'opening_qty' => '10']);
        $this->post('/products', ['sku' => 'RAM-16', 'name' => 'DDR5 16GB', 'category_id' => '3', 'cost_price' => '2600', 'selling_price' => '3050', 'reorder_level' => '8', 'opening_qty' => '3']);
        $id = (int) $this->value("SELECT id FROM products WHERE sku = 'SSD-1TB'");
        $this->postJson('/pos/checkout', ['lines' => [['product_id' => $id, 'qty' => 2]], 'payment_method' => 'cash', 'amount_paid' => '8000', 'discount' => '0']);
    }

    public function test_dashboard_shows_stock_sales_and_restock_list_for_every_role(): void
    {
        $admin = $this->get('/');
        $this->assertSame(200, $admin->status);
        $this->assertSeeText('₱7,980.00', $admin, 'Sales today');
        $this->assertSeeText('₱34,600.00', $admin, 'Stock value at cost: 8 × 3,350 + 3 × 2,600');
        $this->assertStringContainsString('DDR5 16GB', $admin->body, 'In the restock list');
        $this->assertSeeText('Activity log', $admin);

        foreach (['staff', 'employee'] as $role) {
            $this->actingAs($role);
            $page = $this->get('/');
            $this->assertSame(200, $page->status, $role);
            $this->assertSeeText('Latest stock movements', $page);
        }
        $this->assertStringNotContainsString('STOCK VALUE', $page->body, 'Employees don’t see cost figures');
    }

    public function test_reports_and_exports(): void
    {
        $page = $this->get('/reports');
        $this->assertSame(200, $page->status);
        $this->assertSeeText('₱7,980.00', $page);
        $this->assertSeeText('₱1,280.00', $page, 'Gross profit: 2 × (3,990 − 3,350)');

        $daily = $this->get('/reports', ['export' => 'daily']);
        $this->assertStringContainsString(date('Y-m-d') . ',1,7980.00,1280.00', $daily->body);
        $this->assertStringContainsString('SSD-1TB,"NVMe 1TB",2,7980.00,1280.00', $this->get('/reports', ['export' => 'top'])->body);
        $this->assertStringStartsWith('%PDF', $this->get('/reports', ['export' => 'pdf'])->body);

        $this->actingAs('employee');
        $this->assertSame(403, $this->get('/reports')->status);
    }

    public function test_activity_log_is_admin_only_and_records_changes(): void
    {
        $page = $this->get('/activity');
        $this->assertSame(200, $page->status);
        $this->assertSeeText('Completed sale', $page);
        $this->assertSeeText('Created product', $page);

        $this->actingAs('staff');
        $this->assertSame(403, $this->get('/activity')->status);
    }

    public function test_labels_include_a_barcode(): void
    {
        $id = (int) $this->value("SELECT id FROM products WHERE sku = 'SSD-1TB'");
        $page = $this->get('/products/' . $id . '/label', ['copies' => '3', 'price' => '0']);

        $this->assertSame(3, substr_count($page->body, 'aria-label="Barcode for SSD-1TB"'));
        $this->assertStringNotContainsString('₱3,990.00', $page->body);
    }
}
