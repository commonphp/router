<?php

declare(strict_types=1);

namespace CommonPHP\Router\Contracts;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\RouteMatch;

interface RouteMatcherInterface
{
    public function match(
        Request|string $request,
        RouteMethod|RequestMethod|string|null $method = null,
    ): RouteMatch;

    public function find(
        Request|string $request,
        RouteMethod|RequestMethod|string|null $method = null,
    ): ?RouteMatch;
}
