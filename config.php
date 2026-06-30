<?php
define('BASEPATH', realpath(__DIR__));

// Auto-detect the URL prefix for this project (e.g. '' if served at web root, '/myproject' if in a subfolder)
$_docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
if ($_docRoot && strpos(BASEPATH, $_docRoot) === 0) {
 define('BASE_URL', str_replace('\\', '/', substr(BASEPATH, strlen($_docRoot))));
} else {
 // Fallback: set manually if auto-detect fails
 define('BASE_URL', '');
}
unset($_docRoot);
