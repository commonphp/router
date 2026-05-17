# Getting Started

Create a `Router`, register routes, then match or dispatch `CommonPHP\HTTP\Request` objects.

```php
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Router;

$router = new Router();

$router->get('/health', static fn (): Response => new Response('ok'));

$router->get('/users/{id}', static function (Request $request, $match): Response {
    return new Response('User ' . $match->parameter('id'));
})->whereNumber('id');

$response = $router->dispatch(new Request('GET', '/users/42'));
```

## Install Dependencies

The package depends on:

- PHP `^8.5`
- `comphp/http`
- `psr/container`

The HTTP package supplies `Request` and `Response`. The PSR container contract is optional at runtime unless you dispatch container-resolved handlers.

## Define Routes

Routes can be registered directly on `Router`:

```php
$router->get('/users/{id}', $handler, 'users.show');
$router->post('/users', $handler, 'users.store');
$router->route('GET|POST', '/search', $handler, 'search');
```

Route names are optional. They are useful for lookup, diagnostics, and higher-level packages that generate URLs.

## Match Without Dispatching

Use `match()` when another package will execute the handler:

```php
$match = $router->match(new Request('GET', '/users/42'));

$route = $match->route();
$id = $match->requiredParameter('id');
```

Use `find()` when a missing route, method mismatch, or scheme mismatch should return `null` instead of throwing:

```php
$match = $router->find('/missing');
```

## Dispatch To A Response

`dispatch()` resolves and calls the route handler. A handler must return `CommonPHP\HTTP\Response`.

```php
$response = $router->dispatch($request);
```

Supported handlers include callables, `RouteHandlerInterface` objects, invokable objects, controller arrays, `Class@method` strings, `Class::method` strings, class names, and service IDs resolved from a PSR container.
