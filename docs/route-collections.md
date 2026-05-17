# Route Collections

`RouteCollection` is an ordered registry of `Route` objects. Matching checks routes in registration order.

## Register Routes

```php
use CommonPHP\Router\RouteCollection;

$routes = new RouteCollection();

$routes->get('/health', $handler, 'health');
$routes->post('/users', $handler, 'users.store');
$routes->route('GET|POST', '/search', $handler, 'search');
```

The helper methods mirror `Route` factories and return the registered `Route`.

## Duplicate Detection

Collections reject:

- duplicate route names;
- duplicate exact method and path pairs.

Different methods may use the same path:

```php
$routes->get('/users', $index);
$routes->post('/users', $store);
```

## Named Lookup

```php
$route = $routes->named('users.store');
$route = $routes->findByName('users.store');
$exists = $routes->hasNamed('users.store');
```

`named()` throws `RouteNotFoundException` when the name is missing. `findByName()` returns `null`.

## Introspection

Use these methods when composing route providers:

- `all()` returns the ordered route list.
- `names()` returns registered route names.
- `isEmpty()` reports whether any routes are registered.
- `count()` returns the number of routes.
- iteration yields each `Route` in registration order.
- `remove($routeOrName)` removes by route object or name.
- `clear()` removes every route.

## Groups

`group()` creates a `RouteGroup` attached to the collection. If a callback is supplied, it is called immediately:

```php
$routes->group('/api', function ($group): void {
    $group->get('/health', $handler, 'health');
}, 'api.');
```
