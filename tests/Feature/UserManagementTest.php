<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    public function test_only_admins_reach_the_users_pages(): void
    {
        $this->actingAs('staff');
        $this->assertSame(403, $this->get('/users')->status);
        $this->assertSame(403, $this->post('/users', ['name' => 'X', 'username' => 'sneaky', 'password' => 'password123', 'role' => 'admin'])->status);
        $this->assertSame(0, (int) $this->value("SELECT COUNT(*) FROM users WHERE username = 'sneaky'"));
    }

    public function test_admin_creates_a_user_with_validation(): void
    {
        $this->actingAs('admin');

        $this->assertRedirectsTo('/users/new', $this->post('/users', ['name' => 'Ana', 'username' => 'a', 'password' => 'short', 'role' => 'owner']));
        $this->assertRedirectsTo('/users', $this->post('/users', ['name' => 'Ana', 'username' => 'ana', 'password' => 'password123', 'role' => 'staff']));
        $this->assertSame('staff', $this->value("SELECT role FROM users WHERE username = 'ana'"));

        $this->assertRedirectsTo('/users/new', $this->post('/users', ['name' => 'Ana 2', 'username' => 'ana', 'password' => 'password123', 'role' => 'staff']));
        $this->assertSame('That username is already taken.', $this->flashError());
    }

    public function test_blank_password_on_edit_keeps_the_old_one(): void
    {
        $this->actingAs('admin');
        $emp = $this->createUser('employee', 'emp1', 'original-pass');

        $this->post('/users/' . $emp['id'], ['name' => 'Renamed', 'username' => 'emp1', 'role' => 'employee', 'is_active' => '1', 'password' => '']);

        $row = $this->row('SELECT name, password_hash FROM users WHERE id = ?', [$emp['id']]);
        $this->assertSame('Renamed', $row['name']);
        $this->assertTrue(password_verify('original-pass', $row['password_hash']));
    }

    public function test_the_last_active_admin_cannot_be_demoted_or_deactivated(): void
    {
        $me = $this->actingAs('admin');

        $this->post('/users/' . $me['id'], ['name' => 'Me', 'username' => $me['username'], 'role' => 'staff', 'is_active' => '1']);
        $this->assertSame('admin', $this->value('SELECT role FROM users WHERE id = ?', [$me['id']]));
        $this->assertStringContainsString('only active Admin', (string) $this->flashError());
    }

    public function test_admins_cannot_deactivate_themselves(): void
    {
        $me = $this->actingAs('admin');
        $this->createUser('admin', 'second-admin');

        $this->post('/users/' . $me['id'], ['name' => 'Me', 'username' => $me['username'], 'role' => 'admin']);
        $this->assertSame(1, (int) $this->value('SELECT is_active FROM users WHERE id = ?', [$me['id']]));
    }

    public function test_changing_your_own_password_needs_the_current_one(): void
    {
        $me = $this->actingAs('employee');
        $base = ['name' => 'Me', 'username' => $me['username'], 'email' => '', 'phone' => ''];

        $this->post('/profile', $base + ['current_password' => 'wrong', 'password' => 'brand-new-pass']);
        $this->assertTrue(password_verify('password123', (string) $this->value('SELECT password_hash FROM users WHERE id = ?', [$me['id']])));

        $this->post('/profile', $base + ['current_password' => 'password123', 'password' => 'brand-new-pass']);
        $this->assertTrue(password_verify('brand-new-pass', (string) $this->value('SELECT password_hash FROM users WHERE id = ?', [$me['id']])));
    }

    public function test_theme_choice_is_saved_on_the_account(): void
    {
        $me = $this->actingAs('employee');

        $this->assertSame(200, $this->postJson('/profile/theme', ['theme' => 'light'])->status);
        $this->assertSame('light', $this->value('SELECT theme FROM users WHERE id = ?', [$me['id']]));
        $this->assertSame(422, $this->postJson('/profile/theme', ['theme' => 'neon'])->status);
    }
}
