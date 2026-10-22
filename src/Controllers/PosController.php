<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Services\ActivityLogger;
use App\Services\CheckoutService;
use App\Services\StockException;

final class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $products = array_map(static fn (array $p) => [
            'id' => (int) $p['id'],
            'sku' => $p['sku'],
            'name' => $p['name'],
            'brand' => $p['brand'],
            'category' => $p['category_name'] ?? 'Uncategorized',
            'price' => db_cents($p['selling_price']),
            'stock' => (int) $p['qty_on_hand'],
            'reorder' => (int) $p['reorder_level'],
            'image' => $p['image_url'],
        ], (new ProductRepository())->sellable());

        return $this->view('pos/index', [
            'pageTitle' => 'Point of sale',
            'products' => $products,
            'categories' => array_values((new CategoryRepository())->options()),
            'canDiscount' => Auth::can('pos.discount'),
        ]);
    }

    /** JSON: {lines: [{product_id, qty}], discount, payment_method, amount_paid, customer_name} */
    public function checkout(Request $request): Response
    {
        $discount = Auth::can('pos.discount') ? (to_cents($request->input('discount', '0')) ?? -1) : 0;
        $paid = $request->input('amount_paid');
        try {
            $sale = (new CheckoutService())->checkout(
                is_array($request->input('lines')) ? $request->input('lines') : [],
                $discount,
                $request->string('payment_method', 'cash'),
                $paid === null || $paid === '' ? null : to_cents($paid),
                $request->string('customer_name'),
            );
        } catch (StockException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
        ActivityLogger::log('completed sale', 'sale', $sale['id'], ['number' => $sale['number'], 'total' => money($sale['total'])], $request->ip);

        return Response::json([
            'id' => $sale['id'],
            'number' => $sale['number'],
            'total' => $sale['total'],
            'change' => $sale['change'],
            'receipt_url' => url('/sales/' . $sale['id'] . '/receipt'),
            'sale_url' => url('/sales/' . $sale['id']),
        ], 201);
    }
}
