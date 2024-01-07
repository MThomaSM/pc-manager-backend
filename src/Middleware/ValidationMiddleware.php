<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Valitron\Validator;

class ValidationMiddleware implements MiddlewareInterface
{

    public function __construct(private readonly array $content, private readonly array $rules)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $v = new Validator($this->content);
        foreach ($this->rules as $field => $fieldRules) {
            foreach ($fieldRules as $ruleDetails) {
                $rule = $ruleDetails['rule'];
                $params = $ruleDetails['params'] ?? [];
                $message = $ruleDetails['message'] ?? null;

                $v->rule($rule, $field, $params);
                if ($message) {
                    $v->message($message);
                }
            }
        }

        if (!$v->validate()) {
            // Handle validation errors
            $errors = $v->errors();
            // Return or handle errors
        }

        return $handler->handle($request);

    }
}