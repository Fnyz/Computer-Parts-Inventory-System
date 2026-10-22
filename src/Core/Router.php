<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Middleware;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

/**
 * Routes map a method + path to [ControllerClass, 'method'] plus a middleware list,
 * e.g. ['auth', 'can:products.manage'].
 */
final class Router
{
    /** @var list<array{string, string, array{class-string, string}, list<string>}> */
    private array $routes = [];

    /** @var list<string> */
    private array $groupMiddleware = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** Routes registered inside $routes all get $middleware first. */
    public function group(array $middleware, callable $routes): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($previous, $middleware);
        $routes($this);
        $this->groupMiddleware = $previous;
    }

    public function dispatch(Request $request): Response
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $r): void {
            foreach ($this->routes as [$method, $path, $handler, $middleware]) {
                $r->addRoute($method, $path, [$handler, $middleware]);
            }
        });

        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $match = $dispatcher->dispatch($method, $request->path);

        if ($match[0] === Dispatcher::NOT_FOUND) {
            throw HttpException::notFound();
        }
        if ($match[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new HttpException(405, 'That action is not allowed here.');
        }

        [[$handler, $middleware], $params] = [$match[1], $match[2]];
        $request->params = $params;

        $core = static function (Request $request) use ($handler): Response {
            [$class, $action] = $handler;

            return (new $class())->{$action}($request);
        };

        // Wrap the controller in its middleware, outermost first
        $pipeline = array_reduce(
            array_reverse($middleware),
            static fn (callable $next, string $name) => static fn (Request $r) => Middleware::make($name)->handle($r, $next),
            $core,
        );

        return $pipeline($request);
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[] = [$method, $path, $handler, array_merge($this->groupMiddleware, $middleware)];
    }
}
