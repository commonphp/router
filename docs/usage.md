# Usage

Router has three common usage styles: direct route registration, route collection composition, and dispatch through a handler or container.

## Direct Router Use

```php
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Router;

$router = new Router();

$router->get('/articles/{slug}', static function (Request $request, $match): Response {
    return new Response('Article: ' . $match->requiredParameter('slug'));
}, 'articles.show')->whereSlug('slug');

$match = $router->match('/articles/router-guide');
$response = $router->dispatch(new Request('GET', '/articles/router-guide'));
```

## Route Collections

Use `RouteCollection` when routes are assembled before a `Router` is created.

```php
use CommonPHP\Router\RouteCollection;
use CommonPHP\Router\Router;

$routes = new RouteCollection();
$routes->get('/health', $healthHandler, 'health');
$routes->post('/sessions', $sessionHandler, 'sessions.store');

$router = new Router($routes);
```

## Route Groups

Groups apply shared path prefixes, name prefixes, constraints, defaults, metadata, schemes, and middleware references.

```php
$router->group('/api', function ($group): void {
    $group->get('/users/{id}', $showUser, 'users.show');
    $group->post('/users', $createUser, 'users.store');
}, 'api.', ['id' => '[0-9]+'], metadata: ['surface' => 'api']);
```

The example registers `api.users.show` at `/api/users/{id}`.

## Matching

`match()` throws on failure:

```php
$match = $router->match(new Request('GET', '/users/42'));
```

`find()` returns `null` for not found, method-not-allowed, or scheme-not-allowed outcomes:

```php
$match = $router->find('/users/42', 'GET');
```

## Dispatching

Handlers receive the `Request` and `RouteMatch`.

```php
$router->get('/users/{id}', function (Request $request, $match): Response {
    return new Response($match->requiredParameter('id'));
});
```

If a handler returns anything other than `Response`, `RouteDispatchException` is thrown.
