<?php
use App\Core\View;

$isNew = $supplier === null;
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/suppliers')) ?>">Suppliers</a> / <?= $isNew ? 'New' : e($supplier['name']) ?></div>
    <h1><?= $isNew ? 'New supplier' : 'Edit ' . e($supplier['name']) ?></h1>
  </div>
</div>

<form class="panel panel-pad form" method="post" action="<?= e(url($isNew ? '/suppliers' : '/suppliers/' . $supplier['id'])) ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <div class="form-grid">
    <?= View::partial('partials/field', ['name' => 'name', 'label' => 'Supplier name', 'value' => $supplier['name'] ?? '', 'attrs' => 'required maxlength="150"', 'class' => 'span-2']) ?>
    <?= View::partial('partials/field', ['name' => 'contact_person', 'label' => 'Contact person', 'value' => $supplier['contact_person'] ?? '']) ?>
    <?= View::partial('partials/field', ['name' => 'phone', 'label' => 'Phone', 'value' => $supplier['phone'] ?? '', 'attrs' => 'inputmode="tel"']) ?>
    <?= View::partial('partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $supplier['email'] ?? '']) ?>
    <?= View::partial('partials/field', ['name' => 'address', 'label' => 'Address', 'value' => $supplier['address'] ?? '']) ?>
    <?= View::partial('partials/field', ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'value' => $supplier['notes'] ?? '', 'class' => 'span-2', 'hint' => 'Payment terms, delivery days, account numbers…']) ?>
  </div>
  <div class="form-actions">
    <a class="btn" href="<?= e(url($isNew ? '/suppliers' : '/suppliers/' . $supplier['id'])) ?>">Cancel</a>
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Add supplier' : 'Save supplier' ?></button>
  </div>
</form>
