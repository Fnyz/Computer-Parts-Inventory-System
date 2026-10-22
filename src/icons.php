<?php

declare(strict_types=1);

/** Inline stroke icons used across the UI. They inherit the text colour. */
function icon(string $name, int $size = 18): string
{
    static $paths = [
        'chip' => '<rect x="6" y="6" width="12" height="12" rx="1.5"/><rect x="9.5" y="9.5" width="5" height="5" fill="currentColor" stroke="none"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
        'cpu' => '<rect x="6" y="6" width="12" height="12" rx="1"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>',
        'gpu' => '<rect x="2" y="7" width="20" height="10" rx="1"/><circle cx="9" cy="12" r="2.5"/><circle cx="16" cy="12" r="2.5"/><path d="M4 17v3"/>',
        'ram' => '<rect x="2" y="7" width="20" height="9" rx="1"/><path d="M6 10v3M10 10v3M14 10v3M18 10v3M4 16v3M20 16v3"/>',
        'storage' => '<rect x="2" y="8" width="20" height="8" rx="1"/><path d="M5 12h8M17 11v2"/>',
        'motherboard' => '<rect x="3" y="3" width="18" height="18" rx="1"/><rect x="7" y="7" width="6" height="6"/><path d="M16 7v10M7 17h6"/>',
        'psu' => '<rect x="2" y="5" width="20" height="14" rx="1"/><circle cx="9" cy="12" r="4"/><path d="M16 9h3M16 12h3M16 15h3"/>',
        'fan' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2"/><path d="M12 10c0-4 2-6 4-6M14 12c4 0 6 2 6 4M12 14c0 4-2 6-4 6M10 12c-4 0-6-2-6-4"/>',
        'case' => '<rect x="6" y="2" width="12" height="20" rx="1"/><path d="M9 6h6M9 9h6"/><circle cx="12" cy="17" r="1.5"/>',
        'peripheral' => '<rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M7 14h10"/>',
        'layers' => '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/>',
        'moves' => '<path d="M7 4v16M3 8l4-4 4 4M17 20V4M13 16l4 4 4-4"/>',
        'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h11.5l2-8H6.2"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="1"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h3"/>',
        'receipt' => '<path d="M5 2h14v20l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'truck' => '<path d="M2 6h11v10H2zM13 10h4l4 3v3h-8z"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'chart' => '<path d="M3 21h18M6 17v-6M11 17V6M16 17V9M20 17v-4"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6M16 4a4 4 0 0 1 0 8M22 21c0-3-2-5-4-5.5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'log' => '<path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-5-5"/>',
        'bell' => '<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>',
        'power' => '<path d="M12 2v10"/><path d="M5.6 6.6a9 9 0 1 0 12.8 0"/>',
        'barcode' => '<path d="M3 7V4h3M18 4h3v3M21 17v3h-3M6 20H3v-3M7 8v8M10 8v8M13 8v8M16 8v8"/>',
        'download' => '<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        'print' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="1"/><path d="M6 14h12v7H6z"/>',
        'check' => '<path d="M4 12l5 5L20 6"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'archive' => '<rect x="3" y="4" width="18" height="4"/><path d="M5 8v12h14V8M10 12h4"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10z"/>',
        'monitor' => '<rect x="2" y="4" width="20" height="13" rx="1"/><path d="M8 21h8M12 17v4"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
    ];
    $body = $paths[$name] ?? $paths['chip'];

    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/** Icon for a product category, matched loosely by name. */
function category_icon(?string $category): string
{
    $name = strtolower((string) $category);
    $map = ['cpu' => 'cpu', 'processor' => 'cpu', 'gpu' => 'gpu', 'graphic' => 'gpu', 'video' => 'gpu', 'ram' => 'ram', 'memory' => 'ram',
        'storage' => 'storage', 'ssd' => 'storage', 'hdd' => 'storage', 'mother' => 'motherboard', 'psu' => 'psu', 'power' => 'psu',
        'cool' => 'fan', 'fan' => 'fan', 'case' => 'case', 'chassis' => 'case', 'periph' => 'peripheral', 'keyboard' => 'peripheral', 'mouse' => 'peripheral'];
    foreach ($map as $needle => $icon) {
        if (str_contains($name, $needle)) {
            return $icon;
        }
    }

    return 'chip';
}
