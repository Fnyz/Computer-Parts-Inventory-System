<?php
function csrf_token(): string {
 if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 }
 return $_SESSION['csrf_token'];
}

function csrf_validate(): void {
 $token = $_POST['csrf_token'] ?? '';
 $session = $_SESSION['csrf_token'] ?? '';
 if (!$session || !hash_equals($session, $token)) {
  http_response_code(403);
  exit('Invalid CSRF token.');
 }
}
