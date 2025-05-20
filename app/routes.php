<?php

declare(strict_types=1);

use App\Application\Actions\Customer\GetCustomerViewAction;
use App\Application\Actions\Customer\CreateCustomerViewAction;
use App\Application\Actions\Customer\CheckFRSAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Views\Twig;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // Customer-related pieces
    $app->get('/', GetCustomerViewAction::class);
    $app->map(['GET', 'POST'], '/create', CreateCustomerViewAction::class);

    // Auth-bits for test env; this is not intended to be used outside of your local test environment with Selenium
    if($_ENV['APP_ENV'] === "development") {
        $app->get('/selenium-cookie', function ($request, $response, $args) {
            echo "This page only serves to hook a cookie for the Selenium driver.";
            exit;
        });
    }

    // TODO help route if we can find gr_help.php?
    $app->get('/frscheck', CheckFRSAction::class);
};
