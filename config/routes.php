<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\ProfileController;
use App\Controllers\UserController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/health', [HealthController::class, 'show']);

    $r->group(['guest'], static function (Router $r): void {
        $r->get('/login', [AuthController::class, 'showLogin']);
        $r->post('/login', [AuthController::class, 'login']);
        $r->get('/setup', [AuthController::class, 'showSetup']);
        $r->post('/setup', [AuthController::class, 'setup']);
    });

    $r->group(['auth'], static function (Router $r): void {
        $r->post('/logout', [AuthController::class, 'logout']);
        $r->get('/', [DashboardController::class, 'index'], ['can:dashboard.view']);

        $r->get('/profile', [ProfileController::class, 'edit']);
        $r->post('/profile', [ProfileController::class, 'update']);
        $r->post('/profile/theme', [ProfileController::class, 'theme']);

        $r->group(['can:users.manage'], static function (Router $r): void {
            $r->get('/users', [UserController::class, 'index']);
            $r->get('/users/new', [UserController::class, 'create']);
            $r->post('/users', [UserController::class, 'store']);
            $r->get('/users/{id:\d+}/edit', [UserController::class, 'edit']);
            $r->post('/users/{id:\d+}', [UserController::class, 'update']);
        });
    });
};
