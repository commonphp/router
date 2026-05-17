<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\Router\Contracts\RouteConstraintInterface;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\InvalidRouteException;
use CommonPHP\Router\Exceptions\InvalidRouteParameterException;

class Route
{
    /**
     * @var list<RouteMethod>
     */
    private array $methods;

    private string $path;

    /**
     * @var array<string, RouteConstraintInterface>
     */
    private array $constraints = [];

    private RouteParameters $defaults;

    private RouteMetadata $metadata;

    /**
     * @var list<string>
     */
    private array $schemes = [];

    /**
     * @var list<mixed>
     */
    private array $middleware = [];

    /**
     * @param RouteMethod|RequestMethod|string|array<RouteMethod|RequestMethod|string> $methods
     * @param array<string, RouteConstraintInterface|string|callable> $constraints
     * @param array<string, mixed>|RouteParameters $defaults
     * @param array<string, mixed>|RouteMetadata $metadata
     * @param list<string> $schemes
     * @param list<mixed> $middleware
     */
    public function __construct(
        RouteMethod|RequestMethod|string|array $methods,
        string $path,
        private mixed $handler,
        private ?string $name = null,
        array $constraints = [],
        array|RouteParameters $defaults = [],
        array|RouteMetadata $metadata = [],
        array $schemes = [],
        array $middleware = [],
    ) {
        $this->methods = $this->normalizeMethods($methods);
        $this->path = $this->normalizePath($path);
        $this->defaults = RouteParameters::from($defaults);
        $this->metadata = RouteMetadata::from($metadata);

        if ($name !== null) {
            $this->named($name);
        }

        foreach ($constraints as $parameter => $constraint) {
            $this->where((string) $parameter, $constraint);
        }

        $this->allowSchemes(...$schemes);
        $this->through(...$middleware);
        $this->validatePathPlaceholders();
    }

    public static function match(
        RouteMethod|RequestMethod|string|array $methods,
        string $path,
        mixed $handler,
        ?string $name = null,
    ): self {
        return new self($methods, $path, $handler, $name);
    }

    public static function any(string $path, mixed $handler, ?string $name = null): self
    {
        return new self(RouteMethod::cases(), $path, $handler, $name);
    }

    public static function get(string $path, mixed $handler, ?string $name = null): self
    {
        return new self(RouteMethod::GET, $path, $handler, $name);
    }

    public static function post(string $path, mixed $handler, ?string $name = null): self
    {
        return new self(RouteMethod::POST, $path, $handler, $name);
    }

    public static function put(string $path, mixed $handler, ?string $name = null): self
    {
        return new self(RouteMethod::PUT, $path, $handler, $name);
    }

    public static function patch(string $path, mixed $handler, ?string $name = null): self
    {
        return new self(RouteMethod::PATCH, $path, $handler, $name);
    }

    public static function delete(string $path, mixed $handler, ?string $name = null): self
    {
        return new self(RouteMethod::DELETE, $path, $handler, $name);
    }

    public static function options(string $path, mixed $handler, ?string $name = null): self
    {
        return new self(RouteMethod::OPTIONS, $path, $handler, $name);
    }

    /**
     * @return list<RouteMethod>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * @return list<string>
     */
    public function methodValues(): array
    {
        return array_map(static fn (RouteMethod $method): string => $method->value(), $this->methods);
    }

    /**
     * @return list<string>
     */
    public function allowedMethodValues(): array
    {
        $methods = $this->methodValues();

        if (in_array(RouteMethod::GET->value(), $methods, true) && !in_array(RouteMethod::HEAD->value(), $methods, true)) {
            $methods[] = RouteMethod::HEAD->value();
        }

        sort($methods);

        return $methods;
    }

    public function allowsMethod(RouteMethod|RequestMethod|string $method): bool
    {
        $method = RouteMethod::normalize($method);

        foreach ($this->methods as $allowed) {
            if ($allowed === $method) {
                return true;
            }

            if ($method === RouteMethod::HEAD && $allowed === RouteMethod::GET) {
                return true;
            }
        }

        return false;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    public function setHandler(mixed $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function named(?string $name): static
    {
        if ($name !== null && !preg_match('/^[A-Za-z0-9_.:-]+$/', $name)) {
            throw InvalidRouteException::invalidName($name);
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @return array<string, RouteConstraintInterface>
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    public function constraint(string $parameter): ?RouteConstraintInterface
    {
        return $this->constraints[$parameter] ?? null;
    }

    public function where(string $parameter, mixed $constraint): static
    {
        $this->assertParameterName($parameter);
        $this->constraints[$parameter] = $this->normalizeConstraint($constraint);

        return $this;
    }

    /**
     * @param list<string|int|float> $values
     */
    public function whereIn(string $parameter, array $values, bool $caseSensitive = true): static
    {
        return $this->where($parameter, RouteConstraint::in($values, $caseSensitive));
    }

    public function whereNumber(string $parameter): static
    {
        return $this->where($parameter, RouteConstraint::number());
    }

    public function whereAlpha(string $parameter): static
    {
        return $this->where($parameter, RouteConstraint::alpha());
    }

    public function whereAlphaNumeric(string $parameter): static
    {
        return $this->where($parameter, RouteConstraint::alphaNumeric());
    }

    public function whereSlug(string $parameter): static
    {
        return $this->where($parameter, RouteConstraint::slug());
    }

    public function whereUuid(string $parameter): static
    {
        return $this->where($parameter, RouteConstraint::uuid());
    }

    public function defaults(): RouteParameters
    {
        return clone $this->defaults;
    }

    /**
     * @param array<string, mixed>|RouteParameters $defaults
     */
    public function withDefaults(array|RouteParameters $defaults): static
    {
        $this->defaults = RouteParameters::from($defaults);

        return $this;
    }

    public function default(string $parameter, mixed $value): static
    {
        $this->defaults->set($parameter, $value);

        return $this;
    }

    public function metadata(): RouteMetadata
    {
        return clone $this->metadata;
    }

    /**
     * @param array<string, mixed>|RouteMetadata $metadata
     */
    public function withMetadata(array|RouteMetadata $metadata): static
    {
        $this->metadata = RouteMetadata::from($metadata);

        return $this;
    }

    public function meta(string $key, mixed $value): static
    {
        $this->metadata->set($key, $value);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function schemes(): array
    {
        return $this->schemes;
    }

    public function allowSchemes(string ...$schemes): static
    {
        $normalized = [];

        foreach ($schemes as $scheme) {
            $scheme = strtolower(trim($scheme));

            if ($scheme === '') {
                continue;
            }

            if (!in_array($scheme, ['http', 'https'], true)) {
                throw InvalidRouteException::because('Unsupported route scheme "' . $scheme . '".');
            }

            if (!in_array($scheme, $normalized, true)) {
                $normalized[] = $scheme;
            }
        }

        $this->schemes = $normalized;

        return $this;
    }

    public function httpOnly(): static
    {
        return $this->allowSchemes('http');
    }

    public function httpsOnly(): static
    {
        return $this->allowSchemes('https');
    }

    public function allowsScheme(?string $scheme): bool
    {
        if ($this->schemes === [] || $scheme === null || trim($scheme) === '') {
            return true;
        }

        return in_array(strtolower($scheme), $this->schemes, true);
    }

    /**
     * @return list<mixed>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    public function through(mixed ...$middleware): static
    {
        foreach ($middleware as $entry) {
            $this->middleware[] = $entry;
        }

        return $this;
    }

    public function signature(): string
    {
        return implode('|', $this->methodValues()) . ' ' . $this->path;
    }

    /**
     * @param RouteMethod|RequestMethod|string|array<RouteMethod|RequestMethod|string> $methods
     * @return list<RouteMethod>
     */
    private function normalizeMethods(RouteMethod|RequestMethod|string|array $methods): array
    {
        $methods = is_array($methods) ? $methods : $this->splitMethods($methods);

        if ($methods === []) {
            throw InvalidRouteException::because('At least one route method is required.');
        }

        $normalized = [];

        foreach ($methods as $method) {
            foreach ($this->splitMethods($method) as $entry) {
                $entry = RouteMethod::normalize($entry);

                if (!isset($normalized[$entry->value()])) {
                    $normalized[$entry->value()] = $entry;
                }
            }
        }

        return array_values($normalized);
    }

    /**
     * @return list<RouteMethod|RequestMethod|string>
     */
    private function splitMethods(RouteMethod|RequestMethod|string $method): array
    {
        if (!($method instanceof RouteMethod) && !($method instanceof RequestMethod) && preg_match('/[,|]/', $method)) {
            return array_values(array_filter(
                array_map('trim', preg_split('/[,|]/', $method) ?: []),
                static fn (string $value): bool => $value !== '',
            ));
        }

        return [$method];
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw InvalidRouteException::invalidPath($path);
        }

        if ($path === '*') {
            return $path;
        }

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    private function normalizeConstraint(mixed $constraint): RouteConstraintInterface
    {
        if ($constraint instanceof RouteConstraintInterface) {
            return $constraint;
        }

        if (is_string($constraint)) {
            return new RouteConstraint($constraint);
        }

        if (is_callable($constraint)) {
            return RouteConstraint::callback($constraint);
        }

        throw InvalidRouteException::because('Invalid route constraint type ' . get_debug_type($constraint) . '.');
    }

    private function validatePathPlaceholders(): void
    {
        if (substr_count($this->path, '{') !== substr_count($this->path, '}')) {
            throw InvalidRouteException::because('Route path "' . $this->path . '" has unbalanced parameter braces.');
        }

        if (!preg_match_all('/\{([^}]*)\}/', $this->path, $matches)) {
            return;
        }

        $seen = [];

        foreach ($matches[1] as $placeholder) {
            if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)(\*)?(?::.+)?$/', $placeholder, $parts)) {
                throw InvalidRouteParameterException::forName($placeholder);
            }

            $name = $parts[1];

            if (isset($seen[$name])) {
                throw InvalidRouteParameterException::duplicate($name);
            }

            $seen[$name] = true;
        }
    }

    private function assertParameterName(string $parameter): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $parameter)) {
            throw InvalidRouteParameterException::forName($parameter);
        }
    }

}
