<?php

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var ContainerInterface $container */
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$container = (require __DIR__ . '/../config/container.php');

$application = $container->get(Application::class);
$application->run();
