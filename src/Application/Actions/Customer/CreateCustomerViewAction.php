<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Database\database;
use App\Application\Responders\CustomerResponder;
use App\Infrastructure\Database\reservation;

include_once __DIR__.'/../../../form_functions.php';

class CreateCustomerViewAction extends CustomerAction
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
        include_once __DIR__.'/../../../form_functions.php';
        $customer = $_SESSION['cuinfo'];
        $userid   = $customer['userid'];
        $redDates = isset($resInfo['RESDATE']) ? $resInfo['RESDATE'] : '';
        $defaultDateStr = $redDates ? explode(',', $redDates)[0] : '';
        $addDatesStr = $redDates ? implode(',', array_map(fn($date) => "'$date'", explode(',', $redDates))) : '';

        $data = [
            'receipt' => '', // Content for receipt
            'error' => '',
            'mode' => 'create',
            'customer' => $customer,
            'reservation' => [],
            'db_reservation' => [], // this is for $res object if we instantiate it.
            'redDates' => $redDates,
            'defaultDateStr' => $defaultDateStr,
            'addDatesStr' => $addDatesStr,
            'maxDatePicks' => 4,
            'unselectDateMsg' => "Please unselect the date you wish to change.",
            'use_default_jquery' => false, // bit for jquery fix.
            'garageOptions' => [],
        ];


        if (isset($_SESSION['resConfirmed']))
        {
            // $_SESSION['resConfirmed'] set and then un-set below, so as to take care of possible Back button problem in browser.
            unset($_SESSION['resConfirmed']);
        }

        self::stripBadChars();
        // done
        if (isset($_GET['res']))
        {
            $data['receipt'] = self::generateResReceipt($_GET['res']);
        }
        // actually confirmed order and ready to submit.
        elseif (isset($_POST['confirm']) && trim($_POST['garage'] ?? ''))
        {
            // option handling
            if ($_POST['groupGuest']=="group") {
                $option1 = array($_POST['groupName']);
                $option2 = $_POST['spaces'];
                $comeGo = isChecked("gcomeGo","1","0");
                $addGuests = "gaddGuests";
            } else {
                $option1 = explode(" | ",$_POST['guestList']);
                $option2 = NULL;
                $comeGo = isChecked("comeGo","1","0");
                $addGuests = "laddGuests";
            }

            if ($_POST['dates']) { $dates = explode(",",$_POST['dates']); }
            $res = new reservation();
            $pdfConfirmFile = '';

            $_SESSION['resConfirmed'] = 1;

            // Create reservations and send confimration emails.
                $x = $res->newRes(
                    $_POST['frs'], 
                    $_POST['KFS_SUB_ACCOUNT_FK'], 
                    $_POST['KFS_SUB_OBJECT_CODE_FK'], 
                    $customer, 
                    $_POST['garage'], 
                    $dates, 
                    $_POST['enterTime'], 
                    $_POST['exitTime'], 
                    $_POST['groupGuest'], 
                    $option1, 
                    $option2, 
                    $comeGo, 
                    isChecked("allowExtra","1","0"), 
                    $_POST[$addGuests] ?? ''); //,
                    // true);
                $_SESSION['resConfirmed'] = 0;

                if ($res->error) {
                    return $this->customerResponder->create($this->response, [
                        'error' => $res->errorOut($res->error, $res->errordate),
                        'reservation' => $resInfo ?? [],
                        'glg' => $glg ?? '',
                        'cancelUri' => 'index.php'
                    ]);
                }
                elseif ($res->conf) {
                    // TODO redirect to 
                    // locationHref('/parking/garage-reservation/view.php?action=receipt&id='.$res->conf.'&pdfConfirmFile='.$pdfConfirmFile);
                    return $this->customerResponder->confirmation($this->response, [
                        'reservation' => $res,
                        'receipt' => true,
                        'gg' => $_POST['groupGuest'] ?? 'guest',
                        'pdfConfirmFile' => $pdfConfirmFile ?? null,
                        'auth' => $customer['auth'],
                        'can_edit' => true,
                        'garage_text' => getGarageByID($_POST['garage']),
                    ]);
                } else {
                    return $this->customerResponder->create($this->response, [
                        'error' => $res->errorOut("noConf"),
                        'reservation' => $resInfo ?? [],
                        'glg' => $glg ?? '',
                        'cancelUri' => 'index.php'
                    ]);
                }

        // We submitted the initial resform and are doing checks...
        } elseif (isset($_POST['reserve']) || isset($_POST['reserve_x'])) {

            //================= confirmation and agreement ===================
            array_walk($_POST,"fixPost");
            $dates = explode(",",$_POST['dates']);
    
            $error = false;
            if ($customer['auth']<3) {
                if ($_POST['spaces']>25)
                    $error = 'maxSpaces';
                if (in_array(date("m/d/Y"),$dates) || in_array(date("n/j/Y"),$dates) || in_array(date("m/j/Y"),$dates) || in_array(date("n/d/Y"),$dates))
                    $error = 'today';
            }
    
            $get_p = '';
            if (isset($_GET['id']) && ctype_digit($_GET['id']) && !strpos($_SERVER['HTTP_REFERER'],'?id=')) {
                $get_p = '?id='.$_GET['id'];
            }

            $reservationData = [
                'frs' => $_POST['frs'],
                'kfs_sub_account_fk' => $_POST['KFS_SUB_ACCOUNT_FK'],
                'kfs_sub_object_code_fk' => $_POST['KFS_SUB_OBJECT_CODE_FK'],
                'dates' => explode(',', $_POST['dates']),
                'enter_time' => $_POST['enterTime'],
                'exit_time' => $_POST['exitTime'],
                'garage_name' => getGarageByID($_POST['garage']),
                'group_guest' => $_POST['groupGuest'],
                'group_name' => $_POST['groupName'] ?? null,
                'spaces' => $_POST['spaces'] ?? null,
                'guest_list' => isset($_POST['guestList']) ? explode(' | ', $_POST['guestList']) : [],
            ];

            $postData = $_POST;
            array_walk($postData, function (&$val, $key) {
                $val = str_replace('"', "''", stripslashes($val));
            });
            
            return $this->customerResponder->agreement($this->response, [
                'reservation' => $reservationData,
                'post_data' => $postData
            ]);
        } else {
            // Generate the basic submit form when you start
            $resInfo = array();
            $glg = '';
            self::massagePost($resInfo, $glg);
            $data['reservation'] = $resInfo;
            $data['glg'] = $glg ?? 'guest'; // Default to 'guest' if not set
            $data['guestList'] = isset($resInfo['guestList']) ? explode(' | ', $resInfo['guestList']) : [];
            $data['groupName'] = $resInfo['GUEST_NAME'] ?? '';
            $data['groupSize'] = $resInfo['GROUP_SIZE'] ?? '';
            $data['garageOptions'] = garageOptions(getVal($resInfo, 'GARAGE_ID_FK', 0), "9006,USA,10003");
            // Return the basic form for order creation
            return $this->customerResponder->create($this->response, $data);
        }
    }

    public static function generateResReceipt($conf) {
        // instantiate the class
        $res = new reservation();
        // get the reservation info
        $res->getRes($conf);
        // get all of the class vars into a local array
        $resInfo = @get_object_vars($res);
        $return = "";
        // if it worked (if not a class, it won't work)
        if (is_array($resInfo)) {
            // receipt header
            echo '<div style="text-align:center; margin-top:20px;"><div style="margin:0 auto; text-align:left; width:800px; padding:20px; border:solid 5px #CCCCCC;">';
            // never finished this, but it runs through the vars and displays them on a receipt
            foreach ($res->resTranspose as $key=>$val) {
                if (isset($resInfo[$key]) && $resInfo[$key]) {
                    echo "<b>$val:</b> ";
                    if (is_array($resInfo[$key])) implode($resInfo[$key]);
                    elseif ($key=="allowextra" || $key=="comego") {
                        if ($resInfo[$key]) echo "Yes";
                        else echo "No";
                    }
                    else echo $resInfo[$key];
                    echo "<br/>\n";
                }
            }
            // receipt footer
            echo "</div></div>\n";
        }
        return $return;
    }

    public static function stripBadChars() {
        if (isset($_POST['guestList']))
            $_POST['guestList'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['guestList']);
        if (isset($_POST['guestName']))
            $_POST['guestName'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['guestName']);
        if (isset($_POST['laddGuests']))
            $_POST['laddGuests'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['laddGuests']);
        if (isset($_POST['groupName']))
            $_POST['groupName'] = preg_replace('/[^ \d\w]/i', '', $_POST['groupName']);
        if (isset($_POST['spaces']))
            $_POST['spaces'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['spaces']);
        if (isset($_POST['gaddGuests']))
            $_POST['gaddGuests'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['gaddGuests']);
    }

    public static function massagePost(&$resInfo, &$glg, $change=0) {
        if (isset($change)) {
            array_walk($_POST, "fixPost");
            $resInfo = array(
                "FRS_FK"=>$_POST['frs'] ?? null,
                "KFS_SUB_ACCOUNT_FK"=>$_POST['KFS_SUB_ACCOUNT_FK'] ?? null,
                "KFS_SUB_OBJECT_CODE_FK"=>$_POST['KFS_SUB_OBJECT_CODE_FK'] ?? null,
                "RESDATE"=>$_POST['startDate'] ?? null,
                "RESSTART"=>$_POST['enterTime'] ?? null,
                "RESEND"=>$_POST['exitTime'] ?? null,
                "GARAGE_ID_FK"=>$_POST['garage'] ?? null,
            );
            $glg = $_POST['groupGuest'] ?? $glg;
            if ($glg=="group") {
                $resInfo['GUEST_NAME'] = $_POST['groupName'] ?? '';
                $resInfo['GROUP_SIZE'] = $_POST['spaces'] ?? 0;
                $resInfo['GUESTS_OFFCAMPUS'] = $_POST['gaddGuests'] ?? '';
            }
            else {
                $resInfo['guestList'] = $_POST['guestList'] ?? null;
                $resInfo['GUESTS_OFFCAMPUS'] = $_POST['laddGuests'] ?? null;
            }
            if (isset($_POST['allowExtra'])) $resInfo['ALLOW_EXTRA'] = 1;
            if (isset($_POST['comeGo'])) $resInfo['COME_AND_GO'] = 1;
            if (isset($_POST['gcomeGo'])) $resInfo['COME_AND_GO'] = 1;
        }
    
        else {
            $glg = "guest";
        }
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
        return $dbConn->results ?? [];
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
        return 'index.php' . ($queryString ? '?' . implode('&', $queryString) : '');
    }
}
