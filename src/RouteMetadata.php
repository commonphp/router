<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class RouteMetadata implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    /**
     * @param array<string, mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->replace($items);
    }

    /**
     * @param array<string, mixed>|self $metadata
     */
    public static function from(array|self $metadata): self
    {
        return $metadata instanceof self ? clone $metadata : new self($metadata);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->assertKey($key);
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $items
     */
    public function replace(array $items): static
    {
        $this->items = [];

        return $this->merge($items);
    }

    /**
     * @param array<string, mixed> $items
     */
    public function merge(array $items): static
    {
        foreach ($items as $key => $value) {
            $this->set((string) $key, $value);
        }

        return $this;
    }

    public function remove(string $key): static
    {
        unset($this->items[$key]);

        return $this;
    }

    public function clear(): static
    {
        $this->items = [];

        return $this;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
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
            throw new InvalidArgumentException('Route metadata keys must be strings.');
        }

        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            $this->remove($offset);
        }
    }

    private function assertKey(string $key): void
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Route metadata key cannot be empty.');
        }
    }
}
