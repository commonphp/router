<?php

declare(strict_types=1);

namespace CommonPHP\Router\Contracts;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\RouteMatch;

interface RouteDispatcherInterface
{
    public function dispatch(RouteMatch $match, Request $request): Response;
}
