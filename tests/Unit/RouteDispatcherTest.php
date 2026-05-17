<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Exceptions\RouteDispatchException;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteDispatcher;
use CommonPHP\Router\RouteMatch;
use CommonPHP\Router\Tests\Fixtures\ControllerHandler;
use CommonPHP\Router\Tests\Fixtures\InvokableHandler;
use CommonPHP\Router\Tests\Fixtures\RecordingHandler;
use CommonPHP\Router\Tests\Fixtures\TestContainer;
use PHPUnit\Framework\TestCase;

final class RouteDispatcherTest extends TestCase
{
    public function testItDispatchesCallableHandlers(): void
    {
        $dispatcher = new RouteDispatcher();
        $match = new RouteMatch(Route::get('/users/{id}', static fn (Request $request, RouteMatch $match): Response => new Response(
            $request->path() . ':' . $match->parameter('id'),
        )), ['id' => '42']);

        $response = $dispatcher->dispatch($match, new Request('GET', '/users/42'));

        self::assertSame('/users/42:42', $response->body());
    }

    public function testItDispatchesRouteHandlerObjects(): void
    {
        $handler = new RecordingHandler('handled');
        $match = new RouteMatch(Route::get('/handled', $handler));

        $response = (new RouteDispatcher())->dispatch($match, new Request('GET', '/handled'));

        self::assertSame('handled:/handled', $response->body());
        self::assertSame('/handled', $handler->request?->path());
        self::assertSame($match, $handler->match);
    }

    public function testItDispatchesInvokableObjects(): void
    {
        $match = new RouteMatch(Route::get('/invoke', new InvokableHandler()));

        $response = (new RouteDispatcher())->dispatch($match, new Request('GET', '/invoke'));

        self::assertSame('invokable:/invoke:/invoke', $response->body());
    }

    public function testItDispatchesObjectMethodArrays(): void
    {
        $match = new RouteMatch(Route::get('/users/{id}', [new ControllerHandler(), 'show']), ['id' => '42']);

        $response = (new RouteDispatcher())->dispatch($match, new Request('GET', '/users/42'));

        self::assertSame('controller:GET:42', $response->body());
    }

    public function testItInstantiatesClassArrayHandlers(): void
    {
        $match = new RouteMatch(Route::get('/users/{id}', [ControllerHandler::class, 'show']), ['id' => '42']);

        $response = (new RouteDispatcher())->dispatch($match, new Request('GET', '/users/42'));

        self::assertSame('controller:GET:42', $response->body());
    }

    public function testItDispatchesAtSyntaxHandlers(): void
    {
        $match = new RouteMatch(Route::get('/users/{id}', ControllerHandler::class . '@show'), ['id' => '42']);

        $response = (new RouteDispatcher())->dispatch($match, new Request('GET', '/users/42'));

        self::assertSame('controller:GET:42', $response->body());
    }

    public function testItDispatchesDoubleColonSyntaxHandlers(): void
    {
        $match = new RouteMatch(Route::get('/users/{id}', ControllerHandler::class . '::show'), ['id' => '42']);

        $response = (new RouteDispatcher())->dispatch($match, new Request('GET', '/users/42'));

        self::assertSame('controller:GET:42', $response->body());
    }

    public function testItResolvesServiceIdsFromAContainer(): void
    {
        $container = (new TestContainer())->set('handler.service', new RecordingHandler('service'));
        $match = new RouteMatch(Route::get('/service', 'handler.service'));

        $response = (new RouteDispatcher($container))->dispatch($match, new Request('GET', '/service'));

        self::assertSame('service:/service', $response->body());
    }

    public function testItResolvesClassNamesFromAContainer(): void
    {
        $handler = new RecordingHandler('container-class');
        $container = (new TestContainer())->set(RecordingHandler::class, $handler);
        $match = new RouteMatch(Route::get('/class', RecordingHandler::class));

        $response = (new RouteDispatcher($container))->dispatch($match, new Request('GET', '/class'));

        self::assertSame('container-class:/class', $response->body());
        self::assertSame('/class', $handler->request?->path());
    }

    public function testItRejectsInvalidHandlers(): void
    {
        $this->expectException(RouteDispatchException::class);

        (new RouteDispatcher())->dispatch(
            new RouteMatch(Route::get('/bad', 123)),
            new Request('GET', '/bad'),
        );
    }

    public function testItRejectsInvalidResponses(): void
    {
        $this->expectException(RouteDispatchException::class);

        (new RouteDispatcher())->dispatch(
            new RouteMatch(Route::get('/bad', [new ControllerHandler(), 'returnsInvalid'])),
            new Request('GET', '/bad'),
        );
    }

    public function testItWrapsHandlerFailures(): void
    {
        try {
            (new RouteDispatcher())->dispatch(
                new RouteMatch(Route::get('/bad', [new ControllerHandler(), 'throws'])),
                new Request('GET', '/bad'),
            );

            self::fail('Expected dispatch failure.');
        } catch (RouteDispatchException $exception) {
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
            self::assertStringContainsString('controller failed', $exception->getMessage());
        }
    }

    public function testItReportsMissingHandlerClasses(): void
    {
        $this->expectException(RouteDispatchException::class);
        $this->expectExceptionMessage('was not found');

        (new RouteDispatcher())->dispatch(
            new RouteMatch(Route::get('/missing', 'Missing\\Handler@show')),
            new Request('GET', '/missing'),
        );
    }
}
