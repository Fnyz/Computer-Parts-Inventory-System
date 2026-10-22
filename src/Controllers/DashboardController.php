<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ActivityRepository;
use App\Repositories\InsightRepository;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\SaleRepository;
use App\Repositories\StockMovementRepository;

final class DashboardController extends Controller
{
    public const CHART_DAYS = 14;

    public function index(Request $request): Response
    {
        $insights = new InsightRepository();
        $today = date('Y-m-d');
        $from = date('Y-m-d', strtotime('-' . (self::CHART_DAYS - 1) . ' days'));

        $sales = (new SaleRepository())->byDay($from, $today);
        $received = Auth::can('po.view') ? (new PurchaseOrderRepository())->receivedByDay($from, $today) : [];
        $days = [];
        for ($i = self::CHART_DAYS - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $days[] = ['date' => $day, 'sales' => $sales[$day]['revenue'] ?? 0, 'purchases' => $received[$day] ?? 0];
        }

        $week = $insights->salesTotals(date('Y-m-d', strtotime('-13 days')), date('Y-m-d', strtotime('-7 days')));
        $thisWeek = $insights->salesTotals(date('Y-m-d', strtotime('-6 days')), $today);

        return $this->view('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'stock' => $insights->stockTotals(),
            'today' => $insights->salesTotals($today, $today),
            'month' => $insights->salesTotals(date('Y-m-01'), $today),
            'weekChange' => $week['revenue'] > 0 ? ($thisWeek['revenue'] - $week['revenue']) / $week['revenue'] * 100 : null,
            'days' => $days,
            'showPurchases' => Auth::can('po.view'),
            'categories' => $insights->categories(),
            'restock' => (new ProductRepository())->lowStock(6),
            'activity' => Auth::can('activity.view') ? (new ActivityRepository())->recent(7) : [],
            'moves' => Auth::can('activity.view') ? [] : (new StockMovementRepository())->recent(7),
        ]);
    }
}
