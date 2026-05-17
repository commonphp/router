# Dispatching

`RouteDispatcher` resolves a matched route handler and calls it with the request and match.

```php
use CommonPHP\Router\RouteDispatcher;

$dispatcher = new RouteDispatcher($container);
$response = $dispatcher->dispatch($match, $request);
```

`Router::dispatch()` combines matching and dispatching:

```php
$response = $router->dispatch($request);
```

## Handler Signature

Callable handlers receive:

```php
function (Request $request, RouteMatch $match): Response
```

The return value must be `CommonPHP\HTTP\Response`.

## Supported Handler Styles

Callable:

```php
$router->get('/health', static fn (): Response => new Response('ok'));
```

Route handler object:

```php
use CommonPHP\Router\Contracts\RouteHandlerInterface;

final class ShowUser implements RouteHandlerInterface
{
    public function handle(Request $request, RouteMatch $match): Response
    {
        return new Response($match->requiredParameter('id'));
    }
}
```

Controller array:

```php
$router->get('/users/{id}', [UserController::class, 'show']);
```

String syntax:

```php
$router->get('/users/{id}', UserController::class . '@show');
$router->get('/users/{id}', UserController::class . '::show');
```

Container service ID:

```php
$router->get('/users/{id}', 'handlers.users.show');
```

## Container Resolution

When a PSR container is supplied, dispatcher checks the container for:

- service IDs;
- class names.

If a class name is not in the container but exists and can be constructed without arguments, dispatcher creates it directly.

## Dispatch Errors

`RouteDispatchException` is thrown when:

- a handler cannot be resolved or called;
- a handler returns something other than `Response`;
- a handler throws an exception.

Original handler failures are available through `getPrevious()`.
