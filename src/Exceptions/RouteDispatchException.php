<?php

declare(strict_types=1);

namespace CommonPHP\Router\Exceptions;

use CommonPHP\Router\RouteMatch;
use Throwable;

class RouteDispatchException extends RouterException
{
    public static function invalidHandler(RouteMatch $match, string $type): self
    {
        return new self('Route handler for ' . $match->label() . ' is not dispatchable: ' . $type . '.');
    }

    public static function invalidResponse(RouteMatch $match, string $type): self
    {
        return new self('Route handler for ' . $match->label() . ' must return a Response instance, got ' . $type . '.');
    }

    public static function failed(RouteMatch $match, Throwable $previous): self
    {
        return new self(
            'Route handler for ' . $match->label() . ' failed: ' . $previous->getMessage(),
            0,
            $previous,
        );
    }
}
