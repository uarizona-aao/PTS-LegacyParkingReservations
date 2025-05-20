<?php
namespace App\Application\Responders;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class CustomerResponder {
    protected $view;

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
}