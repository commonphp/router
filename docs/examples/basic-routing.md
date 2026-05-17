# Basic Routing

```php
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Router;

$router = new Router();

$router->get('/health', static fn (): Response => new Response('ok'));

$router->get('/users/{id}', static function (Request $request, $match): Response {
    return new Response('User ' . $match->requiredParameter('id'));
}, 'users.show')->whereNumber('id');

$match = $router->match('/users/42');

echo $match->name();            // users.show
echo $match->parameter('id');   // 42

$response = $router->dispatch(new Request('GET', '/users/42'));

echo $response->body();         // User 42
```

Use `find()` for optional matching:

```php
$match = $router->find('/missing');

if ($match === null) {
    // Convert to the response style owned by your application layer.
}
```
