<?php
use App\Core\View;
use App\Repositories\CategoryRepository;
?>
<div class="page-head">
  <div>
    <div class="tag">SLOT_03 · INVENTORY</div>
    <h1>Categories</h1>
    <p class="lede">Each category lists the spec fields shown on its product form, such as Socket and TDP for CPUs.</p>
  </div>
</div>

<div class="cols">
  <div class="wide">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Category</th><th>Spec fields</th><th class="r">Products</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($categories as $c): ?>
            <tr>
              <td><div class="part"><span class="thumb"><?= icon(category_icon($c['name']), 20) ?></span><div><b><?= e($c['name']) ?></b><span class="sub"><?= e($c['description'] ?? '') ?></span></div></div></td>
              <td class="small"><?= e(implode(' · ', CategoryRepository::fields($c['spec_fields'])) ?: '—') ?></td>
              <td class="r mono num"><a href="<?= e(url('/products?category=' . $c['id'])) ?>"><?= e($c['product_count']) ?></a></td>
              <td class="r nowrap">
                <a class="btn btn-sm" href="<?= e(url('/categories/' . $c['id'] . '/edit')) ?>"><?= icon('edit', 14) ?>Edit</a>
                <?php if ((int) $c['product_count'] === 0): ?>
                  <form class="inline" method="post" action="<?= e(url('/categories/' . $c['id'] . '/delete')) ?>" data-confirm="Delete the <?= e($c['name']) ?> category?">
                    <?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$categories): ?>
            <tr><td colspan="4"><div class="empty"><?= icon('layers', 28) ?><b>No categories yet</b>Add the first one with the form.</div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <form class="panel panel-pad form" method="post" action="<?= e(url('/categories')) ?>">
    <?= csrf_field() ?>
    <div class="tag">NEW CATEGORY</div>
    <?= View::partial('partials/field', ['name' => 'name', 'label' => 'Name', 'value' => '', 'attrs' => 'required maxlength="80"']) ?>
    <?= View::partial('partials/field', ['name' => 'description', 'label' => 'Description (optional)', 'value' => '', 'attrs' => 'maxlength="255"']) ?>
    <?= View::partial('partials/field', ['name' => 'spec_fields', 'label' => 'Spec fields', 'type' => 'textarea', 'value' => '', 'attrs' => 'rows="4" placeholder="Socket&#10;Cores / threads&#10;TDP"', 'hint' => 'One per line, up to 12.']) ?>
    <button type="submit" class="btn btn-primary"><?= icon('plus', 16) ?>Add category</button>
  </form>
</div>
