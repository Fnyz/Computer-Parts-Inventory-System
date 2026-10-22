<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;

final class RedirectIfAuthenticated extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        return Auth::user() !== null ? Response::redirect('/') : $next($request);
    }
}
