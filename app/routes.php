<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
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

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });
    
    $app->get('/test_twig', function(Request $request, Response $response) {
        $view = Twig::fromRequest($request);

        return $view->render($response, 'example.html.twig', [
            'name' => 'Chocollato'
        ]);
    });

    $app->get('/test_user_header', function(Request $request, Response $response) {
        $view = Twig::fromRequest($request);

        $view->getEnvironment()->addGlobal('user', $_SESSION['eds_data'] ?? null);

        return $view->render($response, 'customer_home.html.twig', [
            'name' => 'test_user_header',
            'user' => $_SESSION['eds_data'] ?? null
        ]);
    });

    // $app->group('/users', function (Group $group) {
    //     $group->get('', ListUsersAction::class);
    //     $group->get('/{id}', ViewUserAction::class);
    // });
};
