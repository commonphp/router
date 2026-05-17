<?php

declare(strict_types=1);

namespace CommonPHP\Router\Contracts;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteCollection;
use CommonPHP\Router\RouteMatch;

interface RouterInterface
{
    public function routes(): RouteCollection;

    public function add(Route $route): static;

    /**
     * @param RouteMethod|RequestMethod|string|array<RouteMethod|RequestMethod|string> $methods
     */
    public function route(
        RouteMethod|RequestMethod|string|array $methods,
        string $path,
        mixed $handler,
        ?string $name = null,
    ): Route;

    public function match(
        Request|string $request,
        RouteMethod|RequestMethod|string|null $method = null,
    ): RouteMatch;

    public function dispatch(Request $request): Response;
}
