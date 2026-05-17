<?php

declare(strict_types=1);

namespace CommonPHP\Router\Exceptions;

use CommonPHP\Router\Route;

class DuplicateRouteException extends RouterException
{
    public static function forRoute(Route $route): self
    {
        return new self('Route already registered for ' . $route->signature() . '.');
    }

    public static function forName(string $name): self
    {
        return new self('Route name "' . $name . '" is already registered.');
    }
}
