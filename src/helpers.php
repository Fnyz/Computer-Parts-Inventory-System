<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Session;

/**
 * Minimal .env loader. Variables already set in the real environment
 * (e.g. on Render) take priority over the file.
 */
function load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
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

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** App URL for a path, honouring an optional sub-folder install (APP_BASE_PATH=/inventory). */
function url(string $path = '/'): string
{
    return rtrim((string) env('APP_BASE_PATH', ''), '/') . '/' . ltrim($path, '/');
}

/** URL for a file in public/assets, with a version stamp so browsers pick up changes. */
function asset(string $path): string
{
    $file = BASE_DIR . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';

    return url('/assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

/** Previously submitted form value (after a validation error), else the default. */
function old(string $key, mixed $default = ''): string
{
    $old = Session::get('_old_now', []);

    return (string) (is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default);
}

/** Validation error for a field from the last submit, if any. */
function error_for(string $key): ?string
{
    $errors = Session::get('_errors_now', []);

    return is_array($errors) ? ($errors[$key] ?? null) : null;
}

/** Money is handled as integer centavos everywhere; this formats it for display. */
function money(int $cents, bool $symbol = true): string
{
    $sign = $cents < 0 ? '-' : '';

    return $sign . ($symbol ? '₱' : '') . number_format(abs($cents) / 100, 2);
}

/** Parses user input such as "1,234.50" or "₱ 99" into centavos. Returns null when it is not a number. */
function to_cents(mixed $value): ?int
{
    $clean = str_replace([',', '₱', ' '], '', trim((string) $value));
    if ($clean === '' || !preg_match('/^-?\d+(\.\d{1,2})?$/', $clean)) {
        return null;
    }
    [$whole, $fraction] = array_pad(explode('.', ltrim($clean, '-')), 2, '0');
    $cents = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');

    return str_starts_with($clean, '-') ? -$cents : $cents;
}

/** Current time as a DATETIME string in the app's timezone (DB servers may run in UTC). */
function now(): string
{
    return date('Y-m-d H:i:s');
}

function format_date(?string $value, string $format = 'M j, Y · H:i'): string
{
    return $value ? date($format, (int) strtotime($value)) : '—';
}

/** Builds a query string from the current one with some keys replaced (for filters and pagination). */
function query_with(array $changes): string
{
    $query = array_filter(array_merge($_GET, $changes), static fn ($v) => $v !== null && $v !== '');

    return $query ? '?' . http_build_query($query) : '';
}
