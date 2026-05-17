<?php

declare(strict_types=1);

namespace CommonPHP\Router;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Contracts\RouteDispatcherInterface;
use CommonPHP\Router\Contracts\RouteHandlerInterface;
use CommonPHP\Router\Exceptions\RouteDispatchException;
use Psr\Container\ContainerInterface;
use Throwable;

class RouteDispatcher implements RouteDispatcherInterface
{
    public function __construct(
        private readonly ?ContainerInterface $container = null,
    ) {
    }

    public function dispatch(RouteMatch $match, Request $request): Response
    {
        try {
            $handler = $this->resolveHandler($match->handler());
            $response = $this->callHandler($handler, $request, $match);
        } catch (RouteDispatchException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RouteDispatchException::failed($match, $exception);
        }

        if (!$response instanceof Response) {
            throw RouteDispatchException::invalidResponse($match, get_debug_type($response));
        }

        return $response;
    }

    private function resolveHandler(mixed $handler): mixed
    {
        if ($handler instanceof RouteHandlerInterface || is_callable($handler)) {
            return $handler;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$target, $method] = array_values($handler);

            if (is_string($target) && is_string($method)) {
                return [$this->resolveClass($target), $method];
            }

            return $handler;
        }

        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                [$class, $method] = explode('@', $handler, 2);

                return [$this->resolveClass($class), $method];
            }

            if (str_contains($handler, '::')) {
                [$class, $method] = explode('::', $handler, 2);

                return [$this->resolveClass($class), $method];
            }

            if ($this->container !== null && $this->container->has($handler)) {
                return $this->container->get($handler);
            }

            if (class_exists($handler)) {
                return $this->resolveClass($handler);
            }
        }

        return $handler;
    }

    private function callHandler(mixed $handler, Request $request, RouteMatch $match): mixed
    {
        if ($handler instanceof RouteHandlerInterface) {
            return $handler->handle($request, $match);
        }

        if (is_callable($handler)) {
            return $handler($request, $match);
        }

        throw RouteDispatchException::invalidHandler($match, get_debug_type($handler));
    }

    private function resolveClass(string $class): object
    {
        if ($this->container !== null && $this->container->has($class)) {
            $resolved = $this->container->get($class);

            if (is_object($resolved)) {
                return $resolved;
            }
        }

        if (!class_exists($class)) {
            throw new RouteDispatchException('Route handler class "' . $class . '" was not found.');
        }

        return new $class();
    }
}
