<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/** Used by the smoke tests and the uptime monitor. Reveals no data. */
final class HealthController extends Controller
{
    public function show(Request $request): Response
    {
        try {
            Database::connection()->query('SELECT 1')->fetchColumn();
            $db = 'ok';
        } catch (\Throwable) {
            $db = 'unreachable';
        }

        return Response::json(
            ['status' => $db === 'ok' ? 'ok' : 'degraded', 'database' => $db, 'time' => date(DATE_ATOM)],
            $db === 'ok' ? 200 : 503,
        );
    }
}
