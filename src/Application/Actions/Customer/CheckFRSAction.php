<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Database\database;
use App\Application\Actions\Action;

class CheckFRSAction extends Action
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $queryParams = $this->request->getQueryParams();
        $frs = $queryParams['frs'] ?? '';
        $cust = $queryParams['cust'] ?? '';

        // Validate FRS format
        if (!preg_match('/^\w{5,}$/', $frs)) {
            return $this->respondWithData([
                'status' => 'error',
                'message' => 'KFS must be 5+ characters.'
            ], 400);
        }

        $dbConn = new database();
        $qVars = ['frs' => $frs];
        $goodKFS = $goodUser = false;

        // Check if the KFS exists
        $query = "SELECT USER_ID
                  FROM PARKING.GR_USER 
                  INNER JOIN (PARKING.GR_USER_DEPARTMENT UD 
                  INNER JOIN PARKING.GR_FRS F ON UD.DEPT_NO_FK=F.DEPT_NO_FK) 
                  ON UD.USER_ID_FK=USER_ID
                  WHERE FRS=:frs";
        $dbConn->sQuery($query, $qVars);
        if ($dbConn->rows) {
            $goodKFS = true;
        }

        // Check if the customer ID is valid for the KFS
        if ($goodKFS) {
            $qVars = ['frs' => $frs, 'cust' => $cust];
            $query = "SELECT USER_ID
                      FROM PARKING.GR_USER 
                      INNER JOIN (PARKING.GR_USER_DEPARTMENT UD 
                      INNER JOIN PARKING.GR_FRS F ON UD.DEPT_NO_FK=F.DEPT_NO_FK) 
                      ON UD.USER_ID_FK=USER_ID
                      WHERE FRS=:frs AND USER_ID=:cust";
            $dbConn->sQuery($query, $qVars);
            if ($dbConn->rows) {
                $goodUser = true;
            }
        }

        $dbConn->disConnect();

        // Prepare the response
        if ($goodUser) {
            return $this->respondWithData([
                'status' => 'success',
                'message' => 'KFS is valid.'
            ]);
        } elseif ($goodKFS) {
            return $this->respondWithData([
                'status' => 'error',
                'message' => 'ERROR: INVALID customer ID.'
            ], 400);
        } else {
            return $this->respondWithData([
                'status' => 'error',
                'message' => 'ERROR: KFS# was not recognized. Check to see if it was entered correctly. Contact (520) 621-3300 to add the KFS# to the system.'
            ], 400);
        }
    }
}
