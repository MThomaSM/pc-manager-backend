<?php

use Nyholm\Psr7\Request;
use Nyholm\Psr7\ServerRequest;
use Slim\App;

return function (App $app) {
    (require __DIR__ . '/../routes/web.php')($app);
    (require __DIR__ . '/../routes/api.php')($app);

//    $app->map(
//        ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
//        '/{routes:.+}',
//        function (ServerRequest $request): void {
//            throw new Slim\Exception\HttpNotFoundException($request);
//        }
//    );
};
