<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\DuplicateRouteException;
use CommonPHP\Router\Exceptions\RouteNotFoundException;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteCollection;
use PHPUnit\Framework\TestCase;

final class RouteCollectionTest extends TestCase
{
    public function testItStartsEmptyAndAddsRoutes(): void
    {
        $route = Route::get('/health', static fn () => null, 'health');
        $collection = new RouteCollection();

        self::assertTrue($collection->isEmpty());
        self::assertSame(0, count($collection));
        self::assertSame($collection, $collection->add($route));
        self::assertFalse($collection->isEmpty());
        self::assertSame(1, count($collection));
        self::assertSame([$route], $collection->all());
        self::assertSame([$route], iterator_to_array($collection));
        self::assertSame(['health'], $collection->names());
    }

    public function testConstructorAcceptsIterableRoutes(): void
    {
        $first = Route::get('/first', static fn () => null);
        $second = Route::post('/second', static fn () => null);

        $collection = new RouteCollection([$first, $second]);

        self::assertSame([$first, $second], $collection->all());
    }

    public function testHelperMethodsRegisterRoutes(): void
    {
        $collection = new RouteCollection();

        self::assertSame(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'CONNECT'], $collection->any('/any', static fn () => null)->methodValues());
        self::assertSame(['GET'], $collection->get('/get', static fn () => null)->methodValues());
        self::assertSame(['POST'], $collection->post('/post', static fn () => null)->methodValues());
        self::assertSame(['PUT'], $collection->put('/put', static fn () => null)->methodValues());
        self::assertSame(['PATCH'], $collection->patch('/patch', static fn () => null)->methodValues());
        self::assertSame(['DELETE'], $collection->delete('/delete', static fn () => null)->methodValues());
        self::assertSame(['OPTIONS'], $collection->options('/options', static fn () => null)->methodValues());
        self::assertSame(7, count($collection));
    }

    public function testItLooksUpNamedRoutes(): void
    {
        $route = Route::get('/health', static fn () => null, 'health');
        $collection = new RouteCollection([$route]);

        self::assertTrue($collection->hasNamed('health'));
        self::assertFalse($collection->hasNamed('missing'));
        self::assertSame($route, $collection->findByName('health'));
        self::assertSame($route, $collection->named('health'));
    }

    public function testNamedLookupRejectsMissingRoutes(): void
    {
        $this->expectException(RouteNotFoundException::class);

        (new RouteCollection())->named('missing');
    }

    public function testItRejectsDuplicateNames(): void
    {
        $this->expectException(DuplicateRouteException::class);

        new RouteCollection([
            Route::get('/first', static fn () => null, 'same'),
            Route::post('/second', static fn () => null, 'same'),
        ]);
    }

    public function testItRejectsDuplicateMethodAndPathPairs(): void
    {
        $this->expectException(DuplicateRouteException::class);

        new RouteCollection([
            Route::get('/same', static fn () => null),
            Route::get('/same', static fn () => null),
        ]);
    }

    public function testItAllowsDifferentMethodsForTheSamePath(): void
    {
        $collection = new RouteCollection([
            Route::get('/same', static fn () => null),
            Route::post('/same', static fn () => null),
        ]);

        self::assertSame(2, count($collection));
    }

    public function testItRemovesRoutesByObjectOrNameAndClears(): void
    {
        $first = Route::get('/first', static fn () => null, 'first');
        $second = Route::post('/second', static fn () => null, 'second');
        $collection = new RouteCollection([$first, $second]);

        self::assertSame($collection, $collection->remove($first));
        self::assertSame([$second], $collection->all());

        self::assertSame($collection, $collection->remove('second'));
        self::assertTrue($collection->isEmpty());

        $collection->add($first)->clear();

        self::assertTrue($collection->isEmpty());
    }

    public function testGroupsApplyPrefixesNamePrefixesAndSharedOptions(): void
    {
        $middleware = new \stdClass();
        $collection = new RouteCollection();

        $group = $collection->group(
            '/api',
            function ($group): void {
                $group->get('/users/{id}', static fn () => null, 'users.show');
            },
            'api.',
            ['id' => '[0-9]+'],
            ['page' => 1],
            ['scope' => 'api'],
            ['https'],
            [$middleware],
        );

        $route = $collection->named('api.users.show');

        self::assertSame($collection, $group->collection());
        self::assertSame('/api', $group->prefix());
        self::assertSame('api.', $group->namePrefix());
        self::assertSame('/api/users/{id}', $route->path());
        self::assertTrue($route->constraint('id')?->matches('42'));
        self::assertSame(['page' => 1], $route->defaults()->all());
        self::assertSame(['scope' => 'api'], $route->metadata()->all());
        self::assertSame(['https'], $route->schemes());
        self::assertSame([$middleware], $route->middleware());
    }

    public function testGroupRouteOptionsCanOverrideSharedOptions(): void
    {
        $collection = new RouteCollection();
        $group = $collection->group('/api', namePrefix: 'api.', constraints: ['id' => '[0-9]+'], schemes: ['https']);

        $route = $group->route(
            RouteMethod::GET,
            '/users/{id}',
            static fn () => null,
            'users.show',
            ['id' => '[A-Z]+'],
            schemes: ['http'],
        );

        self::assertTrue($route->constraint('id')?->matches('ABC'));
        self::assertFalse($route->constraint('id')?->matches('42'));
        self::assertSame(['http'], $route->schemes());
    }

    public function testNestedGroupsComposePrefixesAndNames(): void
    {
        $collection = new RouteCollection();

        $collection->group('/api', function ($group): void {
            $group->group('/v1', function ($group): void {
                $group->get('users', static fn () => null, 'users.index');
            }, 'v1.');
        }, 'api.');

        $route = $collection->named('api.v1.users.index');

        self::assertSame('/api/v1/users', $route->path());
    }
}
