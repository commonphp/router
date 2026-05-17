<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Unit;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\Router\Contracts\RouteConstraintInterface;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\InvalidRouteException;
use CommonPHP\Router\Exceptions\InvalidRouteParameterException;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteConstraint;
use CommonPHP\Router\RouteMetadata;
use CommonPHP\Router\RouteParameters;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testConstructorNormalizesCoreDefinition(): void
    {
        $handler = static fn () => null;
        $route = new Route('get|post,GET', 'users/{id}', $handler, 'users.show');

        self::assertSame([RouteMethod::GET, RouteMethod::POST], $route->methods());
        self::assertSame(['GET', 'POST'], $route->methodValues());
        self::assertSame(['GET', 'HEAD', 'POST'], $route->allowedMethodValues());
        self::assertSame('/users/{id}', $route->path());
        self::assertSame($handler, $route->handler());
        self::assertSame('users.show', $route->name());
        self::assertSame('GET|POST /users/{id}', $route->signature());
    }

    #[DataProvider('factoryProvider')]
    public function testNamedFactoriesCreateExpectedMethods(string $factory, array $expectedMethods): void
    {
        $route = Route::$factory('/path', static fn () => null, 'name');

        self::assertSame($expectedMethods, $route->methodValues());
        self::assertSame('/path', $route->path());
        self::assertSame('name', $route->name());
    }

    public function testMatchFactoryAcceptsRequestMethodEnums(): void
    {
        $route = Route::match([RequestMethod::PUT, RouteMethod::PATCH, 'delete'], '/items', static fn () => null);

        self::assertSame(['PUT', 'PATCH', 'DELETE'], $route->methodValues());
    }

    public function testHeadRequestsAreAllowedForGetRoutes(): void
    {
        $route = Route::get('/health', static fn () => null);

        self::assertTrue($route->allowsMethod(RouteMethod::GET));
        self::assertTrue($route->allowsMethod(RouteMethod::HEAD));
        self::assertFalse($route->allowsMethod(RouteMethod::POST));
    }

    public function testHandlerCanBeReplaced(): void
    {
        $first = static fn () => null;
        $second = static fn () => null;
        $route = Route::get('/health', $first);

        self::assertSame($route, $route->setHandler($second));
        self::assertSame($second, $route->handler());
    }

    public function testItStoresConstraintsWithConvenienceFactories(): void
    {
        $route = Route::get('/users/{id}/{slug}/{uuid}/{kind}/{code}', static fn () => null)
            ->whereNumber('id')
            ->whereSlug('slug')
            ->whereUuid('uuid')
            ->whereIn('kind', ['admin', 'editor'])
            ->where('code', '[A-Z]+');

        self::assertContainsOnlyInstancesOf(RouteConstraintInterface::class, $route->constraints());
        self::assertTrue($route->constraint('id')?->matches('123'));
        self::assertTrue($route->constraint('slug')?->matches('monthly-close'));
        self::assertTrue($route->constraint('uuid')?->matches('123e4567-e89b-12d3-a456-426614174000'));
        self::assertTrue($route->constraint('kind')?->matches('admin'));
        self::assertTrue($route->constraint('code')?->matches('ABC'));
    }

    public function testItAcceptsConstraintObjectsAndCallbacks(): void
    {
        $object = RouteConstraint::alpha();
        $route = Route::get('/users/{name}/{id}', static fn () => null)
            ->where('name', $object)
            ->where('id', static fn (string $value): bool => $value === '42');

        self::assertSame($object, $route->constraint('name'));
        self::assertTrue($route->constraint('id')?->matches('42'));
        self::assertFalse($route->constraint('id')?->matches('43'));
    }

    public function testItStoresDefaultsMetadataSchemesAndMiddleware(): void
    {
        $middleware = new \stdClass();
        $route = new Route(
            RouteMethod::GET,
            '/reports/{page}',
            static fn () => null,
            metadata: ['scope' => 'admin'],
            defaults: ['page' => 1],
            schemes: ['HTTPS', 'https'],
            middleware: [$middleware],
        );

        self::assertSame(['page' => 1], $route->defaults()->all());
        self::assertSame(['scope' => 'admin'], $route->metadata()->all());
        self::assertSame(['https'], $route->schemes());
        self::assertTrue($route->allowsScheme('https'));
        self::assertFalse($route->allowsScheme('http'));
        self::assertSame([$middleware], $route->middleware());
    }

    public function testDefaultsAndMetadataAccessorsReturnClones(): void
    {
        $defaults = new RouteParameters(['page' => 1]);
        $metadata = new RouteMetadata(['scope' => 'admin']);
        $route = Route::get('/reports/{page}', static fn () => null)
            ->withDefaults($defaults)
            ->withMetadata($metadata);

        $route->defaults()->set('page', 2);
        $route->metadata()->set('scope', 'public');

        self::assertSame(['page' => 1], $route->defaults()->all());
        self::assertSame(['scope' => 'admin'], $route->metadata()->all());

        self::assertSame($route, $route->default('page', 3));
        self::assertSame($route, $route->meta('scope', 'reports'));
        self::assertSame(['page' => 3], $route->defaults()->all());
        self::assertSame(['scope' => 'reports'], $route->metadata()->all());
    }

    public function testSchemeConvenienceMethodsReplaceAllowedSchemes(): void
    {
        $route = Route::get('/secure', static fn () => null);

        self::assertSame($route, $route->httpsOnly());
        self::assertSame(['https'], $route->schemes());

        self::assertSame($route, $route->httpOnly());
        self::assertSame(['http'], $route->schemes());

        self::assertSame($route, $route->allowSchemes());
        self::assertSame([], $route->schemes());
        self::assertTrue($route->allowsScheme('ftp'));
    }

    public function testThroughAppendsMiddleware(): void
    {
        $first = new \stdClass();
        $second = new \stdClass();
        $route = Route::get('/path', static fn () => null);

        self::assertSame($route, $route->through($first, $second));
        self::assertSame([$first, $second], $route->middleware());
    }

    public function testWildcardPathIsAccepted(): void
    {
        $route = Route::any('*', static fn () => null);

        self::assertSame('*', $route->path());
        self::assertCount(9, $route->methods());
    }

    #[DataProvider('invalidRouteProvider')]
    public function testItRejectsInvalidDefinitions(callable $factory, string $exceptionClass): void
    {
        $this->expectException($exceptionClass);

        $factory();
    }

    public static function factoryProvider(): iterable
    {
        yield 'any' => ['any', ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'CONNECT']];
        yield 'get' => ['get', ['GET']];
        yield 'post' => ['post', ['POST']];
        yield 'put' => ['put', ['PUT']];
        yield 'patch' => ['patch', ['PATCH']];
        yield 'delete' => ['delete', ['DELETE']];
        yield 'options' => ['options', ['OPTIONS']];
    }

    public static function invalidRouteProvider(): iterable
    {
        yield 'empty methods' => [static fn () => new Route([], '/path', static fn () => null), InvalidRouteException::class];
        yield 'invalid method' => [static fn () => new Route('BREW', '/path', static fn () => null), InvalidRouteException::class];
        yield 'empty path' => [static fn () => new Route('GET', '', static fn () => null), InvalidRouteException::class];
        yield 'invalid name' => [static fn () => new Route('GET', '/path', static fn () => null, 'bad name'), InvalidRouteException::class];
        yield 'unbalanced braces' => [static fn () => new Route('GET', '/users/{id', static fn () => null), InvalidRouteException::class];
        yield 'invalid parameter name' => [static fn () => new Route('GET', '/users/{bad-name}', static fn () => null), InvalidRouteParameterException::class];
        yield 'duplicate parameter' => [static fn () => new Route('GET', '/users/{id}/{id}', static fn () => null), InvalidRouteParameterException::class];
        yield 'invalid where parameter' => [static fn () => Route::get('/users/{id}', static fn () => null)->where('bad-name', '[0-9]+'), InvalidRouteParameterException::class];
        yield 'invalid where constraint' => [static fn () => Route::get('/users/{id}', static fn () => null)->where('id', 123), InvalidRouteException::class];
        yield 'invalid scheme' => [static fn () => Route::get('/path', static fn () => null)->allowSchemes('ftp'), InvalidRouteException::class];
    }
}
