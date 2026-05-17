# Constraints

Constraints restrict parameter values after a path pattern matches.

## Built-In Constraints

```php
$route->whereNumber('id');
$route->whereAlpha('name');
$route->whereAlphaNumeric('code');
$route->whereSlug('slug');
$route->whereUuid('uuid');
$route->whereIn('status', ['draft', 'published']);
```

## Regex Constraints

Use a string or `RouteConstraint::regex()`:

```php
$route->where('code', '[A-Z]{2}[0-9]{2}');
```

The pattern is matched against the whole parameter value.

## Callback Constraints

```php
$route->where('id', static fn (string $value): bool => $value !== '13');
```

Callback constraints use the default single-segment pattern unless you provide a `RouteConstraint` with a custom pattern:

```php
use CommonPHP\Router\RouteConstraint;

$route->where('id', RouteConstraint::callback(
    static fn (string $value): bool => $value !== '13',
    '[0-9]+',
));
```

## Inline Constraints

Inline regex constraints can be declared inside the route path:

```php
$route = Route::get('/users/{id:[0-9]+}', $handler);
```

An explicit `where()` constraint for the same parameter takes precedence over the inline pattern.

## Wildcards

Use `{name*}` to capture multiple path segments:

```php
$route = Route::get('/files/{path*}', $handler);
```

The value is URL-decoded before being placed in `RouteMatch` parameters.
