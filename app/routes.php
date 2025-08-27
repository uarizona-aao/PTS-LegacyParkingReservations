<?php

declare(strict_types=1);

use App\Application\Actions\Customer\GetCustomerViewAction;
use App\Application\Actions\Customer\CreateCustomerViewAction;
use App\Application\Actions\Customer\CheckFRSAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Infrastructure\Database\reservation;
use FastRoute\Route;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // Customer-related pieces
    $app->get('/', GetCustomerViewAction::class);
    $app->map(['GET', 'POST'], '/create', CreateCustomerViewAction::class);

    $app->post('/confirm_user_information', function (Request $request, Response $response, $args) {
        // This will be called from the customer.php->custset() function when a new user is created and the form submits here.
        // invoke customer->validDept() to verify it's valid department number in the POST
        $postData = $request->getParsedBody();
        $deptNum = $postData['deptno'] ?? null;

        if(empty($deptNum)) {
            $data = [
                'error' => 'Department number is required.',
                'newInfo' => $postData,
                'path' => $_ENV['APP_URL'] . '/confirm_user_information'
            ];
            $responder = new \App\Application\Responders\CustomerResponder($this->get(Twig::class));
            return $responder->confirm_user_information($response, $data);
        }

        // Instantiate the db and customer class to call validDept
        $db = new \App\Infrastructure\Database\GrLogin('');
        $customer = new \App\Infrastructure\Database\customer($db);
        $customer->newInfo = $postData; // Keep it in order for class invocations

        $is_deptnum_valid = $customer->validDept($deptNum);
        if(!$is_deptnum_valid) {
            $data = [
                'error' => 'Invalid department number.',
                'newInfo' => $postData,
                'path' => $_ENV['APP_URL'] . '/confirm_user_information'
            ];
            $responder = new \App\Application\Responders\CustomerResponder($this->get(Twig::class));
            return $responder->confirm_user_information($response, $data);
        }

        // Run createAccount and see if it works, if false, use errorMsg and return to the template
        $create_result = $customer->createAccount();
        if($create_result === false) {
            $data = [
                'error' => $customer->errorMsg ?? 'There was an error creating your account. Please contact PTS Visitor Programs at (520) 621-3710.',
                'newInfo' => $postData,
                'path' => $_ENV['APP_URL'] . '/confirm_user_information'
            ];
            $responder = new \App\Application\Responders\CustomerResponder($this->get(Twig::class));
            return $responder->confirm_user_information($response, $data);
        }

        // Appears to have succeeded, return them to the home page.
        // But first, regenerate $_SESSION['cuinfo'] through a fresh GrLogin instance
        $login_refresh = new \App\Infrastructure\Database\GrLogin($_SESSION['resuser']['netid']);

        $response = $response->withHeader('Location', $_ENV['APP_URL'] . '/?msg=custcreate')->withStatus(302);
        return $response;
    })->setName('confirm_user_information');

    // Auth-bits for test env; this is not intended to be used outside of your local test environment with Selenium
    if($_ENV['APP_ENV'] === "development") {
        $app->get('/selenium-cookie', function ($request, $response, $args) {
            echo "This page only serves to hook a cookie for the Selenium driver.";
            exit;
        });
    }

    // TODO help route if we can find gr_help.php?
    $app->get('/frscheck', CheckFRSAction::class);

    // // api route
    // $app->post('/new_res', function (Request $request, Response $response) {
    //     // return a hello world response until i can hook in the reservation class.
    //     $reservation = new reservation();
    //     $postData = $request->getParsedBody();

    //     /*
    //         Dev notes:
    //         - gg is either 'guest' or 'group'; 
    //         ** 'group' is for group reservations
    //         ** If so, option1/option2 == group name (as an array with one value) and group size, [str]/int
    //         ** 'guest' is for guest reservations
    //         ** If so, option1/option2 == array of users, from " | "-delimited string, and null respectively.
    //         - add_guests maps laddGuests if list, gaddGuests if group
    //         - dates is a comma-delimited string of dates. format MM/DD/YYYY
    //         - start and end time are HH:MM AM/PM format
    //         - come_go is based on group and gComeGo set, 1 or 0, OR list and comego;
    //         ** In every case, it's 0. We don't use it anymore.
    //     */

    //     // Assuming newRes expects specific arguments, map them explicitly
    //     $data = [
    //         'frs' => $postData['frs'] ?? null,
    //         'kfs_account' => $postData['kfs_account'] ?? null,
    //         'kfs_sub_object_code' => $postData['kfs_sub_object_code'] ?? null,
    //         'dates' => $postData['dates'] ?? null,
    //         'garage' => $postData['garage'] ?? null,
    //         'group_guests' => $postData['group_guests'] ?? null,
    //         'option1' => $postData['option1'] ?? null,
    //         'option2' => $postData['option2'] ?? null,
    //         'add_guests' => $postData['add_guests'] ?? '',
    //         'come_go' => "0",
    //         'extra' => "0",
    //         'notes' => "", // useless column that isn't used in the main app.
    //         'start_time' => $postData['start_time'] ?? null,
    //         'end_time' => $postData['end_time'] ?? null,
    //         'dry' => true, // assuming dry run is always true for this test
    //         'customer' => [
    //             "userid" => "12110",
    //             "username" => "Todd Dalton",
    //             "netid" => "daltont",
    //             "phone" => "931-494-4803",
    //             "email" => "daltont@arizona.edu",
    //             "auth" => "4",
    //             "authdesc" => "Administrator",
    //             "deptno" => [
    //                 "09804",
    //                 "EN32",
    //                 "09521"
    //             ],
    //             "deptname" => [
    //                 "PARKING/TRANSPORTATION SERVICE",
    //                 "Engineering Graduate Education",
    //                 "Information Security Office"
    //             ] 
    //         ],
    //     ];

    //     // $result = $reservation->newRes(
    //     //     $data['frs'] ?? '',
    //     //     $data['kfs_account'] ?? '',
    //     //     $data['kfs_sub_object_code'] ?? '',
    //     //     $data['customer'] ?? [],
    //     //     $data['garage'] ?? 0,
    //     //     $data['dates'] ?? [],
    //     //     $data['start_time'] ?? '',
    //     //     $data['end_time'] ?? '',
    //     //     $data['group_guests'] ?? '',
    //     //     $data['option1'] ?? '',
    //     //     $data['option2'] ?? 0,
    //     //     $data['come_go'] ?? '0',
    //     //     $data['extra'] ?? 0,
    //     //     $data['add_guests'] ?? '',
    //     //     $data['dry'] ?? false
    //     // );

    //     $response->getBody()->write($reservation->error);
    //     return $response;
    // });
};