<?php

declare(strict_types=1);

use App\Application\Actions\Customer\GetCustomerViewAction;
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

    // Main landing page for users
    $app->get('/', function (Request $request, Response $response) {
        // Relevant fetches of data
        // Testing instead if we can invoke $this->dependencies customer responder

        // Configure the environment.
        $view = Twig::fromRequest($request);
        $view->getEnvironment()->addGlobal('user', $_SESSION['eds_data'] ?? null);
        $view->getEnvironment()->addGlobal('get', $_GET);

        return $view->render($response, 'customer_home.html.twig', [
            'user' => $_SESSION['eds_data'] ?? null,
        ]);
    });
    
    $app->get('/test_customer_view', GetCustomerViewAction::class);

    // $app->group('/users', function (Group $group) {
    //     $group->get('', ListUsersAction::class);
    //     $group->get('/{id}', ViewUserAction::class);
    // });
};
