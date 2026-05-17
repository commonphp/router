<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\Router\Enums\RouteMethod;

class RouteGroup
{
    private RouteParameters $defaults;

    private RouteMetadata $metadata;

    /**
     * @var array<string, mixed>
     */
    private array $constraints;

    /**
     * @var list<string>
     */
    private array $schemes;

    /**
     * @var list<mixed>
     */
    private array $middleware;

    /**
     * @param array<string, mixed> $constraints
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $metadata
     * @param list<string> $schemes
     * @param list<mixed> $middleware
     */
    public function __construct(
        private readonly RouteCollection $collection,
        private readonly string $prefix = '',
        private readonly ?string $namePrefix = null,
        array $constraints = [],
        array $defaults = [],
        array $metadata = [],
        array $schemes = [],
        array $middleware = [],
    ) {
        $this->constraints = $constraints;
        $this->defaults = new RouteParameters($defaults);
        $this->metadata = new RouteMetadata($metadata);
        $this->schemes = $schemes;
        $this->middleware = $middleware;
    }

    public function collection(): RouteCollection
    {
        return $this->collection;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function namePrefix(): ?string
    {
        return $this->namePrefix;
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
        return $this->collection->route(
            $methods,
            $this->joinPaths($this->prefix, $path),
            $handler,
            $this->joinNames($this->namePrefix, $name),
            array_replace($this->constraints, $constraints),
            array_merge($this->defaults->all(), $defaults),
            array_merge($this->metadata->all(), $metadata),
            $schemes === [] ? $this->schemes : $schemes,
            array_merge($this->middleware, $middleware),
        );
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
     */
    public function group(string $prefix = '', ?callable $routes = null, ?string $namePrefix = null): RouteGroup
    {
        $group = new self(
            $this->collection,
            $this->joinPaths($this->prefix, $prefix),
            $this->joinNames($this->namePrefix, $namePrefix),
            $this->constraints,
            $this->defaults->all(),
            $this->metadata->all(),
            $this->schemes,
            $this->middleware,
        );

        if ($routes !== null) {
            $routes($group);
        }

        return $group;
    }

    private function joinPaths(string $prefix, string $path): string
    {
        $prefix = trim($prefix);
        $path = trim($path);

        if ($prefix === '' || $prefix === '/') {
            return $path === '' ? '/' : (str_starts_with($path, '/') ? $path : '/' . $path);
        }

        if ($path === '' || $path === '/') {
            return str_starts_with($prefix, '/') ? $prefix : '/' . $prefix;
        }

        return '/' . trim($prefix, '/') . '/' . ltrim($path, '/');
    }

    private function joinNames(?string $prefix, ?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return ($prefix ?? '') . $name;
    }

}
