<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Auth\Permissions;
use PHPUnit\Framework\TestCase;

final class PermissionsTest extends TestCase
{
    public function test_admin_can_do_everything(): void
    {
        $this->assertTrue(Permissions::allows('admin', 'users.manage'));
        $this->assertTrue(Permissions::allows('admin', 'anything.at.all'));
    }

    public function test_staff_run_inventory_but_not_accounts(): void
    {
        $this->assertTrue(Permissions::allows('staff', 'po.manage'));
        $this->assertTrue(Permissions::allows('staff', 'stock.adjust'));
        $this->assertFalse(Permissions::allows('staff', 'users.manage'));
        $this->assertFalse(Permissions::allows('staff', 'activity.view'));
    }

    public function test_employees_sell_and_view_only(): void
    {
        $this->assertTrue(Permissions::allows('employee', 'pos.use'));
        $this->assertTrue(Permissions::allows('employee', 'products.view'));
        $this->assertFalse(Permissions::allows('employee', 'products.manage'));
        $this->assertFalse(Permissions::allows('employee', 'po.manage'));
        $this->assertFalse(Permissions::allows('employee', 'stock.adjust'));
    }

    public function test_unknown_roles_get_nothing(): void
    {
        $this->assertFalse(Permissions::allows('owner', 'dashboard.view'));
    }
}
