<?php

use App\Middleware\CorsMiddleware;
use App\Middleware\WhoopsMiddleware;
use Clockwork\Support\Slim\ClockworkMiddleware;
use DI\NotFoundException;
use Medoo\Medoo;
use Middlewares\TrailingSlash;
use Selective\BasePath\BasePathMiddleware;
use Slim\App;
use Slim\Views\TwigMiddleware;
use Tuupola\Middleware\JwtAuthentication;

return function (App $app) { //LIFO last in first out - posledny na spodku sa exekuuje vždy prvý
    if (!($container = $app->getContainer())) {
        throw new NotFoundException('Could not get the container.');
    }
    $app->add($container->get(BasePathMiddleware::class));

    $app->addRoutingMiddleware();

    $app->add(CorsMiddleware::class);

    $app->add($container->get(JwtAuthentication::class));

    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    // Add the Slim built-in routing middleware


    $app->add($container->get(TrailingSlash::class));

    $app->add($container->get(WhoopsMiddleware::class));
};