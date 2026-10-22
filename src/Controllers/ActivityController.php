<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ActivityRepository;
use App\Repositories\UserRepository;

/** Who changed what. Admin only. */
final class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $date = static fn (string $v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        $activity = new ActivityRepository();
        $filters = [
            'user' => $request->int('user'),
            'type' => in_array($request->string('type'), $activity->subjectTypes(), true) ? $request->string('type') : '',
            'q' => $request->string('q'),
            'from' => $date($request->string('from')),
            'to' => $date($request->string('to')),
        ];

        return $this->view('activity/index', [
            'pageTitle' => 'Activity log',
            'filters' => $filters,
            'result' => $activity->paginate($filters, $request->int('page', 1)),
            'users' => array_column((new UserRepository())->all(), 'name', 'id'),
            'types' => $activity->subjectTypes(),
        ]);
    }
}
