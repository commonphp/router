<?php

declare(strict_types=1);

namespace CommonPHP\Router\Exceptions;

use CommonPHP\Router\Enums\RouteMethod;
use Throwable;

class MethodNotAllowedException extends RouterException
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(
        string $message,
        private readonly array $allowedMethods = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param list<RouteMethod|string> $allowedMethods
     */
    public static function forPath(string $method, string $path, array $allowedMethods): self
    {
        $allowed = self::normalizeAllowedMethods($allowedMethods);

        return new self(
            'Method ' . strtoupper($method) . ' is not allowed for route path "' . $path . '". Allowed methods: '
                . implode(', ', $allowed) . '.',
            $allowed,
        );
    }

    /**
     * @return list<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * @param list<RouteMethod|string> $methods
     * @return list<string>
     */
    private static function normalizeAllowedMethods(array $methods): array
    {
        $allowed = [];

        foreach ($methods as $method) {
            $value = $method instanceof RouteMethod ? $method->value() : strtoupper((string) $method);

            if (!in_array($value, $allowed, true)) {
                $allowed[] = $value;
            }
        }

        sort($allowed);

        return $allowed;
    }
}
