<?php
if (!checkEnabled('Garage Coupon')) // Winter Shutdown message will be displayed within checkEnabled func.
{
	locationHref('index.php?biggerWarning=1');
	exit; // just in case somebody turns off javascript
}
//if (!isset($dbConn)) $dbConn = new database();
//// Winter shutdown messages
//include_once 'messages.inc.php';
?>
<script type="text/javascript">
//var is_firefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
//var is_ie = (navigator.userAgent.toLowerCase().indexOf('msie') > -1 || navigator.userAgent.toLowerCase().indexOf('trident') > -1);
//if (!is_firefox && !is_ie)
//	alert('Your current web browser will not work here. Please use Firefox or Internet Explorer.')
</script>
<?php

//if (@$_SESSION['resConfirmed']) {
//	// $_SESSION['resConfirmed'] set and then un-set below, so as to take care of possible Back button problem in browser.
//	session_destroy();
//	$_SESSION = $_POST = $_GET = array();
//	unset($_SESSION);
//	unset($_POST);
//	unset($_GET);
//}
//
//function stripBadChars() {
//	if (@$_POST['guestList'])
//		$_POST['guestList'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['guestList']);
//	if (@$_POST['guestName'])
//		$_POST['guestName'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['guestName']);
//	if (@$_POST['laddGuests'])
//		$_POST['laddGuests'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['laddGuests']);
//	if (@$_POST['groupName'])
//		$_POST['groupName'] = preg_replace('/[^ \d\w]/i', '', $_POST['groupName']);
//	if (@$_POST['spaces'])
//		$_POST['spaces'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['spaces']);
//	if (@$_POST['gaddGuests'])
//		$_POST['gaddGuests'] = preg_replace('/[^ \|\d\w]/i', '', $_POST['gaddGuests']);
//}
//
//stripBadChars();
//
//include_once 'gr/reservation_functions.php';
//include_once 'gr/form_functions.php';
//
//$protectPass = $dbConn->protectCheck(2);
//
//if ($protectPass)
//{
//
//	$customer = $_SESSION['cuinfo'];
//	$userid   = $customer['userid'];
//
//	if (isset($_GET['res']))
//	{
//		echo generateResReceipt($_GET['res']);
//
//	}
//	elseif (@$_POST['confirm'] && trim($_POST['garage']))
//	{
//		if ($_POST['groupGuest']=="group") {
//			$option1 = array($_POST['groupName']);
//			$option2 = $_POST['spaces'];
//			$comeGo = isChecked("gcomeGo","1","0");
//			$addGuests = "gaddGuests";
//		}
//		else {
//			$option1 = explode(" | ",$_POST['guestList']);
//			$option2 = NULL;
//			$comeGo = isChecked("comeGo","1","0");
//			$addGuests = "laddGuests";
//		}
//
//		if ($_POST['dates'])
//			$dates = explode(",",$_POST['dates']);
//		//$dates[] = $_POST['startDate'];
//
//		$res = new reservation();
//
//		$pdfConfirmFile = '';
//
//		$_SESSION['resConfirmed'] = 1;
//		// Create reservations and send confimration emails.
//		$res->newRes($_POST['frs'], $_POST['KFS_SUB_ACCOUNT_FK'], $_POST['KFS_SUB_OBJECT_CODE_FK'], $customer, $_POST['garage'], $dates, $_POST['enterTime'], $_POST['exitTime'], $_POST['groupGuest'], $option1, $option2, $comeGo, isChecked("allowExtra","1","0"), $_POST[$addGuests]);
//		$_SESSION['resConfirmed'] = 0;
//
//
//		if ($res->error) {

//			$errMsg = $res->errorOut($res->error,$res->errordate);
//
//			$resInfo = array();
//			$glg = '';
//			massagePost($resInfo, $glg, true);
//
//			$cancelUri = 'index.php';
//
//			include_once 'rescouponform.php';
//
//		}
//
//		elseif ($res->conf) {
//			header("Location: view.php?action=receipt&id=$res->conf&pdfConfirmFile=$pdfConfirmFile");
//		}
//
//		else {
//			echo $res->errorOut("noConf");
//		}
//
//	}
//	elseif (isset($_POST['reserve']) || isset($_POST['reserve_x']))
//	{
//
//		//================= confirmation and agreement ===================
//
//		array_walk($_POST,"fixPost");
//
//		$dates = explode(",",$_POST['dates']);
//
//		if ($_SERVER['REMOTE_ADDR']=='128.196.6.35' || $_SERVER['REMOTE_ADDR']=='150.135.113.209')
//		{
//			echo '<div style="font-weight:bold; border:1px solid red">debug, changing Auth to 2.</div>';
//			$customer['auth'] = 2;
//		}
//
//		$error = false;
//		if ($customer['auth']<3) {
//			if ($_POST['spaces']>25)
//				$error = 'maxSpaces';
//			if (in_array(date("m/d/Y"),$dates) || in_array(date("n/j/Y"),$dates) || in_array(date("m/j/Y"),$dates) || in_array(date("n/d/Y"),$dates))
//				$error = 'today';
//		}
//
//
//		echo "\n".'<form method="post" action="';
//			if (isset($_SERVER['HTTP_REFERER'])) {
//				echo $_SERVER['HTTP_REFERER'];
//				if (isset($_GET['id']) && !strpos($_SERVER['HTTP_REFERER'],'?id='))
//					echo '?id='.$_GET['id'];
//			} else {
//				echo 'create.php';
//			}
//		echo '">'."\n";
//


////		                                              BLOCKED GARAGES FOR GAME DAYS

//		Garage id's and names:
//			11 -> USA Lot
//			10 -> 9006 Lot
//			8 -> Cherry Avenue Garage
//			7 -> Highland Avenue Garage
//			1 -> Main Gate Garage
//			2 -> Park Avenue Garage
//			9 -> Phoenix Biomedical Lot 10002 (formally Phoenix BioMedical Campus)
//			3 -> Second Street Garage
//			4 -> Sixth Street Garage
//			5 -> Tyndall Avenue Garage


		//==================================== Saturday Football Home Games =========================================
//		$closeGarages_football = array(8, 7, 1, 2, 3, 4, 5);
//		//$closeDates_football = array('08/30/2008', '09/06/2008', '10/04/2008', '10/18/2008', '10/25/2008', '11/22/2008', '12/06/2008'); // mm/dd/yyyy
//		//$closeDates_football = array('09/05/2009', '09/12/2009', '10/17/2009', '10/24/2009', '11/07/2009', '11/21/2009'); // mm/dd/yyyy
//		//$closeDates_football = array('09/11/2010', '09/18/2010', '09/25/2010', '10/09/2010', '10/23/2010', '11/13/2010', '12/02/2010'); // mm/dd/yyyy
//		$closeDates_football = array('08/29/2014', '09/13/2014', '09/20/2014', '10/11/2014', '11/08/2014', '11/15/2014', '11/28/2014'); // mm/dd/yyyy
//
//		// See if selected dates are on football game days.
//		foreach ($closeDates_football as $bk => $bDate) {
//			if (in_array($bDate, $dates) && in_array($_POST['garage'], $closeGarages_football)) {
//				if (@$closeMsg_football)
//					$closeMsg_football .= ', ';
//				$closeMsg_football .= $bDate;
//			}
//		}
//		if (@$closeMsg_football) {
//			// Get the names of garages that are closed
//			foreach ($closeGarages_football as $bk => $bGarage) {
//				if (@$garages_football)
//					$garages_football .= ', ';
//				$garages_football .= getGarageByID($bGarage);
//			}
//		}

//		//==================================== Basketball Home Games =========================================
//		$closeGarages_basketball = array(8);
//		// $closeDates_basketball = array('11/30/2008', '12/02/2008', '12/10/2008', '12/23/2008', '12/29/2008', '01/08/2009', '01/10/2009', '01/21/2009', '01/24/2009', '01/29/2009', '01/31/2009', '02/12/2009', '02/14/2009', '03/05/2009', '03/07/2009'); // mm/dd/yyyy
//		$closeDates_basketball = array('11/07/2010', '11/14/2010', '11/18/2010', '11/21/2010', '11/23/2010', '12/05/2010', '12/08/2010', '12/16/2010', '12/22/2010', '01/06/2011', '01/08/2011', '01/15/2011', '01/27/2011', '01/29/2011', '02/17/2011', '02/19/2011', '03/03/2011', '03/05/2011'); // mm/dd/yyyy
//
//		// See if selected dates are on basketball game days.
//		foreach ($closeDates_basketball as $bk => $bDate) {
//			if (in_array($bDate, $dates) && in_array($_POST['garage'], $closeGarages_basketball)) {
//				if (@$closeMsg_basketball)
//					$closeMsg_basketball .= ', ';
//				$closeMsg_basketball .= $bDate;
//			}
//		}
//		if (@$closeMsg_basketball) {
//			// Get the names of garages that are closed
//			foreach ($closeGarages_basketball as $bk => $bGarage) {
//				if (@$garages_basketball)
//					$garages_basketball .= ', ';
//				$garages_basketball .= getGarageByID($bGarage);
//			}
//		}
//
//
//
//
//		//==================================== OTHER =========================================
//		$closeGarages_other = array(8, 3, 4);
//		$closeOther = array('01/12/2011'); // mm/dd/yyyy
//
//		foreach ($closeOther as $bk => $bDate) {
//			if (in_array($bDate, $dates) && in_array($_POST['garage'], $closeGarages_other)) {
//				if (@$closeMsg_other)
//					$closeMsg_other .= ', ';
//				$closeMsg_other .= $bDate;
//			}
//		}
//		if (@$closeMsg_other) {
//			// Get the names of garages that are closed
//			foreach ($closeGarages_other as $bk => $bGarage) {
//				if (@$garages_other)
//					$garages_other .= ', ';
//				$garages_other .= getGarageByID($bGarage);
//			}
//		}
//
//
//		echo '<h2>Agreement and Confirmation</h2>';
//
//		echo '<div align="left" font-weight:bold;"><div style="margin:0 auto; width:800px; padding:20px; border:solid 2px #003366;">';
//
//		if (@$closeMsg_football)
//		{
//			echo '<div style="color:#cc0033;">There is a football game on the following date(s):&nbsp; '.$closeMsg_football.'.  &nbsp; And for these dates the following garages are not available: &nbsp;' . $garages_football . '.  <br>Please <input type="button" name="cancelit" class="submitter" value="Cancel" onclick="document.location.href=\'/parking/garage-reservation/create.php?logout=1\';" /> or <input type="submit" name="change" class="submitter" value="Make Changes"/> or call PTS Visitor Programs at (520) 621-3710.</div><br>';
//
//		}
//		elseif (@$closeMsg_basketball)
//		{
//			echo '<div style="color:#cc0033;">There is a basketball game on the following date(s):&nbsp; '.$closeMsg_basketball.'.  &nbsp; And for these dates the following garages are not available: &nbsp;' . $garages_basketball . '.  <br>Please <input type="button" name="cancelit" class="submitter" value="Cancel" onclick="document.location.href=\'/parking/garage-reservation/create.php?logout=1\';" /> or  <input type="submit" name="change" class="submitter" value="Make Changes"/> or call PTS Visitor Programs at (520) 621-3710.</div><br>';
//
//		}
//		elseif (@$closeMsg_other)
//		{
//			echo '<div style="color:#cc0033;">There is a Memorial & President Obama is to visit on:&nbsp; '.$closeMsg_other.'.  &nbsp; And for this date the following garages are not available: &nbsp;'. $garages_other . '.  <br>Please <input type="button" name="cancelit" class="submitter" value="Cancel" onclick="document.location.href=\'/parking/garage-reservation/create.php?logout=1\';" /> or  <input type="submit" name="change" class="submitter" value="Make Changes"/> or call PTS Visitor Programs at (520) 621-3710.</div><br>';
//
//		}
//		else
//		{
//			echo '<b>Please read the following, confirm your information below, and click "Accept and Confirm" below.</b>';
//
//			include "reservation_agreement.php";
//
//			echo "\n	</div>\n";
//
//			// second st garage one dollar more.
//			$price = ($_POST['garage']==3) ? $_SESSION['G_price_second'] : $_SESSION['G_price_regular'];
//
//			if (isset($_POST['comeGo']) || isset($_POST['gcomeGo']))
//				$price = $_SESSION['G_price_comeandgo'];
//
//			$dateCount = count($dates);
//			if (count($dates)>1)
//				$firstDate = array_shift($dates);
//			else
//				$dates = $dates[0];
//			echo '<div style="margin:0 auto; width:600px; text-align:left; border:solid 2px #000000; padding:0 100px;"><h3>Reservation Details:</h3><p>';
//			echo '<b>KFS: </b>'.$_POST['frs'].'<br/>';
//			echo '<b>KFS Sub Acct.: </b>'.$_POST['KFS_SUB_ACCOUNT_FK'].'<br/>';
//			echo '<b>Sub Obj. Code: </b>'.$_POST['KFS_SUB_OBJECT_CODE_FK'].'<br/>';
//			if (is_array($dates))
//				echo "<b>Date:</b> $firstDate<br/><b>Additional Dates:</b> ".implode(",",$dates);
//			else
//				echo "<b>Date:</b> $dates";
//			echo "<br/>\n<b>Enter Time:</b> ".$_POST['enterTime']."<br/><b>Exit Time:</b> ".$_POST['exitTime']."<br/>\n";
//
//
//			if (preg_match('/bio.?med/si', getGarageByID($_POST['garage'])))
//			{
//				// Note: don't use quick link http://parking.arizona.edu/maps/pbc/ - it will make user re-login.
//				$realGarageStr = "Phoenix BioMedical Campus<b>,</b> " .
//									  "<a href='http://parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf' target='_blank'>Parking Lot 10002" .
//									  ",&nbsp; 714 E Van Buren</a>";
//			}
//			else
//			{
//				$realGarageStr = getGarageByID($_POST['garage']);
//			}
//
//			echo '<b>Garage:</b> '.$realGarageStr."<br/>\n";
//			if ($_POST['groupGuest']=="group") {
//				echo "<b>Group Name:</b> ".$_POST['groupName']."<br/>\n<b>Spaces:</b> ".$_POST['spaces']."<br/>\n<b>Total Cost:</b> \$".($_POST['spaces']*$price*$dateCount).".00<br/>\n";
//			} else {
//				$guestList = explode(" | ",stripslashes($_POST['guestList']));
//				$guestList = array_unique($guestList);
//				$guestPost = implode(" | ",$guestList);
//				echo "<b>Guest List:</b> $guestPost<br/>\n<b>Total Cost:</b> \$".(count($guestList)*$price*$dateCount).".00<br/>\n";
//			}
			?>
			</p>
			<p align="center">
			<input type="hidden" name="confirm" value="">
			<input type="hidden" name="change" value="">
			<input type="button" class="submitter" value="Accept and Confirm" onclick="this.form.setAttribute('target', '_blank'); this.form.confirm.value=1; this.form.submit(); document.location.href='/parking/garage-reservation/?resConf=1';" /> &nbsp;
			<input type="button" class="submitter" value="Make Changes" onclick="this.form.change.value=1; this.form.submit();"/> <br />
			<span style="color:#CC0000;">NOTE: When you Accept and Confirm a new window will open up.</span><br /></p>
			<p align="center"><a href="index.php"><img src="/images/cancelform-button.gif" width="120" height="25" alt="Cancel" align="absmiddle" border="0"/></a></p>
			<?php
//		}
//
//		array_walk($_POST, 'breakPost');
//
//		foreach ($_POST as $field=>$val)
//		{
//			if ($field!="reserve" && $field!='reserve_x' && $field!="change")
//				echo "\n<input type=\"hidden\" name=\"$field\" value=\"$val\"/>";
//		}
//
//		echo '</form>';
//	}
//	else
//	{
//		$resInfo = array();
//		$glg = '';
//		massagePost($resInfo, $glg);
//		$cancelUri = "index.php";
//		include_once 'rescouponform.php';
//	}
//}
//
//
//function massagePost(&$resInfo, &$glg, $change=0) {
//	if (isset($change)) {
//		array_walk($_POST, "fixPost");
//		$resInfo = array(
//			"FRS_FK"=>$_POST['frs'],
//			"KFS_SUB_ACCOUNT_FK"=>$_POST['KFS_SUB_ACCOUNT_FK'],
//			"KFS_SUB_OBJECT_CODE_FK"=>$_POST['KFS_SUB_OBJECT_CODE_FK'],
//			"RESDATE"=>$_POST['startDate'],
//			"RESSTART"=>$_POST['enterTime'],
//			"RESEND"=>$_POST['exitTime'],
//			"GARAGE_ID_FK"=>$_POST['garage']
//		);
//		$glg = $_POST['groupGuest'];
//		if ($glg=="group") {
//			$resInfo['GUEST_NAME'] = $_POST['groupName'];
//			$resInfo['GROUP_SIZE'] = $_POST['spaces'];
//			$resInfo['GUESTS_OFFCAMPUS'] = $_POST['gaddGuests'];
//		}
//		else {
//			$resInfo['guestList'] = $_POST['guestList'];
//			$resInfo['GUESTS_OFFCAMPUS'] = $_POST['laddGuests'];
//		}
//		if (isset($_POST['allowExtra'])) $resInfo['ALLOW_EXTRA'] = 1;
//		if (isset($_POST['comeGo'])) $resInfo['COME_AND_GO'] = 1;
//		if (isset($_POST['gcomeGo'])) $resInfo['COME_AND_GO'] = 1;
//	}
//
//	else {
//		$glg = "guest";
//	}
//}

?>