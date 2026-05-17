# Route Groups

`RouteGroup` applies shared route options while registering routes into an existing `RouteCollection`.

## Prefixes

```php
$routes->group('/api', function ($group): void {
    $group->get('/users/{id}', $handler, 'users.show');
}, 'api.');
```

This creates:

- path: `/api/users/{id}`
- name: `api.users.show`

Nested groups compose prefixes:

```php
$routes->group('/api', function ($group): void {
    $group->group('/v1', function ($group): void {
        $group->get('/users', $handler, 'users.index');
    }, 'v1.');
}, 'api.');
```

The nested route name is `api.v1.users.index`.

## Shared Options

Groups can provide shared:

- constraints;
- default parameters;
- metadata;
- schemes;
- middleware references.

```php
$routes->group(
    '/admin',
    function ($group): void {
        $group->get('/reports/{id}', $handler, 'reports.show');
    },
    'admin.',
    ['id' => '[0-9]+'],
    ['page' => 1],
    ['scope' => 'admin'],
    ['https'],
    ['auth'],
);
```

Route-level constraints, defaults, metadata, schemes, and middleware can be provided when calling `route()`. Constraints override group constraints by parameter name. Defaults and metadata merge with group values. Explicit route schemes replace group schemes. Middleware references append after group middleware.

## Group Objects

Calling `group()` without a callback returns a `RouteGroup` for later registration:

```php
$admin = $routes->group('/admin', namePrefix: 'admin.');

$admin->get('/dashboard', $handler, 'dashboard');
```
