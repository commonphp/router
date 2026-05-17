<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use ArrayIterator;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\DuplicateRouteException;
use CommonPHP\Router\Exceptions\RouteNotFoundException;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Route>
 */
class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    /**
     * @param iterable<Route> $routes
     */
    public function __construct(iterable $routes = [])
    {
        foreach ($routes as $route) {
            $this->add($route);
        }
    }

    public function add(Route $route): static
    {
        $this->assertUnique($route);
        $this->routes[] = $route;

        return $this;
    }

    /**
     * @param RouteMethod|RequestMethod|string|array<RouteMethod|RequestMethod|string> $methods
     * @param array<string, mixed> $constraints
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $metadata
     * @param list<string> $schemes
     * @param list<mixed> $middleware
     */
    public function route(
        RouteMethod|RequestMethod|string|array $methods,
        string $path,
        mixed $handler,
        ?string $name = null,
        array $constraints = [],
        array $defaults = [],
        array $metadata = [],
        array $schemes = [],
        array $middleware = [],
    ): Route {
        $route = new Route($methods, $path, $handler, $name, $constraints, $defaults, $metadata, $schemes, $middleware);
        $this->add($route);

        return $route;
    }

    public function any(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::cases(), $path, $handler, $name);
    }

    public function get(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::GET, $path, $handler, $name);
    }

    public function post(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::POST, $path, $handler, $name);
    }

    public function put(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::PUT, $path, $handler, $name);
    }

    public function patch(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::PATCH, $path, $handler, $name);
    }

    public function delete(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::DELETE, $path, $handler, $name);
    }

    public function options(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::OPTIONS, $path, $handler, $name);
    }

    /**
     * @param callable(RouteGroup): void|null $routes
     * @param array<string, mixed> $constraints
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $metadata
     * @param list<string> $schemes
     * @param list<mixed> $middleware
     */
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
        $group = new RouteGroup($this, $prefix, $namePrefix, $constraints, $defaults, $metadata, $schemes, $middleware);

        if ($routes !== null) {
            $routes($group);
        }

        return $group;
    }

    public function hasNamed(string $name): bool
    {
        return $this->findByName($name) !== null;
    }

    public function named(string $name): Route
    {
        return $this->findByName($name) ?? throw RouteNotFoundException::forName($name);
    }

    public function findByName(string $name): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->name() === $name) {
                return $route;
            }
        }

        return null;
    }

    public function remove(Route|string $route): static
    {
        $this->routes = array_values(array_filter(
            $this->routes,
            static fn (Route $registered): bool => $route instanceof Route
                ? $registered !== $route
                : $registered->name() !== $route,
        ));

        return $this;
    }

    public function clear(): static
    {
        $this->routes = [];

        return $this;
    }

    /**
     * @return list<Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(array_filter(
            array_map(static fn (Route $route): ?string => $route->name(), $this->routes),
            static fn (?string $name): bool => $name !== null,
        ));
    }

    public function isEmpty(): bool
    {
        return $this->routes === [];
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->routes);
    }

    private function assertUnique(Route $route): void
    {
        foreach ($this->routes as $existing) {
            if ($route->name() !== null && $existing->name() === $route->name()) {
                throw DuplicateRouteException::forName($route->name());
            }

            if ($existing->path() !== $route->path()) {
                continue;
            }

            if (array_intersect($existing->methodValues(), $route->methodValues()) !== []) {
                throw DuplicateRouteException::forRoute($route);
            }
        }
    }
}
