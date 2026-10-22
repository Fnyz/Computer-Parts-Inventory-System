<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csv;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use App\Repositories\SupplierRepository;
use App\Services\ActivityLogger;
use App\Services\Images\ImageUploads;
use App\Services\StockException;
use App\Services\StockService;

final class ProductController extends Controller
{
    private const RULES = [
        'sku' => 'required|max:64|regex:/^[A-Za-z0-9][A-Za-z0-9._\-\/]*$/',
        'name' => 'required|max:150',
        'brand' => 'max:80',
        'model' => 'max:80',
        'description' => 'max:5000',
        'cost_price' => 'required|money|min:0',
        'selling_price' => 'required|money|min:0',
        'reorder_level' => 'required|int|min:0',
    ];
    private const LABELS = ['sku' => 'SKU', 'cost_price' => 'Cost price', 'selling_price' => 'Selling price', 'reorder_level' => 'Reorder level'];

    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q'),
            'category' => $request->int('category'),
            'supplier' => $request->int('supplier'),
            'status' => $request->string('status'),
            'archived' => $request->string('show') === 'archived',
            'sort' => $request->string('sort', 'name'),
        ];
        if ($request->string('export') === 'csv') {
            return $this->csv((new ProductRepository())->all($filters));
        }

        return $this->view('products/index', [
            'pageTitle' => 'Products',
            'filters' => $filters,
            'result' => (new ProductRepository())->paginate($filters, $request->int('page', 1)),
            'categories' => (new CategoryRepository())->options(),
        ]);
    }

    public function show(Request $request): Response
    {
        $product = $this->findOr404((new ProductRepository())->find((int) $request->param('id')));

        return $this->view('products/show', [
            'pageTitle' => $product['name'],
            'product' => $product,
            'specs' => ProductRepository::specs($product['specs']),
            'moves' => (new StockMovementRepository())->forProduct((int) $product['id'], 8),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function store(Request $request): Response
    {
        $products = new ProductRepository();
        $errors = $this->check($request, $products, 0);
        $opening = $request->string('opening_qty');
        if ($opening !== '' && (filter_var($opening, FILTER_VALIDATE_INT) === false || (int) $opening < 0)) {
            $errors['opening_qty'] = 'Opening stock must be a whole number, 0 or more.';
        }
        [$upload, $mime, $imageError] = $this->upload($request);
        if ($imageError) {
            $errors['image'] = $imageError;
        }
        if ($errors) {
            return $this->backWithErrors($request, '/products/new', $errors);
        }

        $id = Database::transaction(function () use ($products, $request, $opening): int {
            $id = $products->create($this->data($request));
            if ((int) $opening > 0) {
                (new StockService())->move($id, (int) $opening, 'opening', null, null, 'Opening stock', to_cents($request->string('cost_price')));
            }

            return $id;
        });
        if ($upload !== null) {
            $products->setImage($id, ImageUploads::storage()->store($upload, (string) $mime, 'products'));
        }
        ActivityLogger::log('created product', 'product', $id, ['sku' => strtoupper($request->string('sku'))]);

        return $this->redirect('/products/' . $id, 'Product ' . $request->string('name') . ' added.');
    }

    public function edit(Request $request): Response
    {
        return $this->form($this->findOr404((new ProductRepository())->find((int) $request->param('id'))));
    }

    public function update(Request $request): Response
    {
        $products = new ProductRepository();
        $product = $this->findOr404($products->find((int) $request->param('id')));
        $id = (int) $product['id'];
        $errors = $this->check($request, $products, $id);
        [$upload, $mime, $imageError] = $this->upload($request);
        if ($imageError) {
            $errors['image'] = $imageError;
        }
        if ($errors) {
            return $this->backWithErrors($request, "/products/{$id}/edit", $errors);
        }

        $products->update($id, $this->data($request));
        $storage = ImageUploads::storage();
        if ($upload !== null) {
            $products->setImage($id, $storage->store($upload, (string) $mime, 'products'));
        } elseif ($request->input('remove_image')) {
            $products->setImage($id, null);
        }
        if (($upload !== null || $request->input('remove_image')) && $product['image_url']) {
            $storage->delete($product['image_url']);
        }
        ActivityLogger::log('updated product', 'product', $id, [
            'sku' => strtoupper($request->string('sku')),
            'price' => db_cents($product['selling_price']) !== to_cents($request->string('selling_price'))
                ? [money(db_cents($product['selling_price'])), money((int) to_cents($request->string('selling_price')))] : null,
        ]);

        return $this->redirect('/products/' . $id, 'Product saved.');
    }

    /** Archived products disappear from the POS and purchase orders but keep their history. */
    public function archive(Request $request): Response
    {
        $products = new ProductRepository();
        $product = $this->findOr404($products->find((int) $request->param('id')));
        $archive = $product['archived_at'] === null;
        $products->setArchived((int) $product['id'], $archive);
        ActivityLogger::log($archive ? 'archived product' : 'restored product', 'product', (int) $product['id'], ['sku' => $product['sku']]);

        return $this->redirect('/products/' . $product['id'], $product['name'] . ($archive ? ' archived.' : ' restored.'));
    }

    public function adjustForm(Request $request): Response
    {
        $product = $this->findOr404((new ProductRepository())->find((int) $request->param('id')));

        return $this->view('products/adjust', ['pageTitle' => 'Adjust stock · ' . $product['name'], 'product' => $product]);
    }

    public function adjust(Request $request): Response
    {
        $product = $this->findOr404((new ProductRepository())->find((int) $request->param('id')));
        $id = (int) $product['id'];
        $v = $this->validate($request, [
            'mode' => 'required|in:add,remove,set',
            'qty' => 'required|int|min:0',
            'reason' => 'required|in:' . implode(',', StockService::REASONS),
            'note' => 'max:200',
        ], ['qty' => 'Quantity']);
        if ($v->fails()) {
            return $this->backWithErrors($request, "/products/{$id}/adjust", $v->errors());
        }
        try {
            $balance = (new StockService())->adjust($id, $request->string('mode'), $request->int('qty'), $request->string('reason'), $request->string('note'));
        } catch (StockException $e) {
            return $this->backWithErrors($request, "/products/{$id}/adjust", ['qty' => $e->getMessage()]);
        }
        ActivityLogger::log('adjusted stock', 'product', $id, ['sku' => $product['sku'], 'from' => (int) $product['qty_on_hand'], 'to' => $balance, 'reason' => $request->string('reason')]);

        return $this->redirect('/products/' . $id, "Stock for {$product['sku']} is now {$balance}.");
    }

    private function form(?array $product): Response
    {
        return $this->view('products/form', [
            'pageTitle' => $product ? 'Edit ' . $product['name'] : 'New product',
            'product' => $product,
            'specs' => $product ? ProductRepository::specs($product['specs']) : [],
            'categories' => (new CategoryRepository())->options(),
            'specFields' => (new CategoryRepository())->specFieldMap(),
            'suppliers' => (new SupplierRepository())->options(),
        ]);
    }

    private function check(Request $request, ProductRepository $products, int $exceptId): array
    {
        $errors = $this->validate($request, self::RULES, self::LABELS)->errors();
        if (!isset($errors['sku']) && $products->skuTaken(strtoupper($request->string('sku')), $exceptId)) {
            $errors['sku'] = 'Another product already uses this SKU.';
        }
        if ($request->int('category_id') && (new CategoryRepository())->find($request->int('category_id')) === null) {
            $errors['category_id'] = 'Choose a category from the list.';
        }
        if ($request->int('supplier_id') && (new SupplierRepository())->find($request->int('supplier_id')) === null) {
            $errors['supplier_id'] = 'Choose a supplier from the list.';
        }

        return $errors;
    }

    private function data(Request $request): array
    {
        $specs = [];
        foreach ((array) $request->input('specs', []) as $key => $value) {
            $key = mb_substr(trim((string) $key), 0, 40);
            $value = mb_substr(trim((string) $value), 0, 120);
            if ($key !== '' && $value !== '') {
                $specs[$key] = $value;
            }
        }

        return [
            'sku' => $request->string('sku'),
            'name' => $request->string('name'),
            'category_id' => $request->int('category_id'),
            'supplier_id' => $request->int('supplier_id'),
            'brand' => $request->string('brand'),
            'model' => $request->string('model'),
            'description' => $request->string('description'),
            'specs' => $specs,
            'cost_cents' => (int) to_cents($request->string('cost_price')),
            'price_cents' => (int) to_cents($request->string('selling_price')),
            'reorder_level' => $request->int('reorder_level'),
        ];
    }

    /** @return array{0: ?array, 1: ?string, 2: ?string} upload, mime, error */
    private function upload(Request $request): array
    {
        $file = $request->file('image');
        if ($file === null) {
            return [null, null, null];
        }
        $mime = null;
        $problem = ImageUploads::problem($file, $mime);

        return $problem ? [null, null, $problem] : [$file, $mime, null];
    }

    private function csv(array $rows): Response
    {
        return Csv::download(
            'products-' . date('Y-m-d') . '.csv',
            ['SKU', 'Name', 'Category', 'Brand', 'Model', 'Supplier', 'Cost', 'Price', 'On hand', 'Reorder level', 'Status'],
            array_map(static fn (array $p) => [
                $p['sku'], $p['name'], $p['category_name'], $p['brand'], $p['model'], $p['supplier_name'],
                $p['cost_price'], $p['selling_price'], $p['qty_on_hand'], $p['reorder_level'],
                stock_status((int) $p['qty_on_hand'], (int) $p['reorder_level'])['label'],
            ], $rows),
        );
    }
}
