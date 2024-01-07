<?php

namespace App\Controller;

use App\Helper;
use DI\Attribute\Inject;
use Fig\Http\Message\StatusCodeInterface;
use Medoo\Medoo;
use Monolog\Logger;
use Nette\Database\Connection;
use Nette\Database\Explorer;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Symfony\Component\VarExporter\Internal\Exporter;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Valitron\Validator;

abstract class AbstractController
{
    public function __construct(
        protected readonly Medoo $db,
        #[Inject('settings')]
        protected readonly array $settings,
        protected readonly RouteParserInterface $routeParser,
        protected readonly Logger $logger,
    )
    {
    }

    protected function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write((string)json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    protected function data(Response $response, mixed $data, int $status = StatusCodeInterface::STATUS_OK, array $meta = []): Response
    {
        return $this->json($response, ["status" => "success", "data" => $data, "meta" => $meta], $status);
    }

    protected function error(Response $response, mixed $data, int $status = StatusCodeInterface::STATUS_BAD_REQUEST): Response
    {
        return $this->json($response, ["status" => "error", "message" => $data], $status);
    }

    protected function html(Response $response, string $data, int $status = 200, int $flags = 0): Response
    {
        $response->getBody()->write($data);
        return $response->withStatus($status)->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    protected function redirect(Response $response, string $url,array $query = null, int $status = 302): Response
    {
        if ($query) {
            $url = sprintf('%s?%s', $url, http_build_query($query));
        }

        return $response->withStatus($status)->withHeader('Location', $url);
    }

    protected function redirectFor(
        Response $response,
        string $routeName,
        array $data = [],
        array $queryParams = []
    ): Response {
        return $this->redirect($response, $this->routeParser->urlFor($routeName, $data, $queryParams));
    }
}