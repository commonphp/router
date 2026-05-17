<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\InvalidRouteException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RouteMethodTest extends TestCase
{
    #[DataProvider('methodProvider')]
    public function testItNormalizesMethods(
        RouteMethod $method,
        string $value,
        bool $safe,
        bool $idempotent,
        bool $usuallyHasBody,
    ): void {
        self::assertSame($method, RouteMethod::fromString(strtolower($value)));
        self::assertSame($method, RouteMethod::tryFromName($value));
        self::assertSame($method, RouteMethod::normalize($method));
        self::assertSame($method, RouteMethod::normalize(RequestMethod::fromString($value)));
        self::assertSame($method, RouteMethod::normalize(' ' . strtolower($value) . ' '));
        self::assertSame($value, $method->value());
        self::assertSame($value, $method->toHttp()->value());
        self::assertSame($safe, $method->isSafe());
        self::assertSame($idempotent, $method->isIdempotent());
        self::assertSame($usuallyHasBody, $method->usuallyHasBody());
    }

    public function testItReturnsNullForUnknownNames(): void
    {
        self::assertNull(RouteMethod::tryFromName('BREW'));
    }

    public function testItRejectsUnknownMethods(): void
    {
        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessage('BREW');

        RouteMethod::fromString('BREW');
    }

    public static function methodProvider(): iterable
    {
        yield 'GET' => [RouteMethod::GET, 'GET', true, true, false];
        yield 'HEAD' => [RouteMethod::HEAD, 'HEAD', true, true, false];
        yield 'POST' => [RouteMethod::POST, 'POST', false, false, true];
        yield 'PUT' => [RouteMethod::PUT, 'PUT', false, true, true];
        yield 'PATCH' => [RouteMethod::PATCH, 'PATCH', false, false, true];
        yield 'DELETE' => [RouteMethod::DELETE, 'DELETE', false, true, false];
        yield 'OPTIONS' => [RouteMethod::OPTIONS, 'OPTIONS', true, true, false];
        yield 'TRACE' => [RouteMethod::TRACE, 'TRACE', true, true, false];
        yield 'CONNECT' => [RouteMethod::CONNECT, 'CONNECT', false, false, false];
    }
}
