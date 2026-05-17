# CommonPHP Router Documentation

CommonPHP Router is the standalone route definition, matching, and dispatch package for CommonPHP applications. It maps HTTP methods and paths to explicit handlers while keeping request parsing, response objects, middleware processing, controllers, and application rendering in their own packages.

Router is intentionally small. Route definitions are plain objects, route collections are simple registries, match results are easy to inspect, and dispatch failures are reported through router exceptions.

## Start Here

- [Getting started](getting-started.md)
- [Usage](usage.md)
- [Package boundaries](package-boundaries.md)

## Router Concepts

- [Routes](routes.md)
- [Route collections](route-collections.md)
- [Route groups](route-groups.md)
- [Constraints](constraints.md)
- [Matching](matching.md)
- [Dispatching](dispatching.md)
- [Parameters and metadata](parameters-metadata.md)
- [Error handling](error-handling.md)

## Examples

- [Examples index](examples/index.md)
- [Basic routing](examples/basic-routing.md)
- [Grouped routes](examples/grouped-routes.md)
- [Container dispatch](examples/container-dispatch.md)

## Development

- [Testing and QA](testing.md)

## Public API Map

Entry points:

- `CommonPHP\Router\Router`
- `CommonPHP\Router\RouteCollection`
- `CommonPHP\Router\Route`
- `CommonPHP\Router\RouteMatcher`
- `CommonPHP\Router\RouteDispatcher`

Route data:

- `CommonPHP\Router\RouteGroup`
- `CommonPHP\Router\RouteMatch`
- `CommonPHP\Router\RouteParameters`
- `CommonPHP\Router\RouteMetadata`
- `CommonPHP\Router\RouteConstraint`
- `CommonPHP\Router\Enums\RouteMethod`

Contracts:

- `CommonPHP\Router\Contracts\RouterInterface`
- `CommonPHP\Router\Contracts\RouteMatcherInterface`
- `CommonPHP\Router\Contracts\RouteDispatcherInterface`
- `CommonPHP\Router\Contracts\RouteHandlerInterface`
- `CommonPHP\Router\Contracts\RouteConstraintInterface`

Exceptions:

- `CommonPHP\Router\Exceptions\RouterException`
- `CommonPHP\Router\Exceptions\InvalidRouteException`
- `CommonPHP\Router\Exceptions\DuplicateRouteException`
- `CommonPHP\Router\Exceptions\InvalidRouteParameterException`
- `CommonPHP\Router\Exceptions\RouteNotFoundException`
- `CommonPHP\Router\Exceptions\MethodNotAllowedException`
- `CommonPHP\Router\Exceptions\SchemaNotAllowedException`
- `CommonPHP\Router\Exceptions\RouteDispatchException`
