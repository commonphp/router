<?php

declare(strict_types=1);

namespace CommonPHP\Router\Contracts;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\RouteMatch;

interface RouteHandlerInterface
{
    public function handle(Request $request, RouteMatch $match): Response;
}
