<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

final class Authorize extends Middleware
{
    public function __construct(private readonly string $ability)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::can($this->ability)) {
            throw HttpException::forbidden();
        }

        return $next($request);
    }
}
