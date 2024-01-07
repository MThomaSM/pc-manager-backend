<?php

declare(strict_types=1);

use App\Console\ExampleCommand;

$siteName = 'Slim-App';

return [
    'app' => [
        'name' => $_ENV["APP_NAME"],
        'env' => $_ENV["APP_ENV"],
        'debug' => $_ENV["APP_DEBUG"],
        'locale' => 'sk',
        'url' => "http://localhost:3000",
    ],
    'environment' => 'DEVELOPMENT', // DEVELOPMENT or PRODUCTION
    'session' => [
        'name'                   => str_replace(' ', '_', $siteName),
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
        'smtp_host'       => 'smtp.gmail.com',
        'smtp_auth'       => true,
        'smtp_username'   => 'poweris526@gmail.com',
        'smtp_password'   => 'qsecrbcycieylkgx',
        'smtp_secure'     => 'tls',
        'smtp_port'       => 587,
        'smtp_from_email' => 'poweris526@gmail.com',
        'smtp_from_user'  => $siteName . ' Staff',
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
