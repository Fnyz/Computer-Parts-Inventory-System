<?php
/** @var array{total: int, page: int, pages: int, perPage: int, rows: array} $result  @var string $noun */
$from = $result['total'] ? ($result['page'] - 1) * $result['perPage'] + 1 : 0;
$to = min($result['total'], $result['page'] * $result['perPage']);
?>
<div class="table-foot">
  <span class="tag">Showing <?= e(number_format($from)) ?>–<?= e(number_format($to)) ?> of <?= e(number_format($result['total'])) ?> <?= e($noun ?? 'rows') ?></span>
  <?php if ($result['pages'] > 1): ?>
    <nav class="pagination" aria-label="Pages">
      <?php if ($result['page'] > 1): ?><a class="chip" href="<?= e(query_with(['page' => $result['page'] - 1])) ?>" aria-label="Previous page">‹</a><?php endif; ?>
      <?php for ($p = max(1, $result['page'] - 2); $p <= min($result['pages'], $result['page'] + 2); $p++): ?>
        <a class="chip<?= $p === $result['page'] ? ' on' : '' ?>" href="<?= e(query_with(['page' => $p])) ?>"<?= $p === $result['page'] ? ' aria-current="page"' : '' ?>><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($result['page'] < $result['pages']): ?><a class="chip" href="<?= e(query_with(['page' => $result['page'] + 1])) ?>" aria-label="Next page">›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
