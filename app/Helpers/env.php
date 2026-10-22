<?php
/**
 * Minimal .env loader. Variables already set in the real environment
 * (e.g. on the hosting server) take priority over the file.
 */
function load_env(string $path): void {
 if (!is_readable($path)) {
  return;
 }
 foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
  $line = trim($line);
  if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
   continue;
  }
  [$key, $value] = array_map('trim', explode('=', $line, 2));
  $value = trim($value, "\"'");
  if (getenv($key) === false) {
   putenv($key . '=' . $value);
   $_ENV[$key] = $value;
  }
 }
}

function env(string $key, $default = null) {
 $value = getenv($key);
 return $value === false || $value === '' ? $default : $value;
}
