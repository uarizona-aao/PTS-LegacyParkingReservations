<?php
namespace App\Infrastructure\Database\Flowbird;
// require_once('/var/www2/include/flowbird-include/databasemodel.php');
// require_once('/var/www2/include/flowbird-include/flowordermodel.php');
// require_once('/var/www2/include/flowbird-include/notificationcontroller.php');
// require_once('/var/www2/include/flowbird-include/flowsettingsmodel.php');

class ReservationController

{


    protected $settings;
    protected $rm;
    protected $nc;
    protected $mailer;
 function __construct()
 {
     $this->rm = new FloworderModel();
 }
    public function processFlowbirdReservation($package) {
        $set=new FlowsettingsModel();
        $this->settings=$set->getSettings();
        $returnParams=new \stdClass();




        $availableTickets=$this->rm->checkForAvailableCodes($package);
        $response=new \stdClass();
        if ($package->FBNUMBEROFTICKETS>$availableTickets) {
            $response->status="fail";
                $returnParams->orderSuccessful=false;
                $returnParams->ticketsAvalable=$availableTickets;
                $response->message=$this->getTicketsNotAvailabelResponse($package);
        } else {
            $this->mailer=new NotificationController();
                $package->numberOfCodes=$availableTickets;
                $package->ticketCodes=$this->rm->getTicketCodes($package);

                $returnParams->orderSuccessful=true;
                $returnParams->ticketsAvalable=$package->FBNUMBEROFTICKETS;

                $package->orderId=$this->rm->createReservationOrder($package);
                $package->codes=$this->rm->reserveTicketCodes($package);
                $mailResponse=$this->sendReservationOrder($package->orderId);
                $response->status="success";
                 $codesLeft=$availableTickets-$package->FBNUMBEROFTICKETS;
            if ($codesLeft<=$this->settings->INVENTORYLOWTHRESHOLD) {
                $package->REMAININGAVAILALBECODES=$codesLeft;
                $package->TOEMAIL=$this->settings->INVENTORYALERTEMAIL;
                $emailInfo=$this->mailer->lowCodeInventoryNotification($package);
                $this->mailer->sendEmail($emailInfo->fromName, $emailInfo->fromEmail, $emailInfo->toEmail, $emailInfo->emailBody, $emailInfo->subject);
            }
        }
        $notificationRecipiants=$this->settings->RESERVATIONNOTIFICAIONEMAIL;
        return $notificationRecipiants;
    }

    protected function sendReservationOrder($orderId) {
        $order=$this->rm->retrieveReservationOrder($orderId);
        $order->ticketCodes=$this->rm->getOrderTicketCodes($orderId);
        $emailInfo=$this->mailer->flowbirdReservationTicketOrderNotification($order);
        $this->mailer->sendEmail($emailInfo->fromName, $emailInfo->fromEmail, $emailInfo->toEmail, $emailInfo->emailBody, $emailInfo->subject);
        return true;
    }

    private function getTicketsNotAvailabelResponse() {
        $msg="";
        $msg="Codes not available";
        return $msg;
    }

     public function lookupkfc($package)
     {
         $kfsNumber = $package->KFSNUMBER;
         $kfsInfo = $this->rm->getKFSinfo($kfsNumber);
         $result = new \stdClass();
         $result->result = ($kfsInfo->found === true) ? "found" : "notfound";
         $result->DEPARTMENTNAME = $kfsInfo->DEPARTMENTNAME;
         echo json_encode($result);
         exit;
     }

    public function getKFSInformation($kfsNumber)
    {
        return $this->rm->getKFSinfo($kfsNumber);
    }
}
