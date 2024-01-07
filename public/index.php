<?php
declare(strict_types=1);

use DI\Bridge\Slim\Bridge;
use Slim\App;
use Symfony\Component\Dotenv\Dotenv;



date_default_timezone_set('Europe/Bratislava');
setlocale(LC_TIME, 'sk_SK');

require __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$container = require __DIR__ . '/../config/container.php';

$container->get(App::class)->run();