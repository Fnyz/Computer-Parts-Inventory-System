<?php
define('BASEPATH', realpath(__DIR__));

require_once BASEPATH . '/app/Helpers/env.php';
load_env(BASEPATH . '/.env');

// Log unexpected errors instead of showing SQL or stack traces to users
set_exception_handler(function (Throwable $e) {
 error_log((string)$e);
 http_response_code(500);
 echo env('APP_DEBUG') === 'true'
  ? '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>'
  : 'Something went wrong. Please try again.';
});

// Auto-detect the URL prefix for this project (e.g. '' if served at web root, '/myproject' if in a subfolder)
$_docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
if ($_docRoot && strpos(BASEPATH, $_docRoot) === 0) {
 define('BASE_URL', str_replace('\\', '/', substr(BASEPATH, strlen($_docRoot))));
} else {
 // Fallback: set manually if auto-detect fails
 define('BASE_URL', '');
}
unset($_docRoot);
