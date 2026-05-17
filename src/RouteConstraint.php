<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use Closure;
use CommonPHP\Router\Contracts\RouteConstraintInterface;
use CommonPHP\Router\Exceptions\InvalidRouteException;

class RouteConstraint implements RouteConstraintInterface
{
    private ?Closure $validator;

    public function __construct(
        private readonly string $pattern = '[^/]+',
        ?callable $validator = null,
        private readonly string $description = '',
    ) {
        if (trim($pattern) === '') {
            throw InvalidRouteException::because('Route constraint pattern cannot be empty.');
        }

        $this->validator = $validator === null ? null : Closure::fromCallable($validator);
        $this->assertValidPattern($pattern);
    }

    public static function regex(string $pattern, string $description = ''): self
    {
        return new self($pattern, null, $description);
    }

    public static function callback(callable $validator, string $pattern = '[^/]+', string $description = ''): self
    {
        return new self($pattern, $validator, $description);
    }

    /**
     * @param list<string|int|float> $values
     */
    public static function in(array $values, bool $caseSensitive = true): self
    {
        $allowed = array_map(static fn (string|int|float $value): string => (string) $value, $values);

        return new self(
            '[^/]+',
            static function (string $value) use ($allowed, $caseSensitive): bool {
                if ($caseSensitive) {
                    return in_array($value, $allowed, true);
                }

                return in_array(strtolower($value), array_map('strtolower', $allowed), true);
            },
            'one of ' . implode(', ', $allowed),
        );
    }

    public static function number(): self
    {
        return new self('[0-9]+', null, 'number');
    }

    public static function alpha(): self
    {
        return new self('[A-Za-z]+', null, 'letters');
    }

    public static function alphaNumeric(): self
    {
        return new self('[A-Za-z0-9]+', null, 'letters and numbers');
    }

    public static function slug(): self
    {
        return new self('[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*', null, 'slug');
    }

    public static function uuid(): self
    {
        return new self(
            '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}',
            null,
            'UUID',
        );
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function matches(string $value): bool
    {
        if (@preg_match($this->compiledRegex(), $value) !== 1) {
            return false;
        }

        return $this->validator === null || (bool) ($this->validator)($value);
    }

    public function description(): string
    {
        return $this->description === '' ? $this->pattern : $this->description;
    }

    private function compiledRegex(): string
    {
        return '#^(?:' . str_replace('#', '\#', $this->pattern) . ')$#u';
    }

    private function assertValidPattern(string $pattern): void
    {
        if (@preg_match('#^(?:' . str_replace('#', '\#', $pattern) . ')$#u', '') === false) {
            throw InvalidRouteException::because('Invalid route constraint pattern "' . $pattern . '".');
        }
    }
}
