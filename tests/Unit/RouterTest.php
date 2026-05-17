<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Contracts\RouterInterface;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteCollection;
use CommonPHP\Router\Router;
use CommonPHP\Router\Tests\Fixtures\RecordingHandler;
use CommonPHP\Router\Tests\Fixtures\StubDispatcher;
use CommonPHP\Router\Tests\Fixtures\TestContainer;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testItCreatesDefaultCollaborators(): void
    {
        $router = new Router();

        self::assertInstanceOf(RouterInterface::class, $router);
        self::assertInstanceOf(RouteCollection::class, $router->routes());
        self::assertTrue($router->routes()->isEmpty());
    }

    public function testItUsesProvidedRouteCollections(): void
    {
        $collection = new RouteCollection();
        $router = new Router($collection);

        self::assertSame($collection, $router->routes());
    }

    public function testItAddsRoutesAndDelegatesHelpers(): void
    {
        $router = new Router();
        $route = Route::get('/manual', static fn () => new Response('manual'), 'manual');

        self::assertSame($router, $router->add($route));
        self::assertSame($route, $router->named('manual'));
        self::assertSame(['POST'], $router->post('/posts', static fn () => new Response('post'), 'posts.store')->methodValues());
        self::assertSame(['PUT'], $router->put('/posts/{id}', static fn () => new Response('put'), 'posts.update')->methodValues());
        self::assertSame(['PATCH'], $router->patch('/posts/{id}', static fn () => new Response('patch'), 'posts.patch')->methodValues());
        self::assertSame(['DELETE'], $router->delete('/posts/{id}', static fn () => new Response('delete'), 'posts.delete')->methodValues());
        self::assertSame(['OPTIONS'], $router->options('/posts', static fn () => new Response('options'), 'posts.options')->methodValues());
        self::assertSame(6, count($router->routes()));
    }

    public function testItRegistersGenericAndAnyRoutes(): void
    {
        $router = new Router();

        self::assertSame(['GET', 'POST'], $router->route('GET|POST', '/multi', static fn () => new Response(), 'multi')->methodValues());
        self::assertCount(9, $router->any('/any', static fn () => new Response(), 'any')->methods());
    }

    public function testGroupsRegisterAgainstRouterCollection(): void
    {
        $router = new Router();

        $router->group('/api', function ($group): void {
            $group->get('/health', static fn () => new Response('ok'), 'health');
        }, 'api.');

        self::assertSame('/api/health', $router->named('api.health')->path());
    }

    public function testItMatchesAndFindsRoutes(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static fn () => new Response(), 'users.show')->whereNumber('id');

        self::assertSame('42', $router->match('/users/42')->parameter('id'));
        self::assertSame('users.show', $router->find(new Request('GET', '/users/42'))?->name());
        self::assertNull($router->find('/users/not-number'));
    }

    public function testItDispatchesAndHandlesRequests(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static fn (Request $request, $match): Response => new Response(
            $request->path() . ':' . $match->parameter('id'),
        ));

        self::assertSame('/users/42:42', $router->dispatch(new Request('GET', '/users/42'))->body());
        self::assertSame('/users/42:42', $router->handle(new Request('GET', '/users/42'))->body());
    }

    public function testItUsesCustomDispatchers(): void
    {
        $dispatcher = new StubDispatcher(new Response('custom'));
        $router = new Router(dispatcher: $dispatcher);
        $router->get('/custom', static fn () => new Response('ignored'));

        $response = $router->dispatch(new Request('GET', '/custom'));

        self::assertSame('custom', $response->body());
        self::assertSame('/custom', $dispatcher->request?->path());
        self::assertSame(RouteMethod::GET, $dispatcher->match?->method());
    }

    public function testItPassesContainersToTheDefaultDispatcher(): void
    {
        $handler = new RecordingHandler('container');
        $container = (new TestContainer())->set('handler', $handler);
        $router = new Router(container: $container);
        $router->get('/container', 'handler');

        $response = $router->dispatch(new Request('GET', '/container'));

        self::assertSame('container:/container', $response->body());
        self::assertSame('/container', $handler->request?->path());
    }
}
