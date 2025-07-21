<?php

declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use App\Application\Middleware\WebauthMiddleware;
use App\Application\Middleware\DevCorsMiddleware;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

/*
It is worth noting the middleware declarations are LIFO order; the last one is applied first.
The webauth middleware also relies on the session middleware, so it's added *before* the session middleware.
*/
return function (App $app) {
    // Add twig stuff
    $twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
    $app->add(TwigMiddleware::create($app, $twig));

    // Add the rest
    $app->add(WebauthMiddleware::class);
    $app->add(SessionMiddleware::class);
    if($_ENV['APP_ENV'] === "development") {
        $app->add(DevCorsMiddleware::class);
    }
};