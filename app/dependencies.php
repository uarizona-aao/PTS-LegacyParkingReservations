<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use App\Application\Responders\CustomerResponder;
use App\Application\Services\DateValidator;
use Slim\Views\Twig;
use Twig\Loader\FilesystemLoader;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        Twig::class => function () {
            $loader = new FilesystemLoader(__DIR__ . '/../templates');
            return new Twig($loader);
        },
        CustomerResponder::class => function (ContainerInterface $c) {
            return new CustomerResponder($c->get(Twig::class));
        },
        DateValidator::class => \DI\autowire(),
    ]);
};
