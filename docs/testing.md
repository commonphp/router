# Testing And QA

CommonPHP Router includes a package-local PHPUnit configuration and unit tests.

## Install Dependencies

From the package directory:

```bash
composer install
```

From the monorepo, the root `vendor` directory can also satisfy the test suite because `tests/bootstrap.php` checks both package and workspace autoloaders.

## Run PHPUnit

From the monorepo root:

```bash
vendor/bin/phpunit -c package/router/phpunit.xml.dist
```

On Windows:

```bash
vendor\bin\phpunit.bat -c package\router\phpunit.xml.dist
```

From `package/router`:

```bash
../../vendor/bin/phpunit -c phpunit.xml.dist
```

## Current Test Coverage

The unit suite covers:

- `RouteMethod` normalization, conversion, and method traits;
- `RouteConstraint` default, regex, callback, list, numeric, alpha, alphanumeric, slug, UUID, and invalid-pattern behavior;
- `RouteParameters` construction, cloning, mutation, array access, iteration, required lookup, and invalid names;
- `RouteMetadata` construction, cloning, mutation, array access, iteration, and invalid keys;
- `Route` factories, method and path normalization, names, handlers, signatures, constraints, defaults, metadata, schemes, middleware references, wildcard paths, and invalid definitions;
- `RouteCollection` registration, helper methods, named lookup, duplicate detection, grouping, removal, clearing, counting, and iteration;
- `RouteGroup` prefix and name composition, nested groups, and shared option inheritance or override behavior;
- `RouteMatcher` exact paths, URL strings, request objects, query stripping, parameter decoding, defaults, inline constraints, wildcard parameters, catch-all routes, constraint fallthrough, `HEAD` fallback, method mismatch, scheme mismatch, missing routes, and `find()` null behavior;
- `RouteMatch` accessors, labels, clone behavior, and required parameter failures;
- `RouteDispatcher` closures, handler objects, invokable objects, object method arrays, class method arrays, `Class@method`, `Class::method`, service IDs, class names, invalid handlers, invalid responses, handler failures, and missing classes;
- `Router` collaborator setup, route helpers, groups, named lookup, match/find, dispatch/handle, custom dispatchers, and container-backed dispatch;
- router exception factory context and stored allowed methods or schemes.

## Manual Review Areas

Manual review should still cover package integrations that interpret route metadata, execute middleware references, generate URLs, or translate router exceptions into API, web, or console responses.
