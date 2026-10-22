<?php

declare(strict_types=1);

namespace App\Core;

/** Renders plain-PHP templates from views/. Templates must escape output with e(). */
final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $data = array_merge(self::$shared, $data);
        $content = self::capture($template, $data);
        if ($layout === null) {
            return $content;
        }

        return self::capture($layout, array_merge($data, ['content' => $content]));
    }

    /** Renders a partial inside a template: View::partial('partials/pagination', [...]) */
    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, array_merge(self::$shared, $data));
    }

    private static function capture(string $template, array $data): string
    {
        $file = BASE_DIR . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
