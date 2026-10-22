<?php

declare(strict_types=1);

namespace App\Services;

use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;

/** Code 128 barcodes for SKU labels. Any USB barcode scanner can read them at the point of sale. */
final class BarcodeService
{
    public static function svg(string $code, float $height = 50): string
    {
        $barcode = (new TypeCode128())->getBarcode($code);
        $svg = (new SvgRenderer())->setSvgType(SvgRenderer::TYPE_SVG_INLINE)->render($barcode, max(120, $barcode->getWidth() * 2), $height);

        // Accessible name; the SKU itself is printed under the bars
        return (string) preg_replace('/<svg /', '<svg role="img" aria-label="Barcode for ' . e($code) . '" ', $svg, 1);
    }
}
