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

    // TODO help route if we can find gr_help.php?
    $app->get('/frscheck', CheckFRSAction::class);

    // $app->group('/users', function (Group $group) {
    //     $group->get('', ListUsersAction::class);
    //     $group->get('/{id}', ViewUserAction::class);
    // });
};
