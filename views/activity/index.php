<?php
use App\Core\View;
use App\Repositories\ActivityRepository;
?>
<div class="page-head">
  <div>
    <div class="tag">J3 · AUDIT</div>
    <h1>Activity log</h1>
    <p class="lede">Every sign-in and every change, with who did it and when.</p>
  </div>
</div>

<form class="filters" method="get" action="<?= e(url('/activity')) ?>">
  <label class="search" style="flex:1 1 220px"><?= icon('search', 16) ?><span class="sr-only">Search</span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Action or detail, e.g. voided, CPU-AMD">
  </label>
  <label class="sr-only" for="a-user">User</label>
  <select id="a-user" class="select" name="user" style="width:auto">
    <option value="">Everyone</option>
    <?php foreach ($users as $id => $name): ?><option value="<?= e($id) ?>"<?= (int) $filters['user'] === (int) $id ? ' selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="a-type">Record type</label>
  <select id="a-type" class="select" name="type" style="width:auto">
    <option value="">All records</option>
    <?php foreach ($types as $type): ?><option value="<?= e($type) ?>"<?= $filters['type'] === $type ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $type))) ?></option><?php endforeach; ?>
  </select>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>From</span><input class="input" type="date" name="from" value="<?= e($filters['from']) ?>" style="width:auto"></label>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>To</span><input class="input" type="date" name="to" value="<?= e($filters['to']) ?>" style="width:auto"></label>
  <button type="submit" class="btn">Filter</button>
  <?php if (array_filter($filters)): ?><a class="btn" href="<?= e(url('/activity')) ?>">Clear</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>When</th><th>Who</th><th>What</th><th>Details</th><th>IP</th></tr></thead>
    <tbody>
      <?php foreach ($result['rows'] as $a): ?>
        <?php $link = ActivityRepository::link($a); ?>
        <tr>
          <td class="mono small nowrap"><?= e(format_date($a['created_at'])) ?></td>
          <td><?= e($a['user_name'] ?? 'System') ?><span class="sub mono"><?= e($a['username'] ?? '') ?></span></td>
          <td><?= $link ? '<a href="' . e(url($link)) . '">' . e(ucfirst($a['action'])) . '</a>' : e(ucfirst($a['action'])) ?></td>
          <td class="small"><?= e(ActivityRepository::details($a['details'])) ?></td>
          <td class="mono small"><?= e($a['ip'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$result['rows']): ?>
        <tr><td colspan="5"><div class="empty"><?= icon('log', 28) ?><b>No activity<?= array_filter($filters) ? ' matches these filters' : ' yet' ?></b></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= View::partial('partials/pagination', ['result' => $result, 'noun' => 'entries']) ?>
