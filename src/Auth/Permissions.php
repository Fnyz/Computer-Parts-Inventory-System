<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * What each account type may do. Admin can do everything.
 * Staff run the inventory and purchasing; Employees sell at the counter.
 */
final class Permissions
{
    public const ROLES = ['admin' => 'Admin', 'staff' => 'Staff', 'employee' => 'Employee'];

    private const MAP = [
        'staff' => [
            'dashboard.view', 'products.view', 'products.manage', 'categories.manage',
            'suppliers.view', 'suppliers.manage', 'stock.view', 'stock.adjust',
            'po.view', 'po.manage', 'pos.use', 'sales.view', 'reports.view',
        ],
        'employee' => [
            'dashboard.view', 'products.view', 'stock.view', 'pos.use', 'sales.view',
        ],
    ];

    public static function allows(string $role, string $ability): bool
    {
        return $role === 'admin' || in_array($ability, self::MAP[$role] ?? [], true);
    }

    public static function label(string $role): string
    {
        return self::ROLES[$role] ?? ucfirst($role);
    }
}
