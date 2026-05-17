<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Fixtures;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class TestContainer implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $entries = [];

    public function set(string $id, mixed $value): self
    {
        $this->entries[$id] = $value;

        return $this;
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new class('Container entry "' . $id . '" was not found.') extends \RuntimeException implements NotFoundExceptionInterface {
            };
        }

        $entry = $this->entries[$id];

        try {
            return is_callable($entry) && !is_object($entry) ? $entry($this) : $entry;
        } catch (\Throwable $exception) {
            throw new class('Container entry "' . $id . '" failed.', 0, $exception) extends \RuntimeException implements ContainerExceptionInterface {
            };
        }
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }
}
