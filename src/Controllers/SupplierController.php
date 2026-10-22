<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\SupplierRepository;
use App\Services\ActivityLogger;

final class SupplierController extends Controller
{
    private const RULES = [
        'name' => 'required|max:150',
        'contact_person' => 'max:150',
        'phone' => 'max:30',
        'email' => 'email|max:150',
        'address' => 'max:255',
        'notes' => 'max:2000',
    ];

    public function index(Request $request): Response
    {
        $archived = $request->string('show') === 'archived';

        return $this->view('suppliers/index', [
            'pageTitle' => 'Suppliers',
            'archived' => $archived,
            'search' => $request->string('q'),
            'result' => (new SupplierRepository())->paginate($request->string('q'), $archived, $request->int('page', 1)),
        ]);
    }

    public function show(Request $request): Response
    {
        $supplier = $this->findOr404((new SupplierRepository())->find((int) $request->param('id')));
        $orders = Auth::can('po.view') ? (new PurchaseOrderRepository())->forSupplier((int) $supplier['id']) : [];

        return $this->view('suppliers/show', [
            'pageTitle' => $supplier['name'],
            'supplier' => $supplier,
            'products' => (new ProductRepository())->all(['supplier' => (int) $supplier['id']]),
            'orders' => $orders,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('suppliers/form', ['pageTitle' => 'New supplier', 'supplier' => null]);
    }

    public function store(Request $request): Response
    {
        $v = $this->validate($request, self::RULES);
        if ($v->fails()) {
            return $this->backWithErrors($request, '/suppliers/new', $v->errors());
        }
        $id = (new SupplierRepository())->create($request->body);
        ActivityLogger::log('created supplier', 'supplier', $id, ['name' => $request->string('name')]);

        return $this->redirect('/suppliers/' . $id, 'Supplier ' . $request->string('name') . ' added.');
    }

    public function edit(Request $request): Response
    {
        $supplier = $this->findOr404((new SupplierRepository())->find((int) $request->param('id')));

        return $this->view('suppliers/form', ['pageTitle' => 'Edit ' . $supplier['name'], 'supplier' => $supplier]);
    }

    public function update(Request $request): Response
    {
        $suppliers = new SupplierRepository();
        $supplier = $this->findOr404($suppliers->find((int) $request->param('id')));
        $id = (int) $supplier['id'];
        $v = $this->validate($request, self::RULES);
        if ($v->fails()) {
            return $this->backWithErrors($request, "/suppliers/{$id}/edit", $v->errors());
        }
        $suppliers->update($id, $request->body);
        ActivityLogger::log('updated supplier', 'supplier', $id, ['name' => $request->string('name')]);

        return $this->redirect('/suppliers/' . $id, 'Supplier saved.');
    }

    /** Archive or restore. Suppliers are never deleted, so old purchase orders keep their name. */
    public function archive(Request $request): Response
    {
        $suppliers = new SupplierRepository();
        $supplier = $this->findOr404($suppliers->find((int) $request->param('id')));
        $archive = $supplier['archived_at'] === null;
        $suppliers->setArchived((int) $supplier['id'], $archive);
        ActivityLogger::log($archive ? 'archived supplier' : 'restored supplier', 'supplier', (int) $supplier['id'], ['name' => $supplier['name']]);

        return $this->redirect('/suppliers/' . $supplier['id'], $supplier['name'] . ($archive ? ' archived.' : ' restored.'));
    }
}
