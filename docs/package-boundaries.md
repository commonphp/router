# Package Boundaries

CommonPHP Router owns route definitions, route collections, route groups, route matching, route match data, route constraints, and basic dispatch.

## Belongs Here

- HTTP method and path route definitions.
- Named route registration and lookup.
- Route parameter extraction.
- Parameter constraints.
- Route grouping.
- Method and scheme mismatch detection.
- Handler dispatch that returns `CommonPHP\HTTP\Response`.
- Router-specific exceptions.

## Does Not Belong Here

- HTTP request parsing from globals.
- HTTP response emission.
- Middleware pipeline execution.
- URL generation.
- Controller base classes.
- View rendering, templates, UI components, or assets.
- Authentication, authorization, sessions, or CSRF handling.
- Runtime bootstrapping, modules, service providers, or environment loading.
- Database lookups or persistence.

Those concerns should live in their own packages and call Router at their boundary.

## Integration Shape

HTTP packages create `Request` objects. Router matches and optionally dispatches them. Web, API, or application packages decide how route metadata, middleware references, authorization, templates, and error responses are interpreted.

This keeps Router small enough to debug directly while still useful as a shared foundation.
