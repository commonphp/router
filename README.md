# CommonPHP Router

CommonPHP Router provides route definition and matching support for CommonPHP applications. It maps HTTP requests to actions, surfaces, or handlers using clear route definitions and CommonPHP conventions.

The package keeps routing explicit while remaining separate from request/response mechanics and application rendering.

## Requirements

- PHP `^8.5`
- `comphp/runtime:^0.3`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/router
```

## Usage

```php
<?php

// TODO: Write usage
```

## Package Notes

This package should define and match routes, route groups, constraints, and dispatch metadata. HTTP request handling belongs in `comphp/http`, and action execution belongs in the relevant action/web/API package.

## Error Handling

Invalid route definitions, duplicate routes, unmatched routes, and dispatch failures should throw CommonPHP router exceptions or return route-not-found results as appropriate.

## Documentation

- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
