<?php

declare(strict_types=1);

namespace CommonPHP\Router\Exceptions;

use CommonPHP\HTTP\Request;

class RouteNotFoundException extends RouterException
{
    public static function forPath(string $path, ?string $method = null): self
    {
        $prefix = $method === null ? '' : strtoupper($method) . ' ';

        return new self('No route matched ' . $prefix . '"' . $path . '".');
    }

    public static function forRequest(Request $request): self
    {
        return self::forPath($request->path(), $request->methodValue());
    }

    public static function forName(string $name): self
    {
        return new self('No route is registered with name "' . $name . '".');
    }
}
