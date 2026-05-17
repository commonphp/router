# Parameters And Metadata

Router uses small data objects for route parameters and metadata. Both are array-accessible, countable, iterable, and easy to clone.

## Route Parameters

`RouteParameters` stores named route values.

```php
use CommonPHP\Router\RouteParameters;

$parameters = new RouteParameters(['id' => '42']);

$parameters->get('id');              // 42
$parameters->get('missing', 'none'); // none
$parameters->getRequired('id');      // 42
```

Missing required parameters throw `InvalidRouteParameterException`.

Valid parameter names must match:

```text
[A-Za-z_][A-Za-z0-9_]*
```

## Parameter Mutation

```php
$parameters
    ->set('id', '42')
    ->merge(['slug' => 'monthly-close'])
    ->remove('slug');

$parameters->replace(['page' => 1]);
$parameters->clear();
```

## Route Metadata

`RouteMetadata` stores arbitrary named values for higher-level packages:

```php
$route->meta('scope', 'admin');
$route->withMetadata(['surface' => 'api']);

$metadata = $match->metadata();
```

Metadata keys must be non-empty strings.

## Clone Behavior

`Route::defaults()`, `Route::metadata()`, `RouteMatch::parameters()`, and `RouteMatch::metadata()` return clones. Mutating the returned object does not mutate the route or match.

Use route mutation methods when you want to update stored values:

```php
$route->default('page', 1);
$route->meta('scope', 'admin');
```
