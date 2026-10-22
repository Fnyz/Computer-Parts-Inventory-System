<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csv;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use App\Services\StockService;

/** The stock ledger: every movement, newest first. */
final class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q'),
            'type' => array_key_exists($request->string('type'), StockService::TYPES) ? $request->string('type') : '',
            'product' => $request->int('product'),
            'from' => $this->date($request->string('from')),
            'to' => $this->date($request->string('to')),
        ];
        $moves = new StockMovementRepository();
        if ($request->string('export') === 'csv') {
            return $this->csv($moves->all($filters));
        }

        return $this->view('stock/index', [
            'pageTitle' => 'Stock movements',
            'filters' => $filters,
            'product' => $filters['product'] ? (new ProductRepository())->find($filters['product']) : null,
            'result' => $moves->paginate($filters, $request->int('page', 1)),
        ]);
    }

    private function date(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function csv(array $rows): Response
    {
        return Csv::download(
            'stock-movements-' . date('Y-m-d') . '.csv',
            ['Date', 'SKU', 'Product', 'Type', 'Change', 'Balance', 'Unit cost', 'Reference', 'By', 'Note'],
            array_map(static fn (array $m) => [
                $m['created_at'], $m['sku'], $m['product_name'], StockService::TYPES[$m['type']] ?? $m['type'],
                $m['qty_change'], $m['balance_after'], $m['unit_cost'], StockMovementRepository::reference($m)[1] ?? '',
                $m['user_name'], $m['note'],
            ], $rows),
        );
    }
}
