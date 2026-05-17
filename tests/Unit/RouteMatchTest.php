<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\InvalidRouteParameterException;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

final class RouteMatchTest extends TestCase
{
    public function testItExposesMatchedRouteContext(): void
    {
        $handler = static fn () => null;
        $route = Route::get('/users/{id}', $handler, 'users.show')->meta('scope', 'users');
        $match = new RouteMatch($route, ['id' => '42'], '/users/42', RouteMethod::GET, 'https');

        self::assertSame($route, $match->route());
        self::assertSame(['id' => '42'], $match->parameters()->all());
        self::assertTrue($match->hasParameter('id'));
        self::assertFalse($match->hasParameter('missing'));
        self::assertSame('42', $match->parameter('id'));
        self::assertSame('fallback', $match->parameter('missing', 'fallback'));
        self::assertSame('42', $match->requiredParameter('id'));
        self::assertSame($handler, $match->handler());
        self::assertSame('users.show', $match->name());
        self::assertSame('/users/42', $match->path());
        self::assertSame(RouteMethod::GET, $match->method());
        self::assertSame('https', $match->scheme());
        self::assertSame(['scope' => 'users'], $match->metadata()->all());
        self::assertSame('route "users.show"', $match->label());
    }

    public function testItLabelsUnnamedRoutesWithMethodAndPath(): void
    {
        $route = Route::post('/users', static fn () => null);
        $match = new RouteMatch($route);

        self::assertSame('POST /users', $match->label());
        self::assertSame('/users', $match->path());
        self::assertNull($match->method());
        self::assertNull($match->scheme());
    }

    public function testParametersAccessorReturnsAClone(): void
    {
        $match = new RouteMatch(Route::get('/users/{id}', static fn () => null), ['id' => '42']);

        $match->parameters()->set('id', '43');

        self::assertSame('42', $match->parameter('id'));
    }

    public function testRequiredParameterRejectsMissingValues(): void
    {
        $this->expectException(InvalidRouteParameterException::class);

        (new RouteMatch(Route::get('/users/{id}', static fn () => null)))->requiredParameter('id');
    }
}
