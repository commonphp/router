<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use ArrayAccess;
use ArrayIterator;
use CommonPHP\Router\Exceptions\InvalidRouteParameterException;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class RouteParameters implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->replace($parameters);
    }

    /**
     * @param array<string, mixed>|self $parameters
     */
    public static function from(array|self $parameters): self
    {
        return $parameters instanceof self ? clone $parameters : new self($parameters);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }

    public function getRequired(string $name): mixed
    {
        if (!$this->has($name)) {
            throw InvalidRouteParameterException::missing($name);
        }

        return $this->parameters[$name];
    }

    public function set(string $name, mixed $value): static
    {
        $this->assertName($name);
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function replace(array $parameters): static
    {
        $this->parameters = [];

        return $this->merge($parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function merge(array $parameters): static
    {
        foreach ($parameters as $name => $value) {
            $this->set((string) $name, $value);
        }

        return $this;
    }

    public function remove(string $name): static
    {
        unset($this->parameters[$name]);

        return $this;
    }

    public function clear(): static
    {
        $this->parameters = [];

        return $this;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->parameters);
    }

    public function count(): int
    {
        return count($this->parameters);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->parameters);
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? $this->get($offset) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw InvalidRouteParameterException::forName((string) $offset);
        }

        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            $this->remove($offset);
        }
    }

    private function assertName(string $name): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw InvalidRouteParameterException::forName($name);
        }
    }
}
