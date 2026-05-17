# Routes

`Route` describes one route definition: methods, path, handler, optional name, constraints, defaults, metadata, schemes, and middleware references.

## Create Routes

```php
use CommonPHP\Router\Route;

$route = Route::get('/users/{id}', $handler, 'users.show');

$route = Route::match(['GET', 'POST'], '/search', $handler, 'search');

$route = Route::any('/webhook', $handler, 'webhook');
```

Convenience factories exist for:

- `any()`
- `get()`
- `post()`
- `put()`
- `patch()`
- `delete()`
- `options()`

## Methods

Methods can be `RouteMethod`, `CommonPHP\HTTP\Enums\RequestMethod`, strings, or arrays of those values.

```php
$route = Route::match('GET|POST', '/search', $handler);
```

Method values are normalized to uppercase router methods. Duplicate methods in a definition are removed.

`GET` routes also allow `HEAD` requests. `allowedMethodValues()` includes that implicit `HEAD` fallback for diagnostics.

## Paths

Paths are normalized with a leading slash:

```php
Route::get('users/{id}', $handler)->path(); // /users/{id}
```

The special `*` path matches any path and is useful as a final fallback route.

## Names

Route names are optional:

```php
$route->named('users.show');
```

Names may contain letters, numbers, underscores, dots, colons, and hyphens. A `RouteCollection` rejects duplicate names.

## Handlers

Route accepts `mixed` as the handler because dispatch supports several handler styles. During dispatch the handler must resolve to a callable or `RouteHandlerInterface` object and return `CommonPHP\HTTP\Response`.

## Schemes

Routes match any request scheme by default.

```php
$route->httpsOnly();
$route->httpOnly();
$route->allowSchemes('http', 'https');
```

Only `http` and `https` are accepted as explicit route schemes.

## Middleware References

`through()` stores middleware references on the route:

```php
$route->through('auth', 'csrf');
```

Router stores these values for higher-level packages. `RouteDispatcher` does not execute middleware.
