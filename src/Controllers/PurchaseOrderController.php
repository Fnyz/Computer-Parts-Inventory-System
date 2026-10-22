<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\SupplierRepository;
use App\Services\ActivityLogger;
use App\Services\PurchaseService;
use App\Services\StockException;

final class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'status' => array_key_exists($request->string('status'), PurchaseOrderRepository::STATUSES) ? $request->string('status') : '',
            'supplier' => $request->int('supplier'),
            'q' => $request->string('q'),
        ];
        $orders = new PurchaseOrderRepository();

        return $this->view('purchase-orders/index', [
            'pageTitle' => 'Purchase orders',
            'filters' => $filters,
            'result' => $orders->paginate($filters, $request->int('page', 1)),
            'counts' => $orders->countByStatus(),
        ]);
    }

    public function show(Request $request): Response
    {
        $orders = new PurchaseOrderRepository();
        $po = $this->findOr404($orders->find((int) $request->param('id')));

        return $this->view('purchase-orders/show', ['pageTitle' => $po['po_number'], 'po' => $po, 'items' => $orders->items((int) $po['id'])]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null, [], $request->int('supplier'), $request->int('product'));
    }

    public function store(Request $request): Response
    {
        try {
            $id = (new PurchaseService())->saveDraft(null, $request->int('supplier_id'), $this->lines($request), $request->string('notes'), $this->date($request));
        } catch (StockException $e) {
            return $this->backWithErrors($request, '/purchase-orders/new', ['lines' => $e->getMessage()]);
        }
        $po = (array) (new PurchaseOrderRepository())->find($id);
        ActivityLogger::log('created purchase order', 'purchase_order', $id, ['number' => $po['po_number']]);

        return $this->redirect('/purchase-orders/' . $id, "Draft {$po['po_number']} saved. Mark it as ordered when you send it to the supplier.");
    }

    public function edit(Request $request): Response
    {
        $orders = new PurchaseOrderRepository();
        $po = $this->findOr404($orders->find((int) $request->param('id')));
        if ($po['status'] !== 'draft') {
            return $this->redirect('/purchase-orders/' . $po['id']);
        }

        return $this->form($po, $orders->items((int) $po['id']), (int) $po['supplier_id'], 0);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->findOr404((new PurchaseOrderRepository())->find($id));
        try {
            (new PurchaseService())->saveDraft($id, $request->int('supplier_id'), $this->lines($request), $request->string('notes'), $this->date($request));
        } catch (StockException $e) {
            return $this->backWithErrors($request, "/purchase-orders/{$id}/edit", ['lines' => $e->getMessage()]);
        }
        ActivityLogger::log('updated purchase order', 'purchase_order', $id);

        return $this->redirect('/purchase-orders/' . $id, 'Draft saved.');
    }

    /** status actions: order | receive | cancel */
    public function transition(Request $request): Response
    {
        $orders = new PurchaseOrderRepository();
        $po = $this->findOr404($orders->find((int) $request->param('id')));
        $id = (int) $po['id'];
        $service = new PurchaseService();
        try {
            switch ($request->param('action')) {
                case 'order':
                    $service->markOrdered($id);
                    $message = "{$po['po_number']} marked as ordered.";
                    break;
                case 'receive':
                    $received = [];
                    foreach ((array) $request->input('received', []) as $itemId => $qty) {
                        $received[(int) $itemId] = is_numeric($qty) ? (int) $qty : -1;
                    }
                    $units = $service->receive($id, $received, (bool) $request->input('update_costs'));
                    $message = "Received {$units} unit(s). Stock is updated.";
                    break;
                case 'cancel':
                    $service->cancel($id);
                    $message = "{$po['po_number']} cancelled.";
                    break;
                default:
                    return $this->redirect('/purchase-orders/' . $id);
            }
        } catch (StockException $e) {
            return $this->backWithErrors($request, '/purchase-orders/' . $id, [], $e->getMessage());
        }
        ActivityLogger::log(['order' => 'ordered', 'receive' => 'received', 'cancel' => 'cancelled'][$request->param('action')] . ' purchase order', 'purchase_order', $id, ['number' => $po['po_number']]);

        return $this->redirect('/purchase-orders/' . $id, $message);
    }

    private function form(?array $po, array $items, int $supplierId, int $productId): Response
    {
        $products = (new ProductRepository())->sellable();
        if ($items === [] && $productId > 0) {
            foreach ($products as $p) {
                if ((int) $p['id'] === $productId) {
                    $items[] = ['product_id' => $productId, 'qty_ordered' => max(1, (int) $p['reorder_level'] * 2 - (int) $p['qty_on_hand']), 'unit_cost' => $p['cost_price']];
                }
            }
        }

        return $this->view('purchase-orders/form', [
            'pageTitle' => $po ? 'Edit ' . $po['po_number'] : 'New purchase order',
            'po' => $po,
            'items' => $items,
            'supplierId' => $supplierId,
            'suppliers' => (new SupplierRepository())->options(),
            'products' => $products,
        ]);
    }

    /** @return list<array{product_id: int, qty: int, unit_cost: int}> */
    private function lines(Request $request): array
    {
        $lines = [];
        foreach ((array) $request->input('lines', []) as $line) {
            if (!is_array($line) || empty($line['product_id'])) {
                continue;
            }
            $cost = to_cents($line['unit_cost'] ?? '');
            $lines[] = [
                'product_id' => (int) $line['product_id'],
                'qty' => filter_var($line['qty'] ?? '', FILTER_VALIDATE_INT) === false ? 0 : (int) $line['qty'],
                'unit_cost' => $cost ?? -1,
            ];
        }

        return $lines;
    }

    private function date(Request $request): ?string
    {
        $date = $request->string('expected_on');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
    }
}
