<?php

namespace App\Controller;

use DI\Attribute\Inject;
use Doctrine\ORM\EntityManager;
use Medoo\Medoo;
use Monolog\Logger;
use Nette\Database\ResultSet;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HelloWorldController extends AbstractController
{
    public function index(Request $request, Response $response, array $args = null, Logger $logger): Response
    {
        return $this->html($response, "Hello world");
    }

}