<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\HTTP\Request;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\DuplicateRouteException;
use CommonPHP\Router\Exceptions\InvalidRouteException;
use CommonPHP\Router\Exceptions\InvalidRouteParameterException;
use CommonPHP\Router\Exceptions\MethodNotAllowedException;
use CommonPHP\Router\Exceptions\RouteDispatchException;
use CommonPHP\Router\Exceptions\RouteNotFoundException;
use CommonPHP\Router\Exceptions\RouterException;
use CommonPHP\Router\Exceptions\SchemaNotAllowedException;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testRouterExceptionExtendsRuntimeException(): void
    {
        self::assertInstanceOf(\RuntimeException::class, new RouterException('failed'));
    }

    public function testDuplicateRouteFactoriesIncludeContext(): void
    {
        self::assertStringContainsString('GET /health', DuplicateRouteException::forRoute(Route::get('/health', static fn () => null))->getMessage());
        self::assertStringContainsString('health', DuplicateRouteException::forName('health')->getMessage());
    }

    public function testInvalidRouteFactoriesIncludeContext(): void
    {
        self::assertStringContainsString('because', InvalidRouteException::because('because')->getMessage());
        self::assertStringContainsString('BREW', InvalidRouteException::invalidMethod('BREW')->getMessage());
        self::assertStringContainsString('path', InvalidRouteException::invalidPath('path')->getMessage());
        self::assertStringContainsString('name', InvalidRouteException::invalidName('name')->getMessage());
        self::assertStringContainsString('array', InvalidRouteException::invalidHandler('array')->getMessage());
    }

    public function testInvalidRouteParameterFactoriesIncludeContext(): void
    {
        self::assertStringContainsString('id', InvalidRouteParameterException::forName('id')->getMessage());
        self::assertStringContainsString('id', InvalidRouteParameterException::duplicate('id')->getMessage());
        self::assertStringContainsString('id', InvalidRouteParameterException::missing('id')->getMessage());
        self::assertStringContainsString('abc', InvalidRouteParameterException::failed('id', 'abc')->getMessage());
    }

    public function testMethodNotAllowedStoresAllowedMethods(): void
    {
        $exception = MethodNotAllowedException::forPath('post', '/users', [
            RouteMethod::GET,
            'POST',
            'GET',
        ]);

        self::assertSame(['GET', 'POST'], $exception->allowedMethods());
        self::assertStringContainsString('/users', $exception->getMessage());
    }

    public function testRouteNotFoundFactoriesIncludeContext(): void
    {
        self::assertStringContainsString('GET', RouteNotFoundException::forPath('/missing', 'GET')->getMessage());
        self::assertStringContainsString('/missing', RouteNotFoundException::forRequest(new Request('GET', '/missing'))->getMessage());
        self::assertStringContainsString('missing', RouteNotFoundException::forName('missing')->getMessage());
    }

    public function testSchemaNotAllowedStoresAllowedSchemes(): void
    {
        $exception = SchemaNotAllowedException::forPath('http', '/secure', ['https', 'HTTPS']);

        self::assertSame(['https'], $exception->allowedSchemes());
        self::assertStringContainsString('/secure', $exception->getMessage());
    }

    public function testRouteDispatchFactoriesIncludeContextAndPreviousException(): void
    {
        $match = new RouteMatch(Route::get('/health', static fn () => null, 'health'));
        $previous = new \RuntimeException('boom');

        self::assertStringContainsString('health', RouteDispatchException::invalidHandler($match, 'integer')->getMessage());
        self::assertStringContainsString('Response', RouteDispatchException::invalidResponse($match, 'string')->getMessage());

        $failed = RouteDispatchException::failed($match, $previous);

        self::assertSame($previous, $failed->getPrevious());
        self::assertStringContainsString('boom', $failed->getMessage());
    }
}
