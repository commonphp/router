# Container Dispatch

`RouteDispatcher` can resolve handlers from a PSR container.

```php
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Contracts\RouteHandlerInterface;
use CommonPHP\Router\RouteMatch;
use CommonPHP\Router\Router;

final class ShowUser implements RouteHandlerInterface
{
    public function handle(Request $request, RouteMatch $match): Response
    {
        return new Response('User ' . $match->requiredParameter('id'));
    }
}

$container = build_container_somewhere_else();

$router = new Router(container: $container);

$router->get('/users/{id}', ShowUser::class, 'users.show')
    ->whereNumber('id');

$response = $router->dispatch(new Request('GET', '/users/42'));
```

Controller method strings are supported too:

```php
$router->get('/users/{id}', UserController::class . '@show');
$router->get('/users/{id}', [UserController::class, 'show']);
```

If the container has the class or service ID, the container value is used. Otherwise, dispatcher can instantiate an existing no-argument class directly.
