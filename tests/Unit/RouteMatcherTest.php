<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\HTTP\Request;
use CommonPHP\Router\Exceptions\MethodNotAllowedException;
use CommonPHP\Router\Exceptions\RouteNotFoundException;
use CommonPHP\Router\Exceptions\SchemaNotAllowedException;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteCollection;
use CommonPHP\Router\RouteMatcher;
use PHPUnit\Framework\TestCase;

final class RouteMatcherTest extends TestCase
{
    public function testItMatchesExactRoutesFromStringPaths(): void
    {
        $route = Route::get('/health', static fn () => null, 'health');
        $matcher = new RouteMatcher(new RouteCollection([$route]));

        $match = $matcher->match('/health');

        self::assertSame($route, $match->route());
        self::assertSame('health', $match->name());
        self::assertSame('/health', $match->path());
        self::assertSame('GET', $match->method()?->value());
        self::assertNull($match->scheme());
    }

    public function testItMatchesRequestsAndIgnoresQueryStrings(): void
    {
        $route = Route::get('/users/{id}', static fn () => null, 'users.show');
        $matcher = new RouteMatcher(new RouteCollection([$route]));
        $request = new Request('GET', 'https://example.test/users/42?expand=roles');

        $match = $matcher->match($request);

        self::assertSame('users.show', $match->name());
        self::assertSame('42', $match->parameter('id'));
        self::assertSame('https', $match->scheme());
    }

    public function testItMatchesUrlStringsWithExplicitMethodsAndSchemes(): void
    {
        $route = Route::post('/users/{id}', static fn () => null)->httpsOnly();
        $matcher = new RouteMatcher(new RouteCollection([$route]));

        $match = $matcher->match('https://example.test/users/42?ignore=yes', 'POST');

        self::assertSame('42', $match->parameter('id'));
        self::assertSame('POST', $match->method()?->value());
        self::assertSame('https', $match->scheme());
    }

    public function testItDecodesRouteParametersAndMergesDefaults(): void
    {
        $route = Route::get('/files/{name}', static fn () => null)
            ->default('disk', 'local');
        $matcher = new RouteMatcher(new RouteCollection([$route]));

        $match = $matcher->match('/files/monthly%20close');

        self::assertSame('monthly close', $match->parameter('name'));
        self::assertSame('local', $match->parameter('disk'));
        self::assertSame(['disk' => 'local', 'name' => 'monthly close'], $match->parameters()->all());
    }

    public function testItMatchesInlineConstraints(): void
    {
        $route = Route::get('/users/{id:[0-9]+}', static fn () => null);
        $matcher = new RouteMatcher(new RouteCollection([$route]));

        self::assertSame('42', $matcher->match('/users/42')->parameter('id'));
        self::assertNull($matcher->find('/users/abc'));
    }

    public function testExplicitConstraintsOverrideInlineConstraints(): void
    {
        $route = Route::get('/users/{id:[0-9]+}', static fn () => null)
            ->where('id', '[A-Z]+');
        $matcher = new RouteMatcher(new RouteCollection([$route]));

        self::assertSame('ABC', $matcher->match('/users/ABC')->parameter('id'));
        self::assertNull($matcher->find('/users/42'));
    }

    public function testItMatchesWildcardParametersAndCatchAllRoutes(): void
    {
        $wildcard = Route::get('/files/{path*}', static fn () => null, 'files.show');
        $catchAll = Route::any('*', static fn () => null, 'fallback')->default('fallback', true);
        $matcher = new RouteMatcher(new RouteCollection([$wildcard, $catchAll]));

        self::assertSame('a/b/c.txt', $matcher->match('/files/a/b/c.txt')->parameter('path'));
        self::assertSame('fallback', $matcher->match('/nothing/here')->name());
        self::assertTrue($matcher->match('/nothing/here')->parameter('fallback'));
    }

    public function testConstraintFailuresFallThroughToLaterRoutes(): void
    {
        $numeric = Route::get('/users/{id}', static fn () => null, 'users.numeric')->whereNumber('id');
        $slug = Route::get('/users/{slug}', static fn () => null, 'users.slug')->whereSlug('slug');
        $matcher = new RouteMatcher(new RouteCollection([$numeric, $slug]));

        self::assertSame('users.numeric', $matcher->match('/users/42')->name());
        self::assertSame('users.slug', $matcher->match('/users/monthly-close')->name());
    }

    public function testHeadRequestsMatchGetRoutes(): void
    {
        $route = Route::get('/health', static fn () => null);
        $matcher = new RouteMatcher(new RouteCollection([$route]));

        self::assertSame($route, $matcher->match(new Request('HEAD', '/health'))->route());
    }

    public function testMethodMismatchThrowsMethodNotAllowedWithAllowedMethods(): void
    {
        $matcher = new RouteMatcher(new RouteCollection([
            Route::get('/users', static fn () => null),
            Route::post('/users', static fn () => null),
        ]));

        try {
            $matcher->match('/users', 'DELETE');
            self::fail('Expected a method mismatch.');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(['GET', 'HEAD', 'POST'], $exception->allowedMethods());
        }
    }

    public function testSchemeMismatchThrowsSchemaNotAllowed(): void
    {
        $matcher = new RouteMatcher(new RouteCollection([
            Route::get('/secure', static fn () => null)->httpsOnly(),
        ]));

        try {
            $matcher->match(new Request('GET', 'http://example.test/secure'));
            self::fail('Expected a scheme mismatch.');
        } catch (SchemaNotAllowedException $exception) {
            self::assertSame(['https'], $exception->allowedSchemes());
        }
    }

    public function testMissingRoutesThrowRouteNotFoundAndFindReturnsNull(): void
    {
        $matcher = new RouteMatcher(new RouteCollection([
            Route::get('/health', static fn () => null),
        ]));

        self::assertNull($matcher->find('/missing'));

        $this->expectException(RouteNotFoundException::class);

        $matcher->match('/missing');
    }

    public function testFindReturnsNullForMethodAndSchemeMismatches(): void
    {
        $matcher = new RouteMatcher(new RouteCollection([
            Route::get('/secure', static fn () => null)->httpsOnly(),
        ]));

        self::assertNull($matcher->find('/secure', 'POST'));
        self::assertNull($matcher->find(new Request('GET', 'http://example.test/secure')));
    }

    public function testEmptyStringPathsNormalizeToRoot(): void
    {
        $route = Route::get('/', static fn () => null, 'home');
        $matcher = new RouteMatcher(new RouteCollection([$route]));

        self::assertSame('home', $matcher->match('')->name());
    }
}
