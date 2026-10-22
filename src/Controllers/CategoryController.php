<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Services\ActivityLogger;

final class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('categories/index', [
            'pageTitle' => 'Categories',
            'categories' => (new CategoryRepository())->allWithCounts(),
        ]);
    }

    public function store(Request $request): Response
    {
        $categories = new CategoryRepository();
        $errors = $this->validate($request, ['name' => 'required|max:80', 'description' => 'max:255'])->errors();
        if (!isset($errors['name']) && $categories->nameTaken($request->string('name'))) {
            $errors['name'] = 'A category with that name already exists.';
        }
        if ($errors) {
            return $this->backWithErrors($request, '/categories', $errors);
        }
        $id = $categories->create($request->string('name'), $request->string('description'), CategoryRepository::parseFields($request->string('spec_fields')));
        ActivityLogger::log('created category', 'category', $id, ['name' => $request->string('name')]);

        return $this->redirect('/categories', 'Category ' . $request->string('name') . ' added.');
    }

    public function edit(Request $request): Response
    {
        $category = $this->findOr404((new CategoryRepository())->find((int) $request->param('id')));

        return $this->view('categories/edit', ['pageTitle' => 'Edit ' . $category['name'], 'category' => $category]);
    }

    public function update(Request $request): Response
    {
        $categories = new CategoryRepository();
        $category = $this->findOr404($categories->find((int) $request->param('id')));
        $id = (int) $category['id'];
        $errors = $this->validate($request, ['name' => 'required|max:80', 'description' => 'max:255'])->errors();
        if (!isset($errors['name']) && $categories->nameTaken($request->string('name'), $id)) {
            $errors['name'] = 'A category with that name already exists.';
        }
        if ($errors) {
            return $this->backWithErrors($request, "/categories/{$id}/edit", $errors);
        }
        $categories->update($id, $request->string('name'), $request->string('description'), CategoryRepository::parseFields($request->string('spec_fields')));
        ActivityLogger::log('updated category', 'category', $id, ['name' => $request->string('name')]);

        return $this->redirect('/categories', 'Category ' . $request->string('name') . ' saved.');
    }

    public function destroy(Request $request): Response
    {
        $categories = new CategoryRepository();
        $category = $this->findOr404($categories->find((int) $request->param('id')));
        $count = $categories->productCount((int) $category['id']);
        if ($count > 0) {
            return $this->backWithErrors($request, '/categories', [], "{$category['name']} still has {$count} product(s). Move them to another category first.");
        }
        $categories->delete((int) $category['id']);
        ActivityLogger::log('deleted category', 'category', (int) $category['id'], ['name' => $category['name']]);

        return $this->redirect('/categories', 'Category ' . $category['name'] . ' deleted.');
    }
}
