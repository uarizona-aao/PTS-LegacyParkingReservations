<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Database\database;
use App\Application\Responders\CustomerResponder;
use App\Infrastructure\Database\reservation;

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
        $data = [
            'receipt' => '', // Content for receipt
            'error' => '',
        ];
        $customer = $_SESSION['cuinfo'];
        $userid   = $customer['userid'];

        if (@$_SESSION['resConfirmed'])
        {
            // $_SESSION['resConfirmed'] set and then un-set below, so as to take care of possible Back button problem in browser.
            unset($_SESSION['resConfirmed']);
        }

        self::stripBadChars();
        // done
        if (isset($_GET['res']))
        {
            $data['receipt'] = generateResReceipt($_GET['res']);
        }
        // actually confirmed order
        elseif (@$_POST['confirm'] && trim($_POST['garage']))
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
            $res->newRes($_POST['frs'], $_POST['KFS_SUB_ACCOUNT_FK'], $_POST['KFS_SUB_OBJECT_CODE_FK'], $customer, $_POST['garage'], $dates, $_POST['enterTime'], $_POST['exitTime'], $_POST['groupGuest'], $option1, $option2, $comeGo, isChecked("allowExtra","1","0"), $_POST[$addGuests]);
            $_SESSION['resConfirmed'] = 0;

            if ($res->error) {
                // TODO figure this out
                // $errMsg = $res->errorOut($res->error,$res->errordate);
                // $resInfo = array();
                // $glg = '';
                // massagePost($resInfo, $glg, true);
                // $cancelUri = 'index.php';
                // include_once 'resform.php'; 
            }
            elseif ($res->conf) {
                // TODO redirect to 
                // locationHref('/parking/garage-reservation/view.php?action=receipt&id='.$res->conf.'&pdfConfirmFile='.$pdfConfirmFile);
            } else {
                // this 
                $data['error'] = $res->errorOut("noConf");
            }
        // We submitted the initial resform...
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

            // TODO generate FORM somehow? Move it to customer_create?
        } else {
            // Generate the basic submit form

            // $resInfo = array();
            // $glg = '';
            // massagePost($resInfo, $glg);
            // $cancelUri = "index.php";
            // // Resform html stuff here...
            // include_once 'resform.php';
        }
    
        return $this->customerResponder->create($this->response, $data);
    }

    public static function stripBadChars() {
        if (@$_POST['guestList'])
            $_POST['guestList'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['guestList']);
        if (@$_POST['guestName'])
            $_POST['guestName'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['guestName']);
        if (@$_POST['laddGuests'])
            $_POST['laddGuests'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['laddGuests']);
        if (@$_POST['groupName'])
            $_POST['groupName'] = preg_replace('/[^ \d\w]/i', '', $_POST['groupName']);
        if (@$_POST['spaces'])
            $_POST['spaces'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['spaces']);
        if (@$_POST['gaddGuests'])
            $_POST['gaddGuests'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['gaddGuests']);
    }

    public static function massagePost(&$resInfo, &$glg, $change=0) {
        if (isset($change)) {
            array_walk($_POST, "fixPost");
            $resInfo = array(
                "FRS_FK"=>$_POST['frs'],
                "KFS_SUB_ACCOUNT_FK"=>$_POST['KFS_SUB_ACCOUNT_FK'],
                "KFS_SUB_OBJECT_CODE_FK"=>$_POST['KFS_SUB_OBJECT_CODE_FK'],
                "RESDATE"=>$_POST['startDate'],
                "RESSTART"=>$_POST['enterTime'],
                "RESEND"=>$_POST['exitTime'],
                "GARAGE_ID_FK"=>$_POST['garage']
            );
            $glg = $_POST['groupGuest'];
            if ($glg=="group") {
                $resInfo['GUEST_NAME'] = $_POST['groupName'];
                $resInfo['GROUP_SIZE'] = $_POST['spaces'];
                $resInfo['GUESTS_OFFCAMPUS'] = $_POST['gaddGuests'];
            }
            else {
                $resInfo['guestList'] = $_POST['guestList'];
                $resInfo['GUESTS_OFFCAMPUS'] = $_POST['laddGuests'];
            }
            if (isset($_POST['allowExtra'])) $resInfo['ALLOW_EXTRA'] = 1;
            if (isset($_POST['comeGo'])) $resInfo['COME_AND_GO'] = 1;
            if (isset($_POST['gcomeGo'])) $resInfo['COME_AND_GO'] = 1;
        }
    
        else {
            $glg = "guest";
        }
    }
}
