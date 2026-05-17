<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Fixtures;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\RouteMatch;

final class ControllerHandler
{
    public function show(Request $request, RouteMatch $match): Response
    {
        return new Response('controller:' . $request->methodValue() . ':' . $match->parameter('id', 'none'));
    }

    public function returnsInvalid(Request $request, RouteMatch $match): string
    {
        return $request->path() . ':' . $match->route()->path();
    }

    public function throws(): Response
    {
        throw new \RuntimeException('controller failed');
    }
}
