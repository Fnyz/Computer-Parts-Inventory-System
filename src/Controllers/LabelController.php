<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProductRepository;

/** Printable SKU labels with a Code 128 barcode. */
final class LabelController extends Controller
{
    public function show(Request $request): Response
    {
        $product = $this->findOr404((new ProductRepository())->find((int) $request->param('id')));
        $copies = min(60, max(1, $request->int('copies', 1)));

        return $this->view('products/label', [
            'product' => $product,
            'copies' => $copies,
            'showPrice' => $request->string('price', '1') !== '0',
        ], null);
    }
}
