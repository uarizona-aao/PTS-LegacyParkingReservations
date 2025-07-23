<?php
namespace App\Infrastructure\Database\Flowbird;
/*
 * fNotificationController - assemble order and ticket email notifications
 * Version 1.0.
 *
 * https://parking.arizona.edu/include/flowbird-includes/ -- FloworderController production
 *
 * @author    David Ross Wallace <davidwallace@arizona.edu>
 * @copyright 2023 - 2023 David Ross Wallace
 */


class NotificationController
{
    protected $settings;

    function __construct()
    {
     //   $this->set = new FlowsettingsModel();
     //   $this->settings=$this->set->getSettings();
    }

    public function sendNotification($emailInfo)
    {
        $this->sendEmailWithCC("PTS Phoenix", $emailInfo->fromEmail, $emailInfo->cc, $emailInfo->toEmail, $emailInfo->emailBody, $emailInfo->subject);
    }

    protected function sendEmailWithCC($fromName, $fromEmail, $cc, $toEmail, $text, $subject)
    {
        $from = "From: " . $fromName . " <" . $fromEmail . ">\r\nCc: " . $cc . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
        mail($toEmail, $subject, $text, $from);
    }

    public function sendEmail($fromName, $fromEmail, $toEmail, $text, $subject)
    {
        $from = "From: " . $fromName . " <" . $fromEmail . ">\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
        mail($toEmail, $subject, $text, $from);
    }

    // private function sendCodeEmail($customer, $stats)
    // {
    //     $body = $this->getEmailBody($customer, $stats);

    //     $subject = "Your Night Cat By Lyft Ride Code : UA Parking & Transportation";
    //     $this->sendEmailWithCC("PTS Night Cat By Lyft", $this->fromEmail, $customer->email, $body, $subject);
    // }


    public  function flowbirdTicketOrderNotification($package) {

        $notInfo = new \stdClass();
        $notInfo->subject = "Flowbird Order Receipt: Validations Codes Included";
        $notInfo->toEmail =  $package->CUSTOMEREMAIL;
        $notInfo->fromEmail ='PTS-Information@email.arizona.edu';
        $notInfo->fromName='UofA Parking & Transportation';
       // $notInfo->cc ="";
        $codeTable=$this->tablelizePasscodes($package->ticketCodes);
        $notInfo->emailBody = "RE: <strong>Flowbird Order Receipt: Validations Codes Included</strong><br><br>
      <p>Greetings $package->CUSTOMERNAME,   
				  <br></p><p>
				Thank you for your purchase.   
				<br></p><p>
				Validation codes are required for all main campus parking garages where there are no gate arms at the entry or exit. To use this code, customers must use the Flowbird Kiosk located near the garage booth, or the elevator on the top floor of Health Sciences Garage.
				<br></p><p>
				<b>Please provide to your guest:
				<ul>
				<li>Selected Parking Garage</li><li>Validation code(s)</li>
				<li><a href='https://parking.arizona.edu/flowbird/validation-code-instructions.pdf' title='Gateless Garage Code Redemption Instructions'>Guest Validation Code Instructions</a></li>
				</ul></b>
				</p>
				Below is your FLowbird ticket order information and list of Flowbird validation codes:  
			 
				 <br>  <br> <hr><br>
				 <h2>Order Receipt:</h2>
				 <p style='font-size:18px'>
				Order#:&nbsp;<b>$package->FBORDERID </b> <br> 
				Order Date:&nbsp;<b>$package->ORDERDATE </b><br> 
				KFSNUMBER:&nbsp;<b>$package->KFSNUMBER </b><br> 
				Department:&nbsp;<b>$package->DEPARTMENTNAME </b><br> 
				Parking Garage:&nbsp;<b>$package->LOCATIONLABEL </b><br> 
				Number of Tickets:&nbsp;<b>$package->FBNUMBEROFTICKETS </b><br> 
				Ticket Type:&nbsp;<b>$package->FBTICKETTYPE-USE </b><br> 
				Purchase Total:&nbsp;<b>$".$package->TOTALORDERCOST.".00</b><br /><br /><h2>Validation Codes:</h2><p>$codeTable</p><br>
				<p style='color:#FF0000'>Validation Tickets are non-refundable and must be used by June 30, 2026. No refunds will be issued for unused codes.</p>
				<br>  <p>
				Kind regards,  <br>
				Parking & Transportation Services    <br><br><br>
				<hr><span style='color:#FF0000;text-decoration:underline;font-size:18px;'>Terms & Conditions</span><br>
				The University of Arizona department named below will be assigned a visitor parking code and accepts responsibility for any code usage and subsequent charges to a KFS account number, regardless of whether the parking code usage is authorized. If a department discovers that a parking code has been misused, it is that department’s responsibility to contact Parking & Transportation Services to deactivate the parking code. A new code may be issued upon request. Misuse of parking services is defined by University of Arizona Policy 9.10 “Requisitions/Reimbursements and states that parking fees for faculty, staff and administrators are unallowable expenses and shall not be paid for with department funds.
				";

          return $notInfo;
    }

    public  function flowbirdReservationTicketOrderNotification($package) {

        $notInfo = new \stdClass();
        $notInfo->subject = "Garage Reservation Receipt: Validations Codes Included";
        $notInfo->toEmail =  $package->CUSTOMEREMAIL;
        $notInfo->fromEmail ='PTS-Information@email.arizona.edu';
        $notInfo->fromName='UofA Parking & Transportation';
        // $notInfo->cc ="";
        $codeTable=$this->tablelizePasscodes($package->ticketCodes);

    $notInfo->emailBody = "RE: <strong>Garage Reservation Receipt: Validations Codes Included</strong><br><br>
      <p>Greetings $package->CUSTOMERNAME,   
				  <br></p><p>
				Thank you for your purchase.   
				<br></p><p>
				Validation codes are required for all main campus parking garages where there are no gate arms at the entry or exit. To use this code, customers must use the Flowbird Kiosk located near the garage booth, or the elevator on the top floor of Health Sciences Garage.
				<br></p><p>
				<b>Please provide to your guest:
				<ul>
				<li>Selected Parking Garage</li><li>Validation code(s)</li>
				<li><a href='https://parking.arizona.edu/flowbird/validation-code-instructions.pdf' title='Gateless Garage Code Redemption Instructions'>Guest Validation Code Instructions</a></li>
				</ul></b>
				</p>
					Below is your Reservation information and list of Flowbird validation codes:  
				 <br>  <br> <hr><br>
				 <h2>Order Receipt:</h2>
				 <p style='font-size:18px'>
				Order#:&nbsp;<b>$package->FBORDERID </b> <br> 
				Order Date:&nbsp;<b>$package->ORDERDATE </b><br> 
				KFSNUMBER:&nbsp;<b>$package->KFSNUMBER </b><br> 
				Department:&nbsp;<b>$package->DEPARTMENTNAME </b><br> 
				Parking Garage:&nbsp;<b>$package->LOCATIONLABEL </b><br> 
				Spaces Reserved:&nbsp;<b>$package->RESERVATIONVISITORCOUNT</b><br> 
				Reservation Date(S):&nbsp;<b>$package->RESERVATIONDATES</b><br> 
				Number of Tickets:&nbsp;<b>$package->FBNUMBEROFTICKETS </b><br> 
				Ticket Type:&nbsp;<b>$package->FBTICKETTYPE-USE </b><br> 
				Purchase Total:&nbsp;<b>$".$package->TOTALORDERCOST.".00</b><br /><br /><h2>Validation Codes:</h2><p>$codeTable</p><br>
				<p style='color:#FF0000'>Validation Tickets are non-refundable and must be used by June 30, 2026. No refunds will be issued for unused codes.</p>
				<br>  <p>
				Kind regards,  <br>
				Parking & Transportation Services    <br><br><br>
				<hr><span style='color:#FF0000;text-decoration:underline;font-size:18px;'>Terms & Conditions</span><br>
				The University of Arizona department named below will be assigned a visitor parking code and accepts responsibility for any code usage and subsequent charges to a KFS account number, regardless of whether the parking code usage is authorized. If a department discovers that a parking code has been misused, it is that department’s responsibility to contact Parking & Transportation Services to deactivate the parking code. A new code may be issued upon request. Misuse of parking services is defined by University of Arizona Policy 9.10 “Requisitions/Reimbursements and states that parking fees for faculty, staff and administrators are unallowable expenses and shall not be paid for with department funds.
				";

        return $notInfo;
    }

    public function lowCodeInventoryNotification($package) {
        $notInfo = new \stdClass();
        $notInfo->subject = "FlowBird Low Inventory ALERT";
        $notInfo->toEmail =  $package->TOEMAIL;
        $notInfo->fromEmail ='PTS-Information@email.arizona.edu';
        $notInfo->fromName='UofA Parking & Transportation';
        // $notInfo->cc ="";
        $codeTable=$this->tablelizePasscodes($package->ticketCodes);

        $notInfo->emailBody="FlowBird Low Inventory ALERT!!<br><br>
            Garage: <strong>$package->FBTICKETLOCATION</strong><br>
            Type: <strong>$package->FBTICKETTYPE</strong><br>
            Ticket Value: <strong>$package->FBTICKETVALUE</strong><br>
            Available Codes Remaining:  <strong>$package->REMAININGAVAILALBECODES</strong><br>";
        return $notInfo;
    }

    // public  function flowBirdReservationPasscodes($package,$settings) {
    //     //	$txt="<p>Greetings @@NAME@@ <br></p><p>Thank you for your Passport Passcode purchase. Below is your order information and list of passcodes. </p><p>Please check and make sure your order is correct. Changes must be made within 24 hours of time of purchase.</p><p>Order#:  @@ORDERID@@ <br>KFS: @@KFSNUMBER@@<br>Purchase Total: @@TOTAL@@</p><p>@@PASSCODES@@<br></p><br><p> Parking &amp; Transportation Services<br> The University of Arizona<br> 1117 E. 6th St.<br> Tucson, AZ 85721</p>";
    //     //	$txt="<p>Greetings @@NAME@@ <br></p><p>Thank you for your Passport Passcode purchase. Below is your order information and list of passcodes. </p><p>All Sales are final.<br></p><p>Order#:  @@ORDERID@@ <br>KFS: @@KFSNUMBER@@<br>Purchase Total: @@TOTAL@@</p><p>@@PASSCODES@@</p><p><br></p><p>All codes expire June 30th of current fiscal year. No refunds.<br></p><br><p> Parking &amp; Transportation Services<br> The University of Arizona<br> 1117 E. 6th St.<br> Tucson, AZ 85721</p>";
    //     $txt="<p>Greetings $package->CUSTOMERNAME,   
	// 			  <br></p><p>
	// 			Thank you for your purchase.   
	// 			<br></p><p>
	// 			Validation codes are required for all main campus parking garages where there are no gate arms at the entry or exit. To use this code, customers must download the Flowbird Parking app on their smartphone and enter the validation code upon parking in the garage.
	// 			<br></p><p>
	// 			<b>Please provide to your guest:
	// 			<ul>
	// 			<li>Selected Parking Garage</li><li>Validation code(s)</li>
	// 			<li><a href='https://parking.arizona.edu/pdf/gateless-garage-guest-passcode-instructions.pdf' title='Gateless Garage Code Redemption Instructions'>Guest Instructions</a></li>
	// 			</ul></b>
	// 			</p><p>
	// 			Below is your order information and list of Flowbird validation codes for your reservation(s):   
	// 			 <br></p><p>
	// 			Order#:<b> @@ORDERID@@ </b> <br> 
	// 			KFS:<b> @@KFSNUMBER@@ </b><br> 
	// 			Date of Reservation:<b> @@RESDATE@@ </b><br> 	
	// 			Number of Spaces:<b> @@GUESTCOUNT@@ </b><br> 
	// 			Reservation Confirmation Number:<b> @@RESERVATIONNUMBER@@ </b><br>
	// 			Purchase Total:<b> @@TOTAL@@ </b><br>
	// 			  <br></p><p>
	// 			All reservations are final. No refunds.  <br>   
	// 				<br></p><p>
	// 			".$this->tablelizePasscodes($package->ticketCodes)."
	// 			  <br></p><p>
	// 			Kind regards,  <br>
	// 			Parking & Transportation Services    <br>";
    //     $rep=$this->getReplacementOrderData($package->ORDERID);
    //     $package->replacements=$rep[0];
    //     $package=$this->tablelizePasscodes($package);
    //     $EMAILTEXT=$this->textReplacementsdb($package,$txt);
    //     return $EMAILTEXT;
    // }

    public function ticketOrderCustomerNotification($record,$settings)
    {
        $notInfo = new \stdClass();
        $notInfo->subject = "UA Special Event Dash-Pass Validation Tickets Ready for Pickup";
        $notInfo->toEmail = ($settings->APPLICATION_MODE === "PRODUCTION") ? $record->CUSTOMEREMAIL : $record->CUSTOMEREMAIL;
        $notInfo->fromEmail = "PTS-Phoenix@email.arizona.edu";
        $notInfo->cc = ($settings->APPLICATION_MODE === "PRODUCTION") ? "PTS Phoenix <PTS-Phoenix@email.arizona.edu>" : "Test Department <" . $settings->TEST_NOTIFICATION_EMAIL . ">";
        $notInfo->emailBody = "RE:  <strong>UA Special Event Dash-Pass Validation Tickets Ready for Pickup</strong><br><br>
Hello,<br><br>
The Dash-Pass Validation Tickets for your special event <strong>".$record->EVENTNAME."</strong> are ready for pickup.<br><br>
You may pickup your tickets at the PBC security desk.<br><br>
Event Name: <strong>$record->EVENTNAME</strong><br>
KFS NUMBER: <strong>$record->KFSNUMBER</strong><br>
Organization/Department: <strong>The University of Arizona / $record->DEPARTMENTNAME</strong><br>
Date of Event MM/DD/YYYY: <strong>$record->RESERVATIONDATE</strong><br>
Time Period of Event: <strong>$record->STARTTIME to $record->ENDTIME</strong><br><br><br>
Number of Tickets: <strong>$record->NUMBEROFVALIDATIONS</strong><br>
Price/Per Ticket: <strong>$".$record->PRICEPERSPACE.".00</strong><br>
Total purchase: <strong>$".($record->PRICEPERSPACE * $record->NUMBEROFVALIDATIONS).".00</strong><br><br><br>

<strong><big>Billing information:</big></strong><br>
Email invoice to: PTS-Phoenix@email.arizona.edu<br><br>
Cordially,<br><br>
Parking & Transportation Services<br>
The University of Arizona<br>
    (602) 827-2760
 ";
        return $notInfo;
    }

    function tablelizePasscodes($codedata) {

        $codedata  =json_encode($codedata);
        $codedata = json_decode($codedata , True);

        $codeHTML="<table style='font-size:18px;width:600px;'>";
        $codeHTML=$codeHTML."<tr style='order-bottom:1px solid #555555;background-color:#eeeeee'><th style='width:50%;'>CODE</th><th style='width:20px;'>VALUE</th><th style='width:30%;'>TYPE</th></tr>";
        for ($i = 0; $i < count($codedata); $i++) {
            $codeHTML.="<tr>";
            $codeHTML.="<td><b>".$codedata[$i]['FBTICKETCODE']."</b></td>";
            $codeHTML.="<td>$".$codedata[$i]['FBTICKETVALUE'].".00</td>";
            $codeHTML.="<td>".$codedata[$i]['FBTICKETTYPE']."-USE</td>";
            $codeHTML.="</tr>";
        }
        $codeHTML.="</table>";
        return $codeHTML;
    }

}
