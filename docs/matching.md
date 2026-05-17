# Matching

`RouteMatcher` matches a path or `CommonPHP\HTTP\Request` against an ordered `RouteCollection`.

## Match A Request

```php
use CommonPHP\Router\RouteMatcher;

$matcher = new RouteMatcher($routes);
$match = $matcher->match($request);
```

The match contains:

- the matched `Route`;
- route parameters;
- the normalized path;
- the matched method;
- the request scheme when available.

## Match A String Path

```php
$match = $matcher->match('/users/42', 'GET');
```

If no method is supplied for string paths, `GET` is used.

URL strings are supported:

```php
$match = $matcher->match('https://example.test/users/42', 'GET');
```

The URL path and scheme are used for matching. Query strings do not affect path matching.

## Find Without Exceptions

```php
$match = $matcher->find('/missing');
```

`find()` returns `null` for:

- route not found;
- method not allowed;
- scheme not allowed.

## Matching Order

Routes are checked in collection order. A route whose path matches but whose constraint rejects a parameter is skipped, allowing a later route to match:

```php
$routes->get('/users/{id}', $byId)->whereNumber('id');
$routes->get('/users/{slug}', $bySlug)->whereSlug('slug');
```

## Failure Modes

`match()` throws:

- `RouteNotFoundException` when no route path matches;
- `MethodNotAllowedException` when the path exists but the method is not allowed;
- `SchemaNotAllowedException` when the path exists but the request scheme is not allowed.

`GET` routes also accept `HEAD` requests.
