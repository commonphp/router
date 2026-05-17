<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Contracts\RouteDispatcherInterface;
use CommonPHP\Router\Contracts\RouterInterface;
use CommonPHP\Router\Enums\RouteMethod;
use Psr\Container\ContainerInterface;

class Router implements RouterInterface
{
    private RouteCollection $routes;

    private RouteMatcher $matcher;

    private RouteDispatcherInterface $dispatcher;

    public function __construct(
        ?RouteCollection $routes = null,
        ?RouteDispatcherInterface $dispatcher = null,
        ?ContainerInterface $container = null,
    ) {
        $this->routes = $routes ?? new RouteCollection();
        $this->matcher = new RouteMatcher($this->routes);
        $this->dispatcher = $dispatcher ?? new RouteDispatcher($container);
    }

    public function routes(): RouteCollection
    {
        return $this->routes;
    }

    public function add(Route $route): static
    {
        $this->routes->add($route);

        return $this;
    }

    public function route(
        RouteMethod|RequestMethod|string|array $methods,
        string $path,
        mixed $handler,
        ?string $name = null,
    ): Route {
        return $this->routes->route($methods, $path, $handler, $name);
    }

    public function any(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->routes->any($path, $handler, $name);
    }

    public function get(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->routes->get($path, $handler, $name);
    }

    public function post(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->routes->post($path, $handler, $name);
    }

    public function put(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->routes->put($path, $handler, $name);
    }

    public function patch(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->routes->patch($path, $handler, $name);
    }

    public function delete(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->routes->delete($path, $handler, $name);
    }

    public function options(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->routes->options($path, $handler, $name);
    }

    public function group(
        string $prefix = '',
        ?callable $routes = null,
        ?string $namePrefix = null,
        array $constraints = [],
        array $defaults = [],
        array $metadata = [],
        array $schemes = [],
        array $middleware = [],
    ): RouteGroup {
        return $this->routes->group($prefix, $routes, $namePrefix, $constraints, $defaults, $metadata, $schemes, $middleware);
    }

    public function named(string $name): Route
    {
        return $this->routes->named($name);
    }

    public function match(
        Request|string $request,
        RouteMethod|RequestMethod|string|null $method = null,
    ): RouteMatch {
        return $this->matcher->match($request, $method);
    }

    public function find(
        Request|string $request,
        RouteMethod|RequestMethod|string|null $method = null,
    ): ?RouteMatch {
        return $this->matcher->find($request, $method);
    }

    public function dispatch(Request $request): Response
    {
        return $this->dispatcher->dispatch($this->match($request), $request);
    }

    public function handle(Request $request): Response
    {
        return $this->dispatch($request);
    }
}
