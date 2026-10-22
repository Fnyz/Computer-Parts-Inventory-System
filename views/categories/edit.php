<?php
use App\Core\View;
use App\Repositories\CategoryRepository;
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/categories')) ?>">Categories</a> / <?= e($category['name']) ?></div>
    <h1>Edit <?= e($category['name']) ?></h1>
  </div>
</div>

<form class="panel panel-pad form" method="post" action="<?= e(url('/categories/' . $category['id'])) ?>" style="max-width:640px">
  <?= csrf_field() ?>
  <?= View::partial('partials/field', ['name' => 'name', 'label' => 'Name', 'value' => $category['name'], 'attrs' => 'required maxlength="80"']) ?>
  <?= View::partial('partials/field', ['name' => 'description', 'label' => 'Description (optional)', 'value' => $category['description'] ?? '', 'attrs' => 'maxlength="255"']) ?>
  <?= View::partial('partials/field', [
      'name' => 'spec_fields', 'label' => 'Spec fields', 'type' => 'textarea', 'attrs' => 'rows="6"',
      'value' => implode("\n", CategoryRepository::fields($category['spec_fields'])),
      'hint' => 'One per line. Removing a field keeps the values already saved on products.',
  ]) ?>
  <div class="form-actions">
    <a class="btn" href="<?= e(url('/categories')) ?>">Cancel</a>
    <button type="submit" class="btn btn-primary">Save category</button>
  </div>
</form>
