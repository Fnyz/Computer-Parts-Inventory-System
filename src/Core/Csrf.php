<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }

        return $token;
    }

    public static function valid(Request $request): bool
    {
        $sent = (string) ($request->input('_token') ?? $request->header('X-CSRF-Token') ?? '');
        $expected = Session::get('_csrf');

        return is_string($expected) && $expected !== '' && hash_equals($expected, $sent);
    }
}
