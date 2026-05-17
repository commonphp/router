# Grouped Routes

```php
use CommonPHP\HTTP\Response;
use CommonPHP\Router\RouteCollection;
use CommonPHP\Router\Router;

$routes = new RouteCollection();

$routes->group(
    '/api',
    function ($group): void {
        $group->get('/users/{id}', static function ($request, $match): Response {
            return new Response('User ' . $match->requiredParameter('id'));
        }, 'users.show');

        $group->post('/users', static fn (): Response => new Response('created', 201), 'users.store');
    },
    'api.',
    ['id' => '[0-9]+'],
    metadata: ['surface' => 'api'],
    schemes: ['https'],
);

$router = new Router($routes);

$route = $router->named('api.users.show');

echo $route->path(); // /api/users/{id}
```

Shared metadata and middleware references are stored on each route for the surrounding web or API package to interpret.
