<?php

declare(strict_types=1);

namespace CommonPHP\Router\Exceptions;

class InvalidRouteParameterException extends RouterException
{
    public static function forName(string $name): self
    {
        return new self('Invalid route parameter name "' . $name . '".');
    }

    public static function duplicate(string $name): self
    {
        return new self('Route parameter "' . $name . '" is defined more than once.');
    }

    public static function missing(string $name): self
    {
        return new self('Route parameter "' . $name . '" was not found.');
    }

    public static function failed(string $name, string $value): self
    {
        return new self('Route parameter "' . $name . '" does not accept value "' . $value . '".');
    }
}
