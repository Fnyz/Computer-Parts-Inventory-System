<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;

/** A user's own account: name, contact details, password and theme. */
final class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return $this->view('profile/edit', ['pageTitle' => 'My account', 'user' => Auth::user()]);
    }

    public function update(Request $request): Response
    {
        $users = new UserRepository();
        $me = (array) Auth::user();
        $v = $this->validate($request, [
            'name' => 'required|max:150',
            'username' => 'required|max:50|regex:/^[A-Za-z0-9._-]{3,50}$/',
            'email' => 'email|max:150',
            'phone' => 'max:30',
            'password' => 'minlen:8|max:200',
        ]);
        $errors = $v->errors();
        if (!isset($errors['username']) && $users->usernameTaken($request->string('username'), (int) $me['id'])) {
            $errors['username'] = 'That username is already taken.';
        }
        $newPassword = (string) $request->input('password', '');
        if ($newPassword !== '' && !password_verify((string) $request->input('current_password', ''), $me['password_hash'])) {
            $errors['current_password'] = 'Your current password is not correct.';
        }
        if ($errors) {
            return $this->backWithErrors($request, '/profile', $errors);
        }

        $users->update((int) $me['id'], [
            'name' => $request->string('name'),
            'username' => $request->string('username'),
            'email' => $request->string('email'),
            'phone' => $request->string('phone'),
            'password' => $newPassword,
        ]);
        ActivityLogger::log('updated own account', 'user', (int) $me['id'], ['password_changed' => $newPassword !== '' ? true : null]);

        return $this->redirect('/profile', 'Your account is saved.');
    }

    /** Saves the Light / Dark / System choice so it follows the user to other computers. */
    public function theme(Request $request): Response
    {
        $theme = $request->string('theme');
        if (!in_array($theme, ['light', 'dark', 'system'], true)) {
            return Response::json(['error' => 'Choose light, dark or system.'], 422);
        }
        (new UserRepository())->setTheme(Auth::id(), $theme);

        return Response::json(['theme' => $theme]);
    }
}
