<?php
namespace App\Application\Responders;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class CustomerResponder {
    protected $view;
    public $phone = '520-626-7275';

    public function __construct(Twig $view)
    {
        $this->view = $view;
        $view->getEnvironment()->addGlobal('user', $_SESSION['eds_data'] ?? null);
        $view->getEnvironment()->addGlobal('post', $_POST ?? null);
        $view->getEnvironment()->addGlobal('get', $_GET);
    }

    public function index(Response $response, array $data) {
        return $this->view->render(
            $response, 
            'customer_home.html.twig', 
            $data);
    }

    public function create(Response $response, array $data) {
        return $this->view->render(
            $response, 
            'customer_create.html.twig', 
            $data);
    }  

    public function agreement(Response $response, array $data) {
        return $this->view->render(
            $response, 
            'reservation_agreement.html.twig', 
            $data);
    }

    public function confirmation(Response $response, array $data): Response 
    {
        return $this->view->render($response, 'components/confirmation.html.twig', [
            'reservation' => $data['reservation'],
            'receipt' => $data['receipt'] ?? false,
            'pdfConfirmFile' => $data['pdfConfirmFile'] ?? null,
            'auth' => $data['auth'] ?? 0,
            'can_edit' => $data['can_edit'] ?? false,
            'can_cancel' => $data['can_cancel'] ?? false,
            'can_revive' => $data['can_revive'] ?? false,
            'dateStr' => $data['dateStr'] ?? '',
            'gg' => $data['gg'] ?? 'guest',
            'garage_text' => $data['garage_text'] ?? '',
            'history' => $data['history'] ?? [],
            'back_url' => $data['back_url'] ?? '/index.php',
            'path' => $data['path'] ?? $_SERVER['PHP_SELF'],
            'is_dry_run' => $data['is_dry_run'] ?? false, // Add dry run flag
        ]);
    }

    public function errorOut ($error,$resdate='',$errormsg='') {
		$errors = array(
			'duplicateGuests' => "There were duplicate names found. Check and try again.",
			'db'=>"There has been a database error. Please try again. If the problem continues, please contact PTS Visitor Programs at $this->phone<br/>$errormsg",
			'noConf'=>"No confirmation number was returned when making the reservation. The reservation has most likely failed due to a system error. Please contact PTS Visitor Programs at $this->phone during normal business hours to complete your reservation.",
			'groupName'=>'Please enter a group name',
			'oneGuest'=>'Please enter at least one guest in the guest list',
			'beforeToday'=>'The date you entered is before today. Reservations can only be made on or after the next business day after today.',
			'today'=>"Reservations for the current date cannot be made online. Please call PTS Visitor Programs at $this->phone if you would like a reservation for today.",
			'groupSize'=>"Reservations are limited to 25 total spaces. To make a larger reservation, please contact PTS Visitor Programs at $this->phone.",
			"resCount"=>"Your department has already reserved spaces in this garage for this date $resdate, but is limited to 25 total spaces. If you would like to reserve more, please contact PTS Visitor Programs at $this->phone.",
			"garageMax"=>"<br/>The garage you selected has already reached its capacity for $resdate.<br/> Please select another garage for this date, or <br/>for further garage availability information on $resdate,<br/> please contact PTS Visitor Programs at <u/>$this->phone</u/>.",
			"weekend"=>"The date you selected falls on a day when the garages are open (campus holiday or weekend day). Reservations are not necessary on these dates.",
			'notDate'=>'The date you selected is not a valid date. Please format all dates as MM/DD/YYYY (e.g. - 01/01/2005). Please check the information and try again.',
			'notTime'=>'The time you entered for this reservation is invalid. Please format all times HH:MI AM (e.g. - 08:00 AM). Please check the information and try again.',
			'notTimeEntry'=>'The time you entered for this reservation entry is invalid. Please format all times HH:MI AM (e.g. - 08:00 AM). Please check the information and try again.',
			'notTimeExit'=>'The time you entered for this reservation exit is invalid. Please format all times HH:MI AM (e.g. - 08:00 AM). Please check the information and try again.',
			'noDates'=>'No dates were selected in the reservation. Please check the information and try again.',
			'addGuests'=>'Please enter a number for Additional Guests in the space provided',
			'noGuests'=>'No guests were entered. Please try again.',
			'frsInvalid'=>'The KFS Number you supplied is invalid. Please try again.',
			'subObjInvalid'=>'The KFS Sub Acct., or Sub Obj. Code that you supplied is invalid. Please try again.'
		);

		return $errors[$error] ?? "An unknown error occurred. Please try again or contact PTS Visitor Programs at $this->phone.";
	}
}