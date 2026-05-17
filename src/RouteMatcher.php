<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;
use CommonPHP\Router\Contracts\RouteMatcherInterface;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Exceptions\InvalidRouteException;
use CommonPHP\Router\Exceptions\InvalidRouteParameterException;
use CommonPHP\Router\Exceptions\MethodNotAllowedException;
use CommonPHP\Router\Exceptions\RouteNotFoundException;
use CommonPHP\Router\Exceptions\SchemaNotAllowedException;

class RouteMatcher implements RouteMatcherInterface
{
    public function __construct(
        private readonly RouteCollection $routes,
    ) {
    }

    public function match(
        Request|string $request,
        RouteMethod|RequestMethod|string|null $method = null,
    ): RouteMatch {
        $context = $this->context($request, $method);
        $allowedMethods = [];
        $allowedSchemes = [];

        foreach ($this->routes as $route) {
            $parameters = $this->matchPath($route, $context['path']);

            if ($parameters === null) {
                continue;
            }

            if (!$route->allowsScheme($context['scheme'])) {
                array_push($allowedSchemes, ...$route->schemes());
                continue;
            }

            if (!$route->allowsMethod($context['method'])) {
                array_push($allowedMethods, ...$route->allowedMethodValues());
                continue;
            }

            return new RouteMatch($route, $parameters, $context['path'], $context['method'], $context['scheme']);
        }

        if ($allowedMethods !== []) {
            throw MethodNotAllowedException::forPath($context['method']->value(), $context['path'], $allowedMethods);
        }

        if ($allowedSchemes !== [] && $context['scheme'] !== null) {
            throw SchemaNotAllowedException::forPath($context['scheme'], $context['path'], $allowedSchemes);
        }

        throw RouteNotFoundException::forPath($context['path'], $context['method']->value());
    }

    public function find(
        Request|string $request,
        RouteMethod|RequestMethod|string|null $method = null,
    ): ?RouteMatch {
        try {
            return $this->match($request, $method);
        } catch (RouteNotFoundException | MethodNotAllowedException | SchemaNotAllowedException) {
            return null;
        }
    }

    /**
     * @return array{path: string, method: RouteMethod, scheme: ?string}
     */
    private function context(Request|string $request, RouteMethod|RequestMethod|string|null $method): array
    {
        if ($request instanceof Request) {
            return [
                'path' => $this->normalizePath($request->path()),
                'method' => RouteMethod::normalize($request->method()),
                'scheme' => $request->scheme()->value(),
            ];
        }

        $path = parse_url($request, PHP_URL_PATH);
        $scheme = parse_url($request, PHP_URL_SCHEME);

        return [
            'path' => $this->normalizePath(is_string($path) && $path !== '' ? $path : '/'),
            'method' => $method === null ? RouteMethod::GET : RouteMethod::normalize($method),
            'scheme' => is_string($scheme) && $scheme !== '' ? strtolower($scheme) : null,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        if ($path === '*') {
            return $path;
        }

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchPath(Route $route, string $path): ?array
    {
        if ($route->path() === '*') {
            return $route->defaults()->all();
        }

        $parameterNames = [];
        $regex = $this->compilePattern($route, $parameterNames);
        $result = @preg_match($regex, $path, $matches);

        if ($result === false) {
            throw InvalidRouteException::because('Route path pattern could not be compiled for "' . $route->path() . '".');
        }

        if ($result !== 1) {
            return null;
        }

        $parameters = $route->defaults()->all();

        foreach ($parameterNames as $name) {
            if (!array_key_exists($name, $matches)) {
                continue;
            }

            $value = rawurldecode((string) $matches[$name]);
            $constraint = $route->constraint($name);

            if ($constraint !== null && !$constraint->matches($value)) {
                return null;
            }

            $parameters[$name] = $value;
        }

        return $parameters;
    }

    /**
     * @param list<string> $parameterNames
     */
    private function compilePattern(Route $route, array &$parameterNames): string
    {
        $path = $route->path();
        $regex = '';
        $offset = 0;
        $seen = [];

        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)(\*)?(?::([^}]+))?\}/', $path, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $index => $match) {
            [$token, $position] = $match;
            $regex .= preg_quote(substr($path, $offset, $position - $offset), '#');

            $name = $matches[1][$index][0];
            $wildcard = $matches[2][$index][0] === '*';
            $inlinePattern = $matches[3][$index][0] ?? null;
            $constraint = $route->constraint($name);

            if (isset($seen[$name])) {
                throw InvalidRouteParameterException::duplicate($name);
            }

            $seen[$name] = true;
            $parameterNames[] = $name;

            $pattern = $constraint?->pattern() ?? ($inlinePattern ?: ($wildcard ? '.+' : '[^/]+'));
            $regex .= '(?P<' . $name . '>' . str_replace('#', '\#', $pattern) . ')';
            $offset = $position + strlen($token);
        }

        $regex .= preg_quote(substr($path, $offset), '#');

        return '#^' . $regex . '$#u';
    }

}
