<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Database\database;
use App\Application\Responders\CustomerResponder;

class GetCustomerViewAction extends CustomerAction
{
    private CustomerResponder $customerResponder;

    public function __construct(CustomerResponder $customerResponder) {
        $this->customerResponder = $customerResponder;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $data = [
            'reservations' => []
        ];
        $viewName = '';
        $dbConn = new database();

        $viewNames = array(
            "normal"=>"Upcoming Active Reservations",
            "cancelled"=>"Cancelled Reservations",
            "past"=>"Past Reservations (12 Mos.)",
            'search'=>"Search Reservations"
        );
        $order = "RES_DATE DESC";

        if (isset($_GET['resConf'])) {
            // resConf is from create.php
            $viewType = 'xxxxxxxxxx';
            $viewName = '';

        } else if (isset($_GET['view']) && isset($viewNames[$_GET['view']])) {
            if (ctype_alnum($_GET['view']))
                $viewType = $_GET['view'];
            else
                $viewType = "normal";
            $viewName = $viewNames[$viewType];
        } else {

            $viewType = "normal";
            $viewName = $viewNames[$viewType];
        }

        $qVarsA  = array();
        $viewSql = '';
        $viewAllMsg = '';

        if (isset($_GET['searchString']) && !isset($_POST['searchString'])) {
            $_POST['searchString'] = urldecode($_GET['searchString']);
            $_POST['searchType'] = urldecode($_GET['searchType']);
            $_POST['sh_DEPT_NO_FK'] = urldecode($_GET['sh_DEPT_NO_FK']);
            $_POST['sh_USER_NAME'] = urldecode($_GET['sh_USER_NAME']);
        }

        if (isset($_GET['resConf'])) {
            $viewSql = "ACTIVE=111111111111111111"; // whoregarble

        } else if ($viewType=="cancelled") {

            $viewSql = "ACTIVE=0";
            if (!isset($_GET['viewAllCanceled']))
                $oneYearAgo = new \DateTime();
                $oneYearAgo->modify('-1 year');
                $viewSql .= " AND RES_DATE > TO_DATE('".$oneYearAgo->format('m/d/Y')."','MM/DD/YYYY')";
            $viewAllMsg = '<br><div align="left"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="?view=cancelled&viewAllCanceled=1" style="font-weight:bold;">View All Canceled</a></div>';

        } elseif ($viewType=="past") {
            //jody 8/22/2013 2000 records if DEPT_NO_FK IN (08905)  	$viewSql = "ACTIVE=1 AND RES_DATE < SYSDATE";
            $date_tomorrow = new \DateTime();
            $date_year_ago = new \DateTime();
            $date_tomorrow->modify("+1 day");
            $date_year_ago->modify("-1 year");
            $viewSql = "ACTIVE=1 AND RES_DATE between to_date('".$date_tomorrow->format('m/d/Y')."', 'mm/dd/yy') and to_date('".$date_year_ago->format("m/d/Y")."', 'mm/dd/yy')";
        } elseif ($viewType=='search' && isset($_POST['searchString'])) {

            $_POST['searchString'] = strtolower($_POST['searchString']);
            $searchString = $_POST['searchString'];
            // get guests
            if ($_POST['searchType']=='GUEST_NAME') {

                //$searchString = stripslashes(strtolower(str_replace("'","''",$_POST['searchString'])));
                //$dbConn->query("SELECT RESERVATION_ID_FK FROM PARKING.GR_GUEST WHERE LOWER(GUEST_NAME) LIKE '%$searchString%'");
                $query = "SELECT RESERVATION_ID_FK FROM PARKING.GR_GUEST WHERE LOWER(GUEST_NAME) LIKE :searchString";
                $qVarsB = array('searchString'=>"%$searchString%");
                $dbConn->sQuery($query, $qVarsB);

                if ($dbConn->rows)
                    $viewSql = "RESERVATION_ID IN (".implode(",",array_unique($dbConn->results['RESERVATION_ID_FK'])).")";
                else
                    $viewSql = 'RESERVATION_ID=0';
            } elseif ($_POST['searchType']=='RES_DATE') {
                // error checking
                if (!preg_match("/[0-9]{1,2}(\/|\-)[0-9]{1,2}(\/|\-)20[0-9]{2}/",stripslashes($searchString))) {
                    $viewSql = 'RESERVATION_ID=0';
                    $errorMsg = 'Date formatted incorrectly (MM/DD/YYYY)';
                }	else {
                    // replace dashes with slashes for a dash-slash party
                    $searchString = str_replace('-','/',$searchString);

                    //$searchString = $dbConn->format($searchString,true,false);
                    //$viewSql = "RES_DATE=TO_DATE($searchString,'MM/DD/YYYY')";
                    $viewSql .= "RES_DATE=TO_DATE(:sq_RES_DATE,'MM/DD/YYYY')";
                    $qVarsA['sq_RES_DATE'] = $dbConn->sFormat($searchString,true,false);
                }
            } elseif ($_POST['searchType']=='FRS_FK') {

                $viewSql .= "FRS_FK=:sq_FRS_FK";
                $qVarsA['sq_FRS_FK'] = $dbConn->sFormat(strtolower($searchString),true,true,255);
            }

            // admin searches for
                // dept no
            if (isset($_POST['sh_DEPT_NO_FK']) && $_POST['sh_DEPT_NO_FK']) {
                if ($viewSql) // this should always be true, this is just some insane insanity check I did.
                    $viewSql .= ' AND ';
                //$viewSql .= "DEPT_NO_FK=".$dbConn->format($_POST['sh_DEPT_NO_FK'],true,false,10);
                $viewSql .= "DEPT_NO_FK=:sq_DEPT_NO_FK";
                $qVarsA['sq_DEPT_NO_FK'] = $dbConn->sFormat($_POST['sh_DEPT_NO_FK'],true,false,10);
            }
                // user name
            if (isset($_POST['sh_USER_NAME']) && $_POST['sh_USER_NAME']) {
                if ($viewSql) // this should always be true, this is just some insane insanity check I did.
                    $viewSql .= ' AND ';
                //$viewSql .= "LOWER(U.USER_NAME) LIKE '%".stripslashes(strtolower(str_replace("'","''",$_POST['sh_USER_NAME'])))."%'";
                $viewSql .= "LOWER(U.USER_NAME) LIKE :sq_u_n";
                $qVarsA['sq_u_n'] = "%" . stripslashes(strtolower($_POST['sh_USER_NAME'])) . "%";
            }
        } elseif ($viewType=='normal') {
            $viewSql = "ACTIVE=1 AND TRUNC(RES_DATE)>=TRUNC(SYSDATE)";
            $order = "RES_DATE ASC";
        }

        // Queries ready, run them, we need to return the following: reservations (if any)
        $customer = $_SESSION['cuinfo'];
        $query = "SELECT RESERVATION_ID, TO_CHAR(RES_DATE,'MM/DD/YYYY') AS RESDATE, TO_CHAR(ENTER_TIME,'HH:MI AM') AS ENTERTIME,
                        TO_CHAR(EXIT_TIME,'HH:MI AM') AS EXITTIME, ACTIVE, USER_ID_FK, GARAGE_NAME,
                        (SELECT MAX(GROUP_SIZE) FROM PARKING.GR_GUEST V WHERE V.RESERVATION_ID_FK=R.RESERVATION_ID) AS SPACES,
                        PRICE, USER_NAME, FRS_FK
                    FROM PARKING.GR_RESERVATION R INNER JOIN PARKING.GR_USER U ON R.USER_ID_FK=U.USER_ID
                    INNER JOIN PARKING.GR_GARAGE G ON R.GARAGE_ID_FK=G.GARAGE_ID
                    WHERE
                    ";
        $validSql = false;
        if (isset($_POST['searchString']) && isset($_SESSION['cuinfo']['auth']) && $_SESSION['cuinfo']['auth']>2) {
            $query .= '1=1 ';
            $validSql = true;
        } else {
            $query .= "DEPT_NO_FK IN (";
            $tmpQvar = '';
            foreach ($customer['deptno'] as $key=>$val) {
                $validSql = true;
                if ($tmpQvar) $query .= ",";
                $tmpQvar = "qv_$key";
                $query .= ":$tmpQvar";
                $qVarsA[$tmpQvar] = $val;
            }
            $query .= ") ";
        }
        if ($viewSql)  $query .= "AND $viewSql ";
        $query .= "ORDER BY $order";

        if ($validSql)
            $dbConn->sQuery($query, $qVarsA);

        // A quick pivot?
        $records = array();
        if($dbConn->results) {
		foreach ($dbConn->results as $key=>$vals) {
			foreach ($vals as $i=>$val) {
				$records[$i][$key] = $val;
			}
		}
        }
        $data['reservations'] = $records;
        return $this->customerResponder->index($this->response, $data);
    }
}
