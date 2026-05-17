<?php

declare(strict_types=1);

namespace CommonPHP\Router\Contracts;

interface RouteConstraintInterface
{
    public function pattern(): string;

    public function matches(string $value): bool;

    public function description(): string;
}
