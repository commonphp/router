# Error Handling

All router-specific exceptions extend `CommonPHP\Router\Exceptions\RouterException`.

## Definition Errors

Invalid route setup throws:

- `InvalidRouteException`
- `InvalidRouteParameterException`
- `DuplicateRouteException`

Examples include invalid methods, empty paths, invalid route names, unbalanced parameter braces, duplicate parameter names, unsupported schemes, duplicate route names, and duplicate method/path pairs.

## Matching Errors

`RouteMatcher::match()` throws:

- `RouteNotFoundException` when no route path matches;
- `MethodNotAllowedException` when a path matches but the request method is not allowed;
- `SchemaNotAllowedException` when a path matches but the request scheme is not allowed.

`MethodNotAllowedException::allowedMethods()` returns normalized allowed methods. `GET` routes include `HEAD`.

`SchemaNotAllowedException::allowedSchemes()` returns normalized allowed schemes.

Use `find()` when these outcomes should be represented by `null`.

## Dispatch Errors

`RouteDispatchException` is thrown when dispatch cannot produce a valid response.

Common causes:

- handler class or service cannot be found;
- handler is not callable and does not implement `RouteHandlerInterface`;
- handler returns a non-`Response`;
- handler throws.

When a handler throws, the original exception is attached as the previous exception.

## Boundary Translation

Application packages should translate router exceptions at their boundary:

- API packages may convert them to JSON problem responses.
- Web packages may render error pages.
- Console or testing helpers may print route diagnostics.

Router itself keeps exceptions transport-neutral except for using HTTP method and scheme concepts.
