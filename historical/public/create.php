<?php
ini_set('display_errors', 0);
// error_reporting(E_ALL);

$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once $docRoot.'/parking/garage-reservation/garage-reservation-include.php';
?>
<div id="main">
 <br />
 <div class="container">
	<div class="row">
	 <div class="col-sm-4 col-md-4 col-lg-4 hidden-xs">
	 <?php
	 include_once $docRoot.'/parking/parking-menu-include.php';
	 ?>
	 </div>
	 <!-- end side nav menu -->
	 <div id="mainContent" class="col-sm-8 col-md-8 col-lg-8"  >
	 <ol class="breadcrumb">
		<li><a href="/">Home</a></li>
		<li><a href="/parking/">Parking & Permits</a></li>
		<li class="active">Visitor Reservation</li>
	 </ol>
	 <h1  class="page-heading">Department Visitor Reservation</h1>
	 <hr />
	 <div id="editableContent">

<script type="text/javascript">
//alert (navigator.userAgent.toLowerCase());
var is_firefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
var is_ie = (navigator.userAgent.toLowerCase().indexOf('msie') > -1 || navigator.userAgent.toLowerCase().indexOf('trident') > -1);
if (!is_firefox && !is_ie)
	alert('Your current web browser '+ navigator.userAgent + ' will not work here. Please use Firefox or Internet Explorer.')
</script>


<?php
spinnerWaiting();
if (@$_SESSION['resConfirmed'])
{
	// $_SESSION['resConfirmed'] set and then un-set below, so as to take care of possible Back button problem in browser.
	session_destroy();
	$_SESSION = $_POST = $_GET = array();
	unset($_SESSION);
	unset($_POST);
	unset($_GET);
}

function stripBadChars() {
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

stripBadChars();

include_once 'gr/reservation_functions.php';
include_once 'gr/form_functions.php';

//if ($_SESSION['cuinfo']['auth'] >= 4)
if ($_SESSION['cuinfo']['auth'] >= 2) // Note that as of AUG-08 we need to only limit this to absolute admins only.
{
	$customer = $_SESSION['cuinfo'];
	$userid   = $customer['userid'];

	if (isset($_GET['res']))
	{
		echo generateResReceipt($_GET['res']);
	}
	elseif (@$_POST['confirm'] && trim($_POST['garage']))
	{
			if ($_POST['groupGuest']=="group") {
			$option1 = array($_POST['groupName']);
			$option2 = $_POST['spaces'];
			$comeGo = isChecked("gcomeGo","1","0");
			$addGuests = "gaddGuests";
		}
		else {
			$option1 = explode(" | ",$_POST['guestList']);
			$option2 = NULL;
			$comeGo = isChecked("comeGo","1","0");
			$addGuests = "laddGuests";
		}

		if ($_POST['dates'])
			$dates = explode(",",$_POST['dates']);

		$res = new reservation();

		$pdfConfirmFile = '';

		$_SESSION['resConfirmed'] = 1;
		// Create reservations and send confimration emails.
		$res->newRes($_POST['frs'], $_POST['KFS_SUB_ACCOUNT_FK'], $_POST['KFS_SUB_OBJECT_CODE_FK'], $customer, $_POST['garage'], $dates, $_POST['enterTime'], $_POST['exitTime'], $_POST['groupGuest'], $option1, $option2, $comeGo, isChecked("allowExtra","1","0"), $_POST[$addGuests]);
		$_SESSION['resConfirmed'] = 0;


		if ($res->error) {



			$errMsg = $res->errorOut($res->error,$res->errordate);

			$resInfo = array();
			$glg = '';
			massagePost($resInfo, $glg, true);

			$cancelUri = 'index.php';

			include_once 'resform.php';
		}

		elseif ($res->conf)
			locationHref('/parking/garage-reservation/view.php?action=receipt&id='.$res->conf.'&pdfConfirmFile='.$pdfConfirmFile);

		else {
			echo $res->errorOut("noConf");
		}
	}
	elseif (isset($_POST['reserve']) || isset($_POST['reserve_x']))
	{
		//================= confirmation and agreement ===================

		array_walk($_POST,"fixPost");

		$dates = explode(",",$_POST['dates']);

		if ($_SERVER['REMOTE_ADDR']=='128.196.6.35' || $_SERVER['REMOTE_ADDR']=='150.135.113.209')
		{
			echo '<div style="font-weight:bold; border:1px solid red">debug, changing Auth to 2.</div>';
			$customer['auth'] = 2;
		}

		$error = false;
		if ($customer['auth']<3) {
			if ($_POST['spaces']>25)
				$error = 'maxSpaces';
			if (in_array(date("m/d/Y"),$dates) || in_array(date("n/j/Y"),$dates) || in_array(date("m/j/Y"),$dates) || in_array(date("n/d/Y"),$dates))
				$error = 'today';
		}

		$get_p = '';
		if (isset($_GET['id']) && ctype_digit($_GET['id']) && !strpos($_SERVER['HTTP_REFERER'],'?id='))
			$get_p = '?id='.$_GET['id'];

		echo '<form method="post" action="';
			if (isset($_SERVER['HTTP_REFERER'])) {
				echo $_SERVER['HTTP_REFERER'];
				echo $get_p;
			} else {
				echo 'create.php';
			}
		echo '">';



		/**************************************************************************************************************
		                                              BLOCKED GARAGES FOR GAME DAYS
		*************************************************************************************************************/

		/**************************************
		Garage id's and names:
			11 -> USA Lot
			10 -> 9006 Lot
			8 -> Cherry Avenue Garage
			7 -> Highland Avenue Garage
			1 -> Main Gate Garage
			2 -> Park Avenue Garage
			9 -> Phoenix Biomedical Lot 10002 (formally Phoenix BioMedical Campus)
			3 -> Second Street Garage
			4 -> Sixth Street Garage
			5 -> Tyndall Avenue Garage
		*************************************/


		//==================================== Saturday Football Home Games =========================================
		$closeGarages_football = array(8, 7, 1, 2, 3, 4, 5, 9, 12, 13);
		$closeDates_football = array('09/02/2023','09/16/2023','09/30/2023','10/28/2023','11/04/2023','11/18/2023'); 

		// NEW 2024 dates. 31-8, 7-9, 5-10, 19-10, 26-10, 15-11, 30-11
		// TARGET: Cherry, 2nd St, 6th St (8, 3, 4)
		$closeGarages_football = [8,3,4];
		$closeDates_football = [
			"08/31/2024",
		    "09/07/2024",
		    "10/05/2024",
		    "10/19/2024",
		    "10/26/2024",
		    "11/15/2024",
		    "11/30/2024"	
		];

		// See if selected dates are on football game days.
		foreach ($closeDates_football as $bk => $bDate) {
			if (in_array($bDate, $dates) && in_array($_POST['garage'], $closeGarages_football)) {
				if (@$closeMsg_football)
					$closeMsg_football .= ', ';
				$closeMsg_football .= $bDate;
			}
		}
		if (@$closeMsg_football) {
			// Get the names of garages that are closed
			foreach ($closeGarages_football as $bk => $bGarage) {
				if (@$garages_football)
					$garages_football .= ', ';
				$garages_football .= getGarageByID($bGarage);
			}
		}


		//==================================== Basketball Home Games =========================================
		$closeGarages_basketball = array(8);
		// $closeDates_basketball = array('11/30/2008', '12/02/2008', '12/10/2008', '12/23/2008', '12/29/2008', '01/08/2009', '01/10/2009', '01/21/2009', '01/24/2009', '01/29/2009', '01/31/2009', '02/12/2009', '02/14/2009', '03/05/2009', '03/07/2009'); // mm/dd/yyyy
		$closeDates_basketball = array(); // mm/dd/yyyy

		// See if selected dates are on basketball game days.
		foreach ($closeDates_basketball as $bk => $bDate) {
			if (in_array($bDate, $dates) && in_array($_POST['garage'], $closeGarages_basketball)) {
				if (@$closeMsg_basketball)
					$closeMsg_basketball .= ', ';
				$closeMsg_basketball .= $bDate;
			}
		}
		if (@$closeMsg_basketball) {
			// Get the names of garages that are closed
			foreach ($closeGarages_basketball as $bk => $bGarage) {
				if (@$garages_basketball)
					$garages_basketball .= ', ';
				$garages_basketball .= getGarageByID($bGarage);
			}
		}




		//==================================== OTHER =========================================
		$closeGarages_other = array(8, 3, 4);
		$closeOther = array('09/02/2013','09/16/2013','09/30/2013','10/28/2013','11/04/2013','10/16/2013','11/18/2013'); // mm/dd/yyyy

		foreach ($closeOther as $bk => $bDate) {
			if (in_array($bDate, $dates) && in_array($_POST['garage'], $closeGarages_other)) {
				if (@$closeMsg_other)
					$closeMsg_other .= ', ';
				$closeMsg_other .= $bDate;
			}
		}
		if (@$closeMsg_other) {
			// Get the names of garages that are closed
			foreach ($closeGarages_other as $bk => $bGarage) {
				if (@$garages_other)
					$garages_other .= ', ';
				$garages_other .= getGarageByID($bGarage);
			}
		}


		echo '<h2>Agreement and Confirmation</h2>';

		echo '<div align="left" font-weight:bold;"><div style="margin:0 auto; width:800px; padding:20px; border:solid 2px #003366;">';

		if (@$closeMsg_football)
		{
			echo '<div style="color:#cc0033;">There is a football game on the following date(s):&nbsp; '.$closeMsg_football.'.  &nbsp; And for these dates the following garages are not available: &nbsp;' . $garages_football . '.  <br>Please <input type="button" name="cancelit" class="submitter" value="Cancel" onclick="document.location.href=\'/parking/garage-reservation/create.php?logout=1\';" /> or <input type="submit" name="change" class="submitter" value="Make Changes"/> or call PTS Visitor Programs at (520) 621-3710.</div><br>';

		}
		elseif (@$closeMsg_basketball)
		{
			echo '<div style="color:#cc0033;">There is a basketball game on the following date(s):&nbsp; '.$closeMsg_basketball.'.  &nbsp; And for these dates the following garages are not available: &nbsp;' . $garages_basketball . '.  <br>Please <input type="button" name="cancelit" class="submitter" value="Cancel" onclick="document.location.href=\'/parking/garage-reservation/create.php?logout=1\';" /> or  <input type="submit" name="change" class="submitter" value="Make Changes"/> or call PTS Visitor Programs at (520) 621-3710.</div><br>';

		}
		elseif (@$closeMsg_other)
		{
			echo '<div style="color:#cc0033;">There is a Memorial & President Obama is to visit on:&nbsp; '.$closeMsg_other.'.  &nbsp; And for this date the following garages are not available: &nbsp;'. $garages_other . '.  <br>Please <input type="button" name="cancelit" class="submitter" value="Cancel" onclick="document.location.href=\'/parking/garage-reservation/create.php?logout=1\';" /> or  <input type="submit" name="change" class="submitter" value="Make Changes"/> or call PTS Visitor Programs at (520) 621-3710.</div><br>';

		}
		else
		{
			echo '<b>Please read the following, confirm your information below, and click "Accept and Confirm" below.</b>';

			include "reservation_agreement.php";

			echo "\n	</div>\n";

			// second st garage one dollar more.
			//$price = ($_POST['garage']==3) ? $_SESSION['G_price_second'] : $_SESSION['G_price_regular'];

			if (isset($_POST['comeGo']) || isset($_POST['gcomeGo']))
				//$price = $_SESSION['G_price_comeandgo'];

			$dateCount = count($dates);
			if (count($dates)>1)
				$firstDate = array_shift($dates);
			else
				$dates = $dates[0];
			echo '<div style="margin:0 auto; width:600px; text-align:left; border:solid 2px #000000; padding:0 100px;"><h3>Reservation Details:</h3><p>';
			echo '<b>KFS: </b>'.$_POST['frs'].'<br/>';
			echo '<b>KFS Sub Acct.: </b>'.$_POST['KFS_SUB_ACCOUNT_FK'].'<br/>';
			echo '<b>Sub Obj. Code: </b>'.$_POST['KFS_SUB_OBJECT_CODE_FK'].'<br/>';
			if (is_array($dates))
				echo "<b>Date:</b> $firstDate<br/><b>Additional Dates:</b> ".implode(', ', $dates);
			else
				echo "<b>Date:</b> $dates";
			echo "<br/>\n<b>Enter Time:</b> ".$_POST['enterTime']."<br/><b>Exit Time:</b> ".$_POST['exitTime']."<br/>\n";


			if (preg_match('/bio.?med/si', getGarageByID($_POST['garage'])))
			{
				// Note: don't use quick link http://parking.arizona.edu/maps/pbc/ - it will make user re-login.
				$realGarageStr = "Phoenix BioMedical Campus<b>,</b> " .
									  "<a href='http://parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf' target='_blank'>Parking Lot 10002" .
									  ",&nbsp; 714 E Van Buren</a>";
			}
			else
			{
				$realGarageStr = getGarageByID($_POST['garage']);
			}

			echo '<b>Garage:</b> '.$realGarageStr."<br/>\n";
			if ($_POST['groupGuest']=="group") {
				echo "<b>Group Name:</b> ".$_POST['groupName']."<br/>\n<b>Spaces:</b> ".$_POST['spaces']."<br/>\n";
				//echo "<b>Total Cost:</b> \$".($_POST['spaces']*$price*$dateCount).".00<br/>\n";
			} else {
				$guestList = explode(" | ",stripslashes($_POST['guestList']));
				$guestList = array_unique($guestList);
				$guestPost = implode(" | ",$guestList);
				echo "<b>Guest List:</b> $guestPost<br/>\n";
				//echo "<b>Total Cost:</b> \$".(count($guestList)*$price*$dateCount).".00<br/>\n"
			}


			?>
			</p>

			<p align="center">
			<input type="hidden" name="confirm" value="">
			<input type="hidden" name="change" value="">
			<?php
			/***
			 * Accept and Confirm button
			 *		hidden "confirm" set to 1.
			 *		submits and also pop up a new window so customer does not try to click buttons during processing.
			 */
			?>
			<input type="button" class="submitter" value="Accept and Confirm" onclick="this.form.setAttribute('target', '_blank'); this.form.confirm.value=1; this.form.submit(); document.location.href='/parking/garage-reservation/?resConf=1';" /> &nbsp;
			<input type="button" class="submitter" value="Make Changes" onclick="this.form.change.value=1; this.form.submit();"/> <br />
			<span style="color:#CC0000;">NOTE: When you Accept and Confirm a new window will open up.</span><br /></p>
			<p align="center"><a href="index.php"><img src="/images/cancelform-button.gif" width="120" height="25" alt="Cancel" align="absmiddle" border="0"/></a></p>
			<?php
		}

		array_walk($_POST, 'breakPost');

		foreach ($_POST as $field=>$val)
		{
			if ($field!="reserve" && $field!='reserve_x' && $field!="change")
				echo "\n<input type=\"hidden\" name=\"$field\" value=\"$val\"/>";
		}

		echo '</form>';


	} else {
		$resInfo = array();
		$glg = '';

		massagePost($resInfo, $glg);

		$cancelUri = "index.php";

		include_once 'resform.php';
	}
}


function massagePost(&$resInfo, &$glg, $change=0) {
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

?>
	 <br /> <br /> <br /> <br /> <br />

	 </div>
  </div>
</div>

</div>
</div>

<div id="garageInfoModel" class="modal fade " role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header" style="border-bottom:1px solid #FF0000;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h3 class="modal-title "  id="garageModelTitle"></h3>
      </div>
      <div class="modal-body">
        <p><span style="color:#FF0000;font-weight:650;">NEW: </span>  <span id="infoGaragename" style="font-weight:650;"></span> is now gateless. 
		Your guest(s) will now need a <span  style="font-weight:550;color:#378DBD">validation code</span> to park since there are no longer gate arms at 
		the entrance or exit. <br><br><b>Please continue</b> making your reservation and we will send you a separate email with the <span  style="font-weight:550;color:#378DBD">validation code(s)</span> 
		and instructions for your guest.  </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<?php
include_once $docRoot.'/Templates/bottom_footer.php';
?>

<script>
$( document ).ready(function() {
	$('#garageSelectControl').on('change',function(e) {
		var garSelected=$(this).val();
		console.log(garSelected);
		if (garSelected==='7' || garSelected==='13') {
			var gText=$("#garageSelectControl option:selected").text();
			$('#infoGaragename').html(gText);
			$('#garageModelTitle').html('<span style="color:#FF0000;font-weight:650;">ATTENTION: &nbsp;</span><span style="font-weight:650;">'+  gText + '</span>')
			$('#garageInfoModel').modal();
					
		}
		
	})
});
</script>
</body>
</html>
