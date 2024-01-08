<?php

declare(strict_types=1);

use App\Console\ExampleCommand;

return [
    'app' => [
        'name' => $_ENV["APP_NAME"],
        'env' => $_ENV["APP_ENV"],
        'debug' => $_ENV["APP_DEBUG"],
        'locale' => 'sk',
        'url' => "http://localhost:3000",
    ],
    'environment' => $_ENV["APP_ENV"], // DEVELOPMENT or PRODUCTION
    'session' => [
        'name'                   => str_replace(' ', '_', $_ENV["APP_NAME"]),
        'sid_length'             => '128',
        'cache_expire'           => '300',
        'lazy_write'             => '0',
        'sid_bits_per_character' => '5',
        'use_strict_mode'        => '1',
        'use_trans_sid'          => '0',
        'use_cookies'            => '1',
        'use_only_cookies'       => '1',
        'cookie_domain'          => '',
        'cookie_httponly'        => '1',
        'cookie_lifetime'        => '0',
        'cookie_path'            => '/',
        'cookie_samesite'        => 'Lax',
        'cookie_secure'          => '0',
    ],
    'db' => [
        "DB_TYPE" => $_ENV["DB_TYPE"],
        "DB_NAME" => $_ENV["DB_NAME"],
        "DB_SERVER" => $_ENV["DB_SERVER"],
        "DB_USERNAME" => $_ENV["DB_USERNAME"],
        "DB_PASSWORD" => $_ENV["DB_PASSWORD"]
    ],
    'twig' => [
        'path' => '../resources/views',
        'cache' => false,
        'charset' => 'UTF-8',
    ],
    'webpack' => [
        'manifest' => 'manifest.json',
    ],
    'mail' => [
        'smtp_enable'     => false,
        'smtp_host'       => $_ENV["SMTP_HOST"],
        'smtp_auth'       => true,
        'smtp_username'   => $_ENV["SMTP_USERNAME"],
        'smtp_password'   => $_ENV["SMTP_PASSWORD"],
        'smtp_secure'     => $_ENV["SMTP_SECURE"],
        'smtp_port'       => $_ENV["SMTP_PORT"],
        'smtp_from_email' => $_ENV["SMTP_FROM_EMAIL"],
        'smtp_from_user'  => $_ENV["SMTP_FROM_NAME"],
    ],
    "jwt" => [
        "secret" => $_ENV["JWT_SECRET"],
        "algorithm" => "HS512",
        "expiration" => $_ENV["JWT_EXPIRATION"]
    ],
    'commands' => [
        ExampleCommand::class,
    ]
];
