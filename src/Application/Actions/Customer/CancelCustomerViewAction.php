<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Responders\CustomerResponder;
use App\Infrastructure\Database\reservation;

class CancelCustomerViewAction extends CustomerAction
{
    private CustomerResponder $customerResponder;

    public function __construct(CustomerResponder $customerResponder)
    {
        $this->customerResponder = $customerResponder;
    }

    protected function action(): Response
    {
        $base_url = $_ENV['APP_URL'] ?? '';
        $id = $this->request->getQueryParams()['id'] ?? null;
        
        if (!$id) {
            return $this->response->withHeader('Location', '/?msg=noselect')
                                ->withStatus(302);
        }

        $customer = $_SESSION['cuinfo'];
        $resObj = new reservation();
        
        // Handle POST for cancellation
        if ($this->request->getMethod() === 'POST') {
            $resObj->getRes($id);
            $testOwner = $resObj->checkMultiResOwner($customer['userid'], [$id]);
            
            if (!count($testOwner['fail']) && count($testOwner['pass'])) {
                // Check if biomedical garage
                if (preg_match('/bio.?med/si', $resObj->garageName)) {
                    return $this->response->withHeader('Location', "$base_url/?msg=nopbc")
                                        ->withStatus(302);
                }
                
                $resObj->cancelRes($testOwner['pass'], $customer['userid']);
                return $this->response->withHeader('Location', "$base_url/?msg=multicancel")
                                    ->withStatus(302);
            }
            
            return $this->response->withHeader('Location', "$base_url/?msg=notallowed")
                                ->withStatus(302);
        }

        // Handle GET - show confirmation form
        $testOwner = $resObj->checkMultiResOwner($customer['userid'], [$id]);
        return $this->customerResponder->cancel($this->response, [
            'id' => $id,
            'can_cancel' => count($testOwner['pass']) > 0
        ]);
    }
}