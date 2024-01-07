<?php

namespace App\Middleware;

use App\Util\WhoopsGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WhoopsMiddleware implements MiddlewareInterface
{
    public function __construct(protected array $settings = [], protected array $handlers = []) {
    }


    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $whoopsGuard = new WhoopsGuard($this->settings);
        $whoopsGuard->setRequest($request);
        $whoopsGuard->setHandlers($this->handlers);
        $whoopsGuard->install();

        return $handler->handle($request);
    }
}