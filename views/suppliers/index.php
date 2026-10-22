<?php use App\Auth\Auth; ?>
<div class="page-head">
  <div>
    <div class="tag">J3 · SUPPLIERS</div>
    <h1>Suppliers</h1>
  </div>
  <?php if (Auth::can('suppliers.manage')): ?>
    <div class="actions"><a class="btn btn-primary" href="<?= e(url('/suppliers/new')) ?>"><?= icon('plus', 16) ?>New supplier</a></div>
  <?php endif; ?>
</div>

<form class="filters" method="get" action="<?= e(url('/suppliers')) ?>">
  <label class="search" style="flex:1 1 280px"><?= icon('search', 16) ?><span class="sr-only">Search suppliers</span>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, contact, phone or email">
  </label>
  <div class="chips" role="group" aria-label="Show">
    <a class="chip<?= !$archived ? ' on' : '' ?>" href="<?= e(url('/suppliers')) ?>">Active</a>
    <a class="chip<?= $archived ? ' on' : '' ?>" href="<?= e(url('/suppliers?show=archived')) ?>">Archived</a>
  </div>
  <?php if ($archived): ?><input type="hidden" name="show" value="archived"><?php endif; ?>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Email</th><th class="r">Products</th></tr></thead>
    <tbody>
      <?php foreach ($result['rows'] as $s): ?>
        <tr>
          <td><a class="row-link" href="<?= e(url('/suppliers/' . $s['id'])) ?>"><?= e($s['name']) ?></a><span class="sub"><?= e($s['address'] ?? '') ?></span></td>
          <td><?= e($s['contact_person'] ?? '—') ?></td>
          <td class="mono small"><?= e($s['phone'] ?? '—') ?></td>
          <td class="small"><?= e($s['email'] ?? '—') ?></td>
          <td class="r mono num"><?= e($s['product_count']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$result['rows']): ?>
        <tr><td colspan="5"><div class="empty"><?= icon('truck', 28) ?><b><?= $search !== '' ? 'No suppliers match your search' : ($archived ? 'No archived suppliers' : 'No suppliers yet') ?></b><?= !$archived && $search === '' && Auth::can('suppliers.manage') ? 'Add the shops and distributors you buy parts from.' : '' ?></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= App\Core\View::partial('partials/pagination', ['result' => $result, 'noun' => 'suppliers']) ?>
