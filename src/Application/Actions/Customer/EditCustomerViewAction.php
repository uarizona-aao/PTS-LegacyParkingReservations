<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Database\database;
use App\Application\Responders\CustomerResponder;
use App\Infrastructure\Database\reservation;
use App\Application\Services\DateValidator;

include_once __DIR__.'/../../../form_functions.php';

class CreateCustomerViewAction extends CustomerAction
{
    private CustomerResponder $customerResponder;
    private DateValidator $dateValidator;

    public function __construct(
        CustomerResponder $customerResponder,
        DateValidator $dateValidator
    ) {
        $this->customerResponder = $customerResponder;
        $this->dateValidator = $dateValidator;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        
        return $this->customerResponder->edit_customer($this->response);
    }
}
