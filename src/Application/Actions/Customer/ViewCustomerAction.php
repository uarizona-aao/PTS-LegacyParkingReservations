<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Responders\CustomerResponder;
use App\Infrastructure\Database\reservation;
use App\Infrastructure\Database\database;

class ViewCustomerAction extends CustomerAction
{
    private CustomerResponder $customerResponder;

    public function __construct(CustomerResponder $customerResponder)
    {
        $this->customerResponder = $customerResponder;
    }

    protected function action(): Response
    {
        $id = $this->request->getQueryParams()['id'] ?? null;
        
        if (!$id) {
            return $this->response->withStatus(404);
        }

        $res = new reservation();
        $customer = $_SESSION['cuinfo'];
        $res->getRes($id);

        if (!$res->resinfo) {
            return $this->response->withHeader('Location', '/?msg=resnotfound')
                                ->withStatus(302);
        }

        $tmpAry = [
            'deptno' => $res->resinfo['DEPT_NO_FK'][0], 
            'userid' => $res->userid
        ];

        if ($customer['auth'] < 3) {
            $res->checkResOwner($customer, $tmpAry);
        }

        return $this->customerResponder->confirmation($this->response, [
            'reservation' => $res,
            'receipt' => isset($_GET['action']) && $_GET['action'] == 'receipt',
            'pdfConfirmFile' => $_GET['pdfConfirmFile'] ?? null,
            'auth' => $customer['auth'],
            'can_edit' => $this->canEdit($res),
            'can_cancel' => $this->canCancel($res),
            'can_revive' => $this->canRevive($res),
            'gg' => (isset($res->groupCount[0]) && $res->groupCount[0] > 1) ? "group" : "guest",
            'garage_text' => $this->formatGarageText($res->garageName),
            'history' => $this->getReservationHistory($id),
            'back_url' => $this->generateBackUrl()
        ]);
    }

    private function canEdit($res): bool 
    {
        $auth = $_SESSION['cuinfo']['auth'] ?? 0;
        return ($auth >= 4 || $res->owner) && 
               strtotime($res->resdate) >= strtotime('today');
    }

    private function canCancel($res): bool 
    {
        $auth = $_SESSION['cuinfo']['auth'] ?? 0;
        return ($res->owner && $res->canCancel([$res->resdate])) || $auth >= 4;
    }

    private function canRevive($res): bool 
    {
        $auth = $_SESSION['cuinfo']['auth'] ?? 0;
        return !$res->active && $auth >= 4 && 
               strtotime($res->resdate) >= strtotime('today');
    }

    private function formatGarageText($garageName): string 
    {
        if (preg_match('/(BioMedical)/i', $garageName)) {
            $pbc_lot_num = preg_match('/(10003)/i', $garageName) ? '10003' : '10002';
            $pbc_lot_loc = ($pbc_lot_num == '10003') 
                ? "Lot 10003, Located at 550 E Van Buren, 85004" 
                : "Lot 10002, Located at 714 E Van Buren, 85004";
            return "Phoenix BioMedical Campus <a href='https://parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf' target='_blank'>{$pbc_lot_loc}</a>";
        }
        return $garageName;
    }

    private function getReservationHistory($resId): array 
    {
        $dbConn = new database();
        $query = "SELECT N.*, TO_CHAR(DATE_RECORDED,'MM-DD-YY HH:MI AM') AS DATERECORDED, U.USER_NAME 
                  FROM PARKING.GR_RESERVATION_NOTE N 
                  INNER JOIN PARKING.GR_USER U ON USER_ID_FK=USER_ID 
                  WHERE RESERVATION_ID_FK = :resid 
                  ORDER BY DATE_RECORDED DESC";
        $dbConn->sQuery($query, ['resid' => $resId]);
        return $dbConn->results !== false ? $dbConn->results : [];
    }

    private function generateBackUrl(): string 
    {
        $params = [
            'view', 'searchString', 'searchType', 
            'sh_DEPT_NO_FK', 'sh_USER_NAME'
        ];
        $queryString = [];
        foreach ($params as $param) {
            if (isset($_GET[$param])) {
                $queryString[] = $param . '=' . urlencode($_GET[$param]);
            }
        }
        return '/' . ($queryString ? '?' . implode('&', $queryString) : '');
    }
}