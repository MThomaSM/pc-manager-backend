<?php

use App\Controller\HelloWorldController;
use Slim\App;

return function (App $app) {
    $app->get('/', [HelloWorldController::class, 'index']);
};
