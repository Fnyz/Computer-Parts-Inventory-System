<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\Auth;
use App\Repositories\ProductRepository;

final class App
{
    public function __construct(private readonly Router $router)
    {
    }

    /** Loads configuration. Called once by public/index.php, the CLI tools and the tests. */
    public static function bootstrap(): void
    {
        if (!defined('BASE_DIR')) {
            define('BASE_DIR', dirname(__DIR__, 2));
        }
        load_env(BASE_DIR . '/.env');
        date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Manila'));
    }

    public static function create(): self
    {
        $router = new Router();
        (require BASE_DIR . '/config/routes.php')($router);

        return new self($router);
    }

    public function handle(Request $request): Response
    {
        try {
            Session::start();
            View::share('request', $request);
            View::share('authUser', Auth::user());
            // Top bar alert: parts at or below their reorder level
            View::share('lowStockCount', Auth::can('products.view') ? (new ProductRepository())->lowStockCount() : 0);

            if ($request->method === 'POST' && !Csrf::valid($request)) {
                throw new HttpException(419, 'This form expired. Go back, refresh the page and try again.');
            }

            return $this->router->dispatch($request);
        } catch (HttpException $e) {
            return $this->errorResponse($request, $e->status, $e->getMessage());
        } catch (\Throwable $e) {
            Logger::get()->error($e->getMessage(), ['exception' => $e, 'path' => $request->path]);
            $message = env('APP_DEBUG') === 'true'
                ? $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                : 'Something went wrong on our side. Try again, and tell an Admin if it keeps happening.';

            return $this->errorResponse($request, 500, $message);
        }
    }

    private function errorResponse(Request $request, int $status, string $message): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['error' => $message], $status);
        }
        $titles = [403 => 'Access denied', 404 => 'Not found', 405 => 'Not allowed', 419 => 'Form expired', 500 => 'System error'];
        $layout = Auth::user() !== null ? 'layouts/app' : 'layouts/auth';

        try {
            $html = View::render('errors/error', [
                'status' => $status,
                'title' => $titles[$status] ?? 'Error',
                'message' => $message,
                'pageTitle' => $titles[$status] ?? 'Error',
            ], $layout);
        } catch (\Throwable) {
            $html = '<h1>' . e($status) . '</h1><p>' . e($message) . '</p>';
        }

        return Response::html($html, $status);
    }
}
