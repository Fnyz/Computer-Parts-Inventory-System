<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Auth\LoginThrottle;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if ((new UserRepository())->count() === 0) {
            return Response::redirect('/setup');
        }

        return $this->view('auth/login', ['pageTitle' => 'Sign in'], 'layouts/auth');
    }

    public function login(Request $request): Response
    {
        $username = $request->string('username');
        $password = (string) $request->input('password', '');
        $throttle = new LoginThrottle();

        if ($throttle->tooManyAttempts($username, $request->ip)) {
            return $this->backWithErrors($request, '/login', ['username' => 'Too many wrong passwords. Wait ' . LoginThrottle::WINDOW_MINUTES . ' minutes, then try again.']);
        }
        if (!Auth::attempt($username, $password)) {
            $throttle->recordFailure($username, $request->ip);

            return $this->backWithErrors($request, '/login', ['username' => 'Wrong username or password.']);
        }

        $throttle->clear($username, $request->ip);
        ActivityLogger::log('signed in', 'user', Auth::id(), [], $request->ip);
        $intended = Session::get('_intended', '/');
        Session::forget('_intended');

        return Response::redirect(is_string($intended) && str_starts_with($intended, '/') ? $intended : '/');
    }

    public function logout(Request $request): Response
    {
        if (Auth::user() !== null) {
            ActivityLogger::log('signed out', 'user', Auth::id(), [], $request->ip);
        }
        Auth::logout();

        return Response::redirect('/login');
    }

    /** First-run screen: creates the first Admin. Gone once any account exists. */
    public function showSetup(Request $request): Response
    {
        $this->ensureFirstRun();

        return $this->view('auth/setup', ['pageTitle' => 'Set up PartsDesk'], 'layouts/auth');
    }

    public function setup(Request $request): Response
    {
        $this->ensureFirstRun();
        $v = $this->validate($request, [
            'name' => 'required|max:150',
            'username' => 'required|max:50|regex:/^[A-Za-z0-9._-]{3,50}$/',
            'password' => 'required|minlen:8|max:200',
        ]);
        if ($v->fails()) {
            return $this->backWithErrors($request, '/setup', $v->errors());
        }

        $users = new UserRepository();
        $id = $users->create([
            'username' => $request->string('username'),
            'name' => $request->string('name'),
            'email' => '',
            'phone' => '',
            'password' => (string) $request->input('password'),
            'role' => 'admin',
        ]);
        Auth::login((array) $users->find($id));
        ActivityLogger::log('created the first admin account', 'user', $id, [], $request->ip);

        return $this->redirect('/', 'Welcome to PartsDesk. Your Admin account is ready.');
    }

    private function ensureFirstRun(): void
    {
        if ((new UserRepository())->count() > 0) {
            throw HttpException::notFound('Setup is already done. Sign in instead.');
        }
    }
}
