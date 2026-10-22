<?php

declare(strict_types=1);

use App\Controllers\ActivityController;
use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\LabelController;
use App\Controllers\PosController;
use App\Controllers\ProductController;
use App\Controllers\ProfileController;
use App\Controllers\PurchaseOrderController;
use App\Controllers\ReportController;
use App\Controllers\SaleController;
use App\Controllers\StockController;
use App\Controllers\SupplierController;
use App\Controllers\UserController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/health', [HealthController::class, 'show']);

    $r->group(['guest'], static function (Router $r): void {
        $r->get('/login', [AuthController::class, 'showLogin']);
        $r->post('/login', [AuthController::class, 'login']);
        $r->get('/setup', [AuthController::class, 'showSetup']);
        $r->post('/setup', [AuthController::class, 'setup']);
    });

    $r->group(['auth'], static function (Router $r): void {
        $r->post('/logout', [AuthController::class, 'logout']);
        $r->get('/', [DashboardController::class, 'index'], ['can:dashboard.view']);

        $r->get('/profile', [ProfileController::class, 'edit']);
        $r->post('/profile', [ProfileController::class, 'update']);
        $r->post('/profile/theme', [ProfileController::class, 'theme']);

        // ---- Inventory ----
        $r->get('/products', [ProductController::class, 'index'], ['can:products.view']);
        $r->get('/products/new', [ProductController::class, 'create'], ['can:products.manage']);
        $r->post('/products', [ProductController::class, 'store'], ['can:products.manage']);
        $r->get('/products/{id:\d+}', [ProductController::class, 'show'], ['can:products.view']);
        $r->get('/products/{id:\d+}/edit', [ProductController::class, 'edit'], ['can:products.manage']);
        $r->post('/products/{id:\d+}', [ProductController::class, 'update'], ['can:products.manage']);
        $r->post('/products/{id:\d+}/archive', [ProductController::class, 'archive'], ['can:products.manage']);
        $r->get('/products/{id:\d+}/label', [LabelController::class, 'show'], ['can:products.view']);
        $r->get('/products/{id:\d+}/adjust', [ProductController::class, 'adjustForm'], ['can:stock.adjust']);
        $r->post('/products/{id:\d+}/adjust', [ProductController::class, 'adjust'], ['can:stock.adjust']);

        $r->group(['can:categories.manage'], static function (Router $r): void {
            $r->get('/categories', [CategoryController::class, 'index']);
            $r->post('/categories', [CategoryController::class, 'store']);
            $r->get('/categories/{id:\d+}/edit', [CategoryController::class, 'edit']);
            $r->post('/categories/{id:\d+}', [CategoryController::class, 'update']);
            $r->post('/categories/{id:\d+}/delete', [CategoryController::class, 'destroy']);
        });

        $r->get('/stock', [StockController::class, 'index'], ['can:stock.view']);

        $r->get('/suppliers', [SupplierController::class, 'index'], ['can:suppliers.view']);
        $r->get('/suppliers/new', [SupplierController::class, 'create'], ['can:suppliers.manage']);
        $r->post('/suppliers', [SupplierController::class, 'store'], ['can:suppliers.manage']);
        $r->get('/suppliers/{id:\d+}', [SupplierController::class, 'show'], ['can:suppliers.view']);
        $r->get('/suppliers/{id:\d+}/edit', [SupplierController::class, 'edit'], ['can:suppliers.manage']);
        $r->post('/suppliers/{id:\d+}', [SupplierController::class, 'update'], ['can:suppliers.manage']);
        $r->post('/suppliers/{id:\d+}/archive', [SupplierController::class, 'archive'], ['can:suppliers.manage']);

        // ---- Transactions ----
        $r->get('/pos', [PosController::class, 'index'], ['can:pos.use']);
        $r->post('/pos/checkout', [PosController::class, 'checkout'], ['can:pos.use']);

        $r->get('/sales', [SaleController::class, 'index'], ['can:sales.view']);
        $r->get('/sales/{id:\d+}', [SaleController::class, 'show'], ['can:sales.view']);
        $r->get('/sales/{id:\d+}/receipt', [SaleController::class, 'receipt'], ['can:sales.view']);
        $r->get('/sales/{id:\d+}/receipt.pdf', [SaleController::class, 'receiptPdf'], ['can:sales.view']);
        $r->post('/sales/{id:\d+}/void', [SaleController::class, 'void'], ['can:sales.void']);

        $r->get('/purchase-orders', [PurchaseOrderController::class, 'index'], ['can:po.view']);
        $r->get('/purchase-orders/new', [PurchaseOrderController::class, 'create'], ['can:po.manage']);
        $r->post('/purchase-orders', [PurchaseOrderController::class, 'store'], ['can:po.manage']);
        $r->get('/purchase-orders/{id:\d+}', [PurchaseOrderController::class, 'show'], ['can:po.view']);
        $r->get('/purchase-orders/{id:\d+}/edit', [PurchaseOrderController::class, 'edit'], ['can:po.manage']);
        $r->post('/purchase-orders/{id:\d+}', [PurchaseOrderController::class, 'update'], ['can:po.manage']);
        $r->post('/purchase-orders/{id:\d+}/{action:order|receive|cancel}', [PurchaseOrderController::class, 'transition'], ['can:po.manage']);

        // ---- Reports & admin ----
        $r->get('/reports', [ReportController::class, 'index'], ['can:reports.view']);
        $r->get('/activity', [ActivityController::class, 'index'], ['can:activity.view']);

        $r->group(['can:users.manage'], static function (Router $r): void {
            $r->get('/users', [UserController::class, 'index']);
            $r->get('/users/new', [UserController::class, 'create']);
            $r->post('/users', [UserController::class, 'store']);
            $r->get('/users/{id:\d+}/edit', [UserController::class, 'edit']);
            $r->post('/users/{id:\d+}', [UserController::class, 'update']);
        });
    });
};
