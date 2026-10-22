<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Auth\Permissions;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;

/** Account management. Admin only (see routes). */
final class UserController extends Controller
{
    private const RULES = [
        'name' => 'required|max:150',
        'username' => 'required|max:50|regex:/^[A-Za-z0-9._-]{3,50}$/',
        'email' => 'email|max:150',
        'phone' => 'max:30',
        'role' => 'required|in:admin,staff,employee',
    ];

    public function index(Request $request): Response
    {
        return $this->view('users/index', ['pageTitle' => 'Users', 'users' => (new UserRepository())->all()]);
    }

    public function create(Request $request): Response
    {
        return $this->view('users/form', ['pageTitle' => 'New user', 'user' => null]);
    }

    public function store(Request $request): Response
    {
        $users = new UserRepository();
        $v = $this->validate($request, self::RULES + ['password' => 'required|minlen:8|max:200']);
        $errors = $v->errors();
        if (!isset($errors['username']) && $users->usernameTaken($request->string('username'))) {
            $errors['username'] = 'That username is already taken.';
        }
        if ($errors) {
            return $this->backWithErrors($request, '/users/new', $errors);
        }

        $id = $users->create($this->fields($request) + ['password' => (string) $request->input('password')]);
        ActivityLogger::log('created user', 'user', $id, ['username' => $request->string('username'), 'role' => $request->string('role')]);

        return $this->redirect('/users', 'User ' . $request->string('username') . ' created.');
    }

    public function edit(Request $request): Response
    {
        $user = $this->findOr404((new UserRepository())->find((int) $request->param('id')));

        return $this->view('users/form', ['pageTitle' => 'Edit ' . $user['username'], 'user' => $user]);
    }

    public function update(Request $request): Response
    {
        $users = new UserRepository();
        $user = $this->findOr404($users->find((int) $request->param('id')));
        $id = (int) $user['id'];
        $back = "/users/{$id}/edit";

        $v = $this->validate($request, self::RULES + ['password' => 'minlen:8|max:200']);
        $errors = $v->errors();
        if (!isset($errors['username']) && $users->usernameTaken($request->string('username'), $id)) {
            $errors['username'] = 'That username is already taken.';
        }
        $active = $request->input('is_active') ? 1 : 0;
        $role = $request->string('role');
        $wasActiveAdmin = $user['role'] === 'admin' && (int) $user['is_active'] === 1;
        if ($wasActiveAdmin && ($role !== 'admin' || !$active) && $users->countActiveAdmins() <= 1) {
            $errors['role'] = 'This is the only active Admin. Make another user an Admin first.';
        }
        if ($id === Auth::id() && !$active) {
            $errors['is_active'] = 'You can’t deactivate your own account.';
        }
        if ($errors) {
            return $this->backWithErrors($request, $back, $errors);
        }

        $users->update($id, $this->fields($request) + ['is_active' => $active, 'password' => (string) $request->input('password', '')]);
        ActivityLogger::log('updated user', 'user', $id, [
            'role' => $user['role'] !== $role ? [$user['role'], $role] : null,
            'active' => (int) $user['is_active'] !== $active ? [(int) $user['is_active'], $active] : null,
            'password_changed' => $request->string('password') !== '' ? true : null,
        ]);

        return $this->redirect('/users', 'User ' . $request->string('username') . ' saved.');
    }

    private function fields(Request $request): array
    {
        return [
            'name' => $request->string('name'),
            'username' => $request->string('username'),
            'email' => $request->string('email'),
            'phone' => $request->string('phone'),
            'role' => array_key_exists($request->string('role'), Permissions::ROLES) ? $request->string('role') : 'employee',
        ];
    }
}
