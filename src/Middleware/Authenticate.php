<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class Authenticate extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::user() === null) {
            if ($request->wantsJson()) {
                return Response::json(['error' => 'Your session has ended. Sign in again.'], 401);
            }
            if ($request->method === 'GET') {
                Session::set('_intended', $request->path);
            }

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
