<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use CommonPHP\Router\Enums\RouteMethod;

class RouteMatch
{
    private RouteParameters $parameters;

    public function __construct(
        private readonly Route $route,
        array|RouteParameters $parameters = [],
        private readonly ?string $path = null,
        private readonly ?RouteMethod $method = null,
        private readonly ?string $scheme = null,
    ) {
        $this->parameters = RouteParameters::from($parameters);
    }

    public function route(): Route
    {
        return $this->route;
    }

    public function parameters(): RouteParameters
    {
        return clone $this->parameters;
    }

    public function hasParameter(string $name): bool
    {
        return $this->parameters->has($name);
    }

    public function parameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters->get($name, $default);
    }

    public function requiredParameter(string $name): mixed
    {
        return $this->parameters->getRequired($name);
    }

    public function handler(): mixed
    {
        return $this->route->handler();
    }

    public function name(): ?string
    {
        return $this->route->name();
    }

    public function path(): string
    {
        return $this->path ?? $this->route->path();
    }

    public function method(): ?RouteMethod
    {
        return $this->method;
    }

    public function scheme(): ?string
    {
        return $this->scheme;
    }

    public function metadata(): RouteMetadata
    {
        return $this->route->metadata();
    }

    public function label(): string
    {
        $name = $this->name();

        if ($name !== null) {
            return 'route "' . $name . '"';
        }

        $method = $this->method?->value() ?? implode('|', $this->route->methodValues());

        return $method . ' ' . $this->route->path();
    }

}
