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
}