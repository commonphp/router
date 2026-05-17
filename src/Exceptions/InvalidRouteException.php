<?php

declare(strict_types=1);

namespace CommonPHP\Router\Exceptions;

class InvalidRouteException extends RouterException
{
    public static function because(string $reason): self
    {
        return new self('Invalid route: ' . $reason);
    }

    public static function invalidMethod(string $method): self
    {
        return new self('Invalid route method "' . $method . '".');
    }

    public static function invalidPath(string $path): self
    {
        return new self('Invalid route path "' . $path . '".');
    }

    public static function invalidName(string $name): self
    {
        return new self('Invalid route name "' . $name . '".');
    }

    public static function invalidHandler(string $type): self
    {
        return new self('Invalid route handler: expected callable, handler object, or resolvable controller, got ' . $type . '.');
    }
}
