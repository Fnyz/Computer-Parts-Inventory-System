<?php

declare(strict_types=1);

namespace Tests;

use App\Auth\Auth;
use App\Core\App;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;

/**
 * Feature-test base: every test starts with empty tables and no session,
 * and sends requests through the real router, middleware and controllers.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $pdo = Database::connection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name <> 'phinx_migrations'")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $_SESSION = [];
        $_GET = [];
        Auth::forgetCache();
    }

    protected function get(string $path, array $query = []): Response
    {
        $_GET = $query;

        return $this->send(new Request('GET', $path, $query));
    }

    /** POSTs a form with a valid CSRF token unless $withToken is false. */
    protected function post(string $path, array $data = [], bool $withToken = true, array $files = []): Response
    {
        if ($withToken) {
            $data['_token'] = Csrf::token();
        }

        return $this->send(new Request('POST', $path, [], $data, $files));
    }

    protected function postJson(string $path, array $data): Response
    {
        return $this->send(new Request('POST', $path, [], $data, [], [
            'content-type' => 'application/json', 'accept' => 'application/json', 'x-csrf-token' => Csrf::token(),
        ]));
    }

    protected function send(Request $request): Response
    {
        Auth::forgetCache(); // each call behaves like a fresh HTTP request

        return App::create()->handle($request);
    }

    protected function createUser(string $role = 'admin', string $username = '', string $password = 'password123'): array
    {
        $users = new UserRepository();
        $id = $users->create([
            'username' => $username ?: $role . random_int(100, 999),
            'name' => ucfirst($role) . ' Tester',
            'email' => '',
            'phone' => '',
            'password' => $password,
            'role' => $role,
        ]);

        return (array) $users->find($id);
    }

    protected function actingAs(string $role = 'admin'): array
    {
        $user = $this->createUser($role);
        $_SESSION['user_id'] = (int) $user['id'];
        Auth::forgetCache();

        return $user;
    }

    protected function assertRedirectsTo(string $path, Response $response): void
    {
        $this->assertSame(302, $response->status, 'Expected a redirect, got ' . $response->status . ': ' . strip_tags(substr($response->body, 0, 300)));
        $this->assertSame(url($path), $response->headers['Location'] ?? null);
    }

    protected function assertSeeText(string $text, Response $response): void
    {
        $this->assertStringContainsString($text, html_entity_decode($response->body, ENT_QUOTES));
    }

    protected function flashError(): ?string
    {
        return $_SESSION['_flash']['error'] ?? null;
    }

    protected function row(string $sql, array $params = []): ?array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    protected function value(string $sql, array $params = []): mixed
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }
}
