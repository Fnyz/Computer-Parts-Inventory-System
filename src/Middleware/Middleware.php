<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

abstract class Middleware
{
    /** @param callable(Request): Response $next */
    abstract public function handle(Request $request, callable $next): Response;

    /** 'auth', 'guest', 'can:products.manage' */
    public static function make(string $name): self
    {
        [$key, $argument] = array_pad(explode(':', $name, 2), 2, null);

        return match ($key) {
            'auth' => new Authenticate(),
            'guest' => new RedirectIfAuthenticated(),
            'can' => new Authorize((string) $argument),
            default => throw new \InvalidArgumentException("Unknown middleware: {$name}"),
        };
    }
}
