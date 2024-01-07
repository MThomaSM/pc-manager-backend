<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private $storage;
    public function __construct()
    {
        session_start();
        $this->storage = &$_SESSION;
    }


    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($this->storage['logged'])) {
            $this->storage['logged'] = false;
        }

        $request = $request->withAttribute('session', $this);
        $request = $request->withAttribute('uinfo', array_key_exists('uinfo', $this->storage) ? $this->storage['uinfo'] : null);
        return $handler->handle($request);
    }
}