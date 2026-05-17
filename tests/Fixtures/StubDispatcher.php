<?php

declare(strict_types=1);

namespace CommonPHP\Router\Tests\Fixtures;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Contracts\RouteDispatcherInterface;
use CommonPHP\Router\RouteMatch;

final class StubDispatcher implements RouteDispatcherInterface
{
    public ?RouteMatch $match = null;

    public ?Request $request = null;

    public function __construct(
        private readonly Response $response = new Response('stub'),
    ) {
    }

    public function dispatch(RouteMatch $match, Request $request): Response
    {
        $this->match = $match;
        $this->request = $request;

        return $this->response;
    }
}
