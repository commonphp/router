<?php

declare(strict_types=1);

namespace CommonPHP\Router\Enums;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\Router\Exceptions\InvalidRouteException;

enum RouteMethod
{
    case GET;
    case HEAD;
    case POST;
    case PUT;
    case PATCH;
    case DELETE;
    case OPTIONS;
    case TRACE;
    case CONNECT;

    public static function normalize(self|RequestMethod|string $method): self
    {
        if ($method instanceof self) {
            return $method;
        }

        if ($method instanceof RequestMethod) {
            return self::fromString($method->value());
        }

        return self::fromString($method);
    }

    public static function fromString(string $method): self
    {
        $method = strtoupper(trim($method));

        return self::tryFromName($method)
            ?? throw InvalidRouteException::invalidMethod($method);
    }

    public static function tryFromName(string $method): ?self
    {
        return match (strtoupper(trim($method))) {
            'GET' => self::GET,
            'HEAD' => self::HEAD,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'PATCH' => self::PATCH,
            'DELETE' => self::DELETE,
            'OPTIONS' => self::OPTIONS,
            'TRACE' => self::TRACE,
            'CONNECT' => self::CONNECT,
            default => null,
        };
    }

    public function value(): string
    {
        return $this->name;
    }

    public function toHttp(): RequestMethod
    {
        return RequestMethod::fromString($this->name);
    }

    public function isSafe(): bool
    {
        return in_array($this, [self::GET, self::HEAD, self::OPTIONS, self::TRACE], true);
    }

    public function isIdempotent(): bool
    {
        return in_array($this, [self::GET, self::HEAD, self::PUT, self::DELETE, self::OPTIONS, self::TRACE], true);
    }

    public function usuallyHasBody(): bool
    {
        return in_array($this, [self::POST, self::PUT, self::PATCH], true);
    }
}
