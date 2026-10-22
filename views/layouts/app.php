<?php
/** @var string $content */
use App\Auth\Auth;
use App\Core\Csrf;

$user = Auth::user();
$themePref = $user['theme'] ?? 'system';
?>
<!doctype html>
<html lang="en" data-theme-pref="<?= e($themePref) ?>" data-signed-in data-base="<?= e(rtrim(url('/'), '/')) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
  <title><?= e(($pageTitle ?? 'PartsDesk') . ' · PartsDesk') ?></title>
  <script>
    // Apply the saved theme before the page paints, so it never flashes the wrong colours
    (function () {
      var t = document.documentElement.getAttribute('data-theme-pref');
      try { t = localStorage.getItem('partsdesk-theme') || t; } catch (e) {}
      if (t === 'light' || t === 'dark') { document.documentElement.setAttribute('data-theme', t); }
    })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body>
<div class="app">
  <?= App\Core\View::partial('partials/sidebar') ?>
  <div class="main">
    <header class="topbar">
      <button type="button" class="btn btn-sm menu-toggle" data-menu-toggle aria-expanded="false" aria-label="Open the menu"><?= icon('menu') ?></button>
      <?php if (Auth::can('products.view')): ?>
        <form class="search" action="<?= e(url('/products')) ?>" method="get" role="search">
          <?= icon('search', 16) ?>
          <label class="sr-only" for="global-search">Search parts</label>
          <input id="global-search" type="search" name="q" placeholder="Search SKU, product or brand" value="<?= e($request->path === '/products' ? ($request->query['q'] ?? '') : '') ?>">
        </form>
      <?php else: ?>
        <div style="flex:1"></div>
      <?php endif; ?>
      <?php if (!empty($lowStockCount)): ?>
        <a class="alert-chip" href="<?= e(url('/products?status=low')) ?>" aria-label="<?= e($lowStockCount) ?> parts are low or out of stock"><?= icon('bell', 16) ?><?= e($lowStockCount) ?> LOW</a>
      <?php endif; ?>
      <?= App\Core\View::partial('partials/theme-switch') ?>
    </header>
    <main class="page" id="main">
      <?= App\Core\View::partial('partials/flash') ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?= $scripts ?? '' ?>
</body>
</html>
