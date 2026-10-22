<?php
/**
 * Daily sales (line) and purchases received (bars), drawn to scale as SVG.
 * @var list<array{date: string, sales: int, purchases: int}> $days  (centavos)
 * @var bool $showPurchases
 */
$n = count($days);
$max = max(1, ...array_map(static fn ($d) => max($d['sales'], $showPurchases ? $d['purchases'] : 0), $days));
// Round the top of the scale up to 1, 2, 2.5 or 5 × a power of ten, in pesos
$pesos = $max / 100;
$mag = 10 ** floor(log10(max(1, $pesos)));
foreach ([1, 2, 2.5, 5, 10] as $m) {
    if ($m * $mag >= $pesos) {
        $top = $m * $mag * 100;
        break;
    }
}
$top = $top ?? $max;
$short = static function (float $cents): string {
    $p = $cents / 100;

    return '₱' . ($p >= 1e6 ? rtrim(rtrim(number_format($p / 1e6, 1), '0'), '.') . 'M' : ($p >= 1e3 ? rtrim(rtrim(number_format($p / 1e3, 1), '0'), '.') . 'k' : number_format($p)));
};
[$left, $right, $y0, $y1] = [60, 606, 180, 20];
$step = $n > 1 ? ($right - $left) / ($n - 1) : 0;
$x = static fn (int $i) => round($left + $i * $step, 1);
$y = static fn (int $v) => round($y0 - $v / $top * ($y0 - $y1), 1);
$points = implode(' ', array_map(static fn ($d, $i) => $x($i) . ',' . $y($d['sales']), $days, array_keys($days)));
$totalSales = array_sum(array_column($days, 'sales'));
$label = 'Daily sales for the last ' . $n . ' days, ' . money($totalSales) . ' in total' . ($showPurchases ? ', with purchases received shown as bars' : '');
?>
<svg role="img" aria-label="<?= e($label) ?>" viewBox="0 0 630 210" style="width:100%;height:auto;display:block">
  <g stroke="var(--line)" stroke-width="1">
    <?php foreach ([0, 1, 2, 3] as $g): $gy = round($y1 + $g * ($y0 - $y1) / 3, 1); ?>
      <line x1="56" y1="<?= $gy ?>" x2="620" y2="<?= $gy ?>"<?= $g === 3 ? ' stroke="var(--line-2)"' : '' ?>/>
    <?php endforeach; ?>
  </g>
  <g font-family="JetBrains Mono, monospace" font-size="10" fill="var(--muted)" text-anchor="end">
    <?php foreach ([0, 1, 2, 3] as $g): $gy = round($y1 + $g * ($y0 - $y1) / 3, 1); ?>
      <text x="48" y="<?= $gy + 3 ?>"><?= e($short($top * (3 - $g) / 3)) ?></text>
    <?php endforeach; ?>
  </g>
  <?php if ($showPurchases): ?>
    <g fill="var(--copper)">
      <?php foreach ($days as $i => $d): if ($d['purchases'] <= 0) { continue; } ?>
        <rect x="<?= $x($i) - 7 ?>" y="<?= $y($d['purchases']) ?>" width="14" height="<?= round($y0 - $y($d['purchases']), 1) ?>"><title><?= e(format_date($d['date'], 'M j') . ': ' . money($d['purchases']) . ' received') ?></title></rect>
      <?php endforeach; ?>
    </g>
  <?php endif; ?>
  <polyline fill="none" stroke="var(--ok-led)" stroke-width="2.5" stroke-linejoin="round" points="<?= e($points) ?>"/>
  <?php foreach ($days as $i => $d): ?>
    <circle cx="<?= $x($i) ?>" cy="<?= $y($d['sales']) ?>" r="<?= $i === $n - 1 ? 5 : 3 ?>" fill="<?= $i === $n - 1 ? 'var(--panel)' : 'var(--ok-led)' ?>" stroke="var(--ok-led)" stroke-width="<?= $i === $n - 1 ? 2.5 : 0 ?>"><title><?= e(format_date($d['date'], 'M j') . ': ' . money($d['sales']) . ' sales') ?></title></circle>
  <?php endforeach; ?>
  <g font-family="JetBrains Mono, monospace" font-size="10" fill="var(--muted)" text-anchor="middle">
    <?php foreach (array_unique([0, intdiv($n - 1, 2), $n - 1]) as $i): ?>
      <text x="<?= $x($i) ?>" y="200"><?= e(format_date($days[$i]['date'], 'M j')) ?></text>
    <?php endforeach; ?>
  </g>
</svg>
<table class="sr-only">
  <caption>Daily figures</caption>
  <tr><th>Date</th><th>Sales</th><?= $showPurchases ? '<th>Purchases received</th>' : '' ?></tr>
  <?php foreach ($days as $d): ?>
    <tr><td><?= e($d['date']) ?></td><td><?= e(money($d['sales'])) ?></td><?= $showPurchases ? '<td>' . e(money($d['purchases'])) . '</td>' : '' ?></tr>
  <?php endforeach; ?>
</table>
