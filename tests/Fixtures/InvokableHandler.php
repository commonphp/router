<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Fixtures;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\RouteMatch;

final class InvokableHandler
{
    public function __invoke(Request $request, RouteMatch $match): Response
    {
        return new Response('invokable:' . $request->path() . ':' . $match->route()->path());
    }
}
