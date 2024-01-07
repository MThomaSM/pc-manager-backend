<?php

use App\Middleware\WhoopsMiddleware;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Medoo\Medoo;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Selective\BasePath\BasePathMiddleware;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Symfony\Component\Console\Application;
use Tuupola\Middleware\JwtAuthentication;

$definitions = [
    App::class => function (ContainerInterface $container): App {
        AppFactory::setContainer($container);
        $app = Bridge::create($container);

        (require __DIR__ . '/validation.php')($app);
        (require __DIR__ . '/routes.php')($app);
        (require __DIR__ . '/middleware.php')($app);

        return $app;
    },
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },
    Psr17Factory::class => function () {
        return new Psr17Factory();
    },
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },
    ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },
    StreamFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },
    UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },
    UriFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },
    BasePathMiddleware::class => function (ContainerInterface $container) {
        return new BasePathMiddleware($container->get(App::class));
    },
    PHPMailer::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['mail'];
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = $settings['smtp_auth'];
        $mail->Port = $settings['smtp_port'];
        if ($settings['smtp_username']) {
            $mail->Username = $settings['smtp_username'];
        }
        if ($settings['smtp_password']) {
            $mail->Password = $settings['smtp_password'];
        }
        if ($settings['smtp_secure']) {
            $mail->SMTPSecure = $settings['smtp_secure'];
        }
        $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_user']);
        $mail->isHTML();

        return $mail;
    },
    JwtAuthentication::class => function (ContainerInterface $container): JwtAuthentication {
        $settings = $container->get('settings');
        return new JwtAuthentication([
            "secure" => false,
            "secret" => $settings["jwt"]["secret"],
            "path" => ["/api"],
            "ignore" => ["/api/signup", "/api/auth", "/api/users/password-request", "/api/users/password-reset/*"],
            "algorithm" => $settings["jwt"]["algorithm"],
            "before" => function ($request, $arguments) use ($container) {
                return $request->withAttribute("user", $container->get(Medoo::class)->get("user", "*" , ["id" => $request->getAttribute("token")["id"]]));
            },
            "error" => function ($response, $arguments) {
                $data["status"] = "error";
                $data["message"] = $arguments["message"];

                $response->getBody()->write(
                    json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                );

                return $response->withHeader("Content-Type", "application/json");
            }
        ]);
    },
    Medoo::class => function (ContainerInterface $container): Medoo {
        $settings = $container->get('settings');
        return new Medoo([
            'type' => 'mysql',
            'host' => $settings['db']["DB_SERVER"],
            'database' => $settings['db']["DB_NAME"],
            'username' => $settings['db']["DB_USERNAME"],
            'password' => $settings['db']["DB_PASSWORD"]
        ]);
    },
    WhoopsMiddleware::class => function (ContainerInterface $container) {
        $env = $container->get('settings')['environment'];

        return new WhoopsMiddleware([
            'enable' => true,
            'editor' => 'phpstorm',
            'title'  => 'whoops',
        ]);
    },
    Application::class => function (ContainerInterface $container): Application {
        $application = new Application();
        foreach ($container->get('settings')['commands'] as $class) {
            $application->add($container->get($class));
        }
        return $application;
    },
    Logger::class => function (ContainerInterface $container): Logger {
        $logger = new Logger('app_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/debug.log', Level::Debug));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/info.log', Level::Info));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/notice.log', Level::Notice));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/warning.log', Level::Warning));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/error.log', Level::Error));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/critical.log', Level::Critical));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/alert.log', Level::Alert));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/emergency.log', Level::Emergency));
        return $logger;
    },
];

return (new ContainerBuilder())->addDefinitions($definitions)->useAttributes(true)->build();