<?php /** @var string $content */ ?>
<!doctype html>
<html lang="en" data-theme-pref="dark" data-base="<?= e(rtrim(url('/'), '/')) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e(($pageTitle ?? 'Sign in') . ' · PartsDesk') ?></title>
  <script>
    // The boot screen is dark by design unless the user picked Light before
    (function () {
      var t = 'dark';
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
<main class="auth-page">
  <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
