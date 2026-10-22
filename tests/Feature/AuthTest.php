<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class AuthTest extends TestCase
{
    public function test_first_run_sends_login_to_setup_and_creates_an_admin(): void
    {
        $this->assertRedirectsTo('/setup', $this->get('/login'));

        $response = $this->post('/setup', ['name' => 'Jez', 'username' => 'jez', 'password' => 'supersecret', 'role' => 'employee']);

        $this->assertRedirectsTo('/', $response);
        $this->assertSame('admin', $this->value("SELECT role FROM users WHERE username = 'jez'"), 'The first account is always an Admin');
        $this->assertSame(200, $this->get('/')->status, 'Setup signs the new Admin in');
    }

    public function test_setup_is_gone_once_an_account_exists(): void
    {
        $this->createUser('admin', 'boss');

        $this->assertSame(404, $this->get('/setup')->status);
        $this->assertSame(404, $this->post('/setup', ['name' => 'X', 'username' => 'intruder', 'password' => 'password123'])->status);
        $this->assertSame(0, (int) $this->value("SELECT COUNT(*) FROM users WHERE username = 'intruder'"));
    }

    public function test_sign_in_with_correct_and_wrong_passwords(): void
    {
        $this->createUser('staff', 'maria', 'correct-horse');

        $this->assertRedirectsTo('/login', $this->post('/login', ['username' => 'maria', 'password' => 'wrong']));
        $this->assertSame('Wrong username or password.', $this->flashError());
        $this->assertRedirectsTo('/login', $this->post('/login', ['username' => 'maria', 'password' => '']));

        $this->assertRedirectsTo('/', $this->post('/login', ['username' => 'maria', 'password' => 'correct-horse']));
        $this->assertSame(200, $this->get('/')->status);
    }

    public function test_sign_in_is_blocked_after_five_wrong_passwords(): void
    {
        $this->createUser('staff', 'maria', 'correct-horse');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'maria', 'password' => 'nope' . $i]);
        }

        $this->post('/login', ['username' => 'maria', 'password' => 'correct-horse']);

        $this->assertStringContainsString('Too many wrong passwords', (string) $this->flashError());
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function test_inactive_accounts_cannot_sign_in_and_are_signed_out(): void
    {
        $user = $this->createUser('employee', 'gone', 'password123');
        $this->post('/login', ['username' => 'gone', 'password' => 'password123']);
        $this->value('UPDATE users SET is_active = 0 WHERE id = ?', [$user['id']]);

        $this->assertRedirectsTo('/login', $this->get('/'));
        $this->assertRedirectsTo('/login', $this->post('/login', ['username' => 'gone', 'password' => 'password123']));
    }

    public function test_pages_require_sign_in_and_remember_where_you_were_going(): void
    {
        $this->createUser('admin', 'boss', 'password123');

        $this->assertRedirectsTo('/login', $this->get('/users'));
        $this->assertRedirectsTo('/users', $this->post('/login', ['username' => 'boss', 'password' => 'password123']));
    }

    public function test_forms_without_a_csrf_token_are_rejected(): void
    {
        $this->createUser('admin', 'boss', 'password123');

        $this->assertSame(419, $this->post('/login', ['username' => 'boss', 'password' => 'password123'], withToken: false)->status);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function test_sign_out_ends_the_session(): void
    {
        $this->actingAs('admin');

        $this->assertRedirectsTo('/login', $this->post('/logout'));
        $this->assertRedirectsTo('/login', $this->get('/'));
    }

    public function test_health_check_reports_database_status(): void
    {
        $response = $this->get('/health');

        $this->assertSame(200, $response->status);
        $this->assertSame('ok', json_decode($response->body, true)['database']);
    }
}
