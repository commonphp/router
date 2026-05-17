<?php

declare(strict_types=1);

namespace CommonPHP\Router\Exceptions;

use Throwable;

class SchemaNotAllowedException extends RouterException
{
    /**
     * @param list<string> $allowedSchemes
     */
    public function __construct(
        string $message,
        private readonly array $allowedSchemes = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param list<string> $allowedSchemes
     */
    public static function forPath(string $scheme, string $path, array $allowedSchemes): self
    {
        $allowed = array_values(array_unique(array_map('strtolower', $allowedSchemes)));
        sort($allowed);

        return new self(
            'Scheme ' . strtolower($scheme) . ' is not allowed for route path "' . $path . '". Allowed schemes: '
                . implode(', ', $allowed) . '.',
            $allowed,
        );
    }

    /**
     * @return list<string>
     */
    public function allowedSchemes(): array
    {
        return $this->allowedSchemes;
    }
}
