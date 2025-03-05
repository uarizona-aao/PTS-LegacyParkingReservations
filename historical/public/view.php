<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once $docRoot.'/parking/garage-reservation/garage-reservation-include.php';
?>
<div id="main">
 <br />
 <div class="container" >
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
		<li class="active">Garage Reservation</li>
	 </ol>
	 <h1  class="page-heading">Department Visitor Garage Reservation</h1>
	 <hr />
	 <div id="editableContent">



<?php
spinnerWaiting();
if (!isset($_GET['id']))
	locationHref('/parking/garage-reservation/index.php');

if ($auth >= 2) // $_SESSION['cuinfo']['auth'] must be >= 2
{
	include_once 'gr/reservation_functions.php';
	include_once 'gr/form_functions.php';

	$res = new reservation();

	$customer = $_SESSION['cuinfo'];
	$userid 	= $customer['userid'];
	if (ctype_alnum($_GET['id']) || !$_GET['id'])
		$res->conf = $_GET['id'];

	$receipt = (isset($_GET['action']) && $_GET['action']=='receipt');

	$res->getRes($res->conf,true);

	if (!$res->resinfo)
		locationHref('/parking/garage-reservation/index.php?msg=resnotfound');

	$tmpAry = array('deptno'=>$res->resinfo['DEPT_NO_FK'][0], 'userid'=>$res->userid);

	if ($customer['auth']<3)
		$res->checkResOwner($customer, $tmpAry);

	$edit_show = 1;
	$cancel_show = 0;
	$revive_show = 0;
        if (@in_array($_SERVER['REMOTE_ADDR'], $debugIP_1)) {
    
    echo "auth:".$customer['auth'];
    echo "res.acting:".$res->active;    
    echo "revided:".$_POST['revive'];
}
	if (isset($_POST['revive']))
	{
		if ($customer['auth']>=4)
		{
			$res->resurrect(array($res->conf));
			echo '<div class="warning" style="border:2px solid #c03; margin:3px; padding:2px;" align="center">This Reservation Has Been Revived<br/>You may now edit it.</div>';
			// Testing refresh of page to show all actions - jsc - 20070916
			//  problem is that message above is lost (Reservation Revived)			//header("location:view.php?id=$res->conf");
			$cancel_show = 1;
			$edit_show = 1;
		}
		else
		{
			echo '<div class="warning" style="border:2px solid #c03; margin:3px; padding:2px;" align="center">This reservation cannot be revived at this time.</div>';
		}
	}
	elseif (isset($_POST['cancelres']))
	{
		if (($res->owner && $res->canCancel(array($res->resdate))) || $customer['auth']>=4)
		{
			// If editing PBC, have them call offoce. (see also view.php and cancel.php and edit.php)
			if (!$GLOBALS['DEBUG_DEBUG'] && preg_match('/bio.?med/si', $res->resinfo['GARAGE_NAME'][0]))
			{
				locationHref('/parking/garage-reservation/index.php?msg=nopbc');
			}
			else
			{
				$res->cancelRes(array($res->conf),$userid);
				echo '<br/><br/><div class="warning" style="border:2px solid #c03; margin:10px 3px 10px 3px; padding:2px;" align="center">This Reservation Has Been Cancelled</div><br/><br/>';
				$revive_show = 1;
				$edit_show = 0;
			}
		}
		else
		{
			echo '<div class="warning" style="border:2px solid #c03; margin:3px; padding:2px;" align="center">This reservation cannot be cancelled at this time.</div>';
		}

	} elseif (isset($_GET['cashtoggle']) && isset($_GET['date'])) {
		$set = ($_GET['cashtoggle']) ? 0 : 1;
		$date = urldecode($_GET['date']);

		$query = "UPDATE PARKING.GR_RESERVATION_NOTE SET CASHIER_DISPLAY=:set WHERE RESERVATION_ID_FK=:resid AND DATE_RECORDED=TO_DATE(:daterec,'MM-DD-YY HH:MI AM')";
		$qVars = array('set' => $set, 'resid' => $res->conf, 'daterec' => $date);
		$dbConn->sQuery($query, $qVars);

		locationHref('/parking/garage-reservation/view.php?id='.$res->conf);
	}

	$res->getGuests($res->resid);
	$gg = (isset($res->groupCount[0]) && $res->groupCount[0]>1) ? "group" : "guest";

	echo '<div style="text-align:center;">';
	if ($receipt)
	{
		echo '<br/><br/><span style="font-weight:bold; border: 1px solid black; padding:5px;">
			Your reservation (#'.$res->conf.') has been confirmed. You should receive a confirmation email shortly.</span><br/><br/><br/>';
	}
	else
	{
		echo "<h2>Reservation $res->conf for $res->deptName (FRS $res->frs)</h2>\n";
	}



	if ($_GET['pdfConfirmFile'] && ($customer['auth']>=4 || $res->owner)) {
		echo '<br/><span class="warning" style="border:2px dashed red; padding:8px;">
			<a href="/parking/garage-reservation/resPDF/'.$_GET['pdfConfirmFile'].'" target="_blank">Please Print and Save the PDF Permit for Phoenix BioMedical Parking here.</a>
			</span><br/><br/><br/>';
	}

	$showActions = $pdfConfStr = '';
if (@in_array($_SERVER['REMOTE_ADDR'], $debugIP_1)) {
    
    echo "auth:".$customer['auth'];
    echo "res.acting:".$res->active;    
}
	if (count($res->otherDates) > 1)
		$dateStr = " - $res->resdate";
	else
		$dateStr = '';


	if (
		($edit_show && (($customer['auth']>=4 || $res->owner) && date("Y/m/d",strtotime($res->resdate)) >= date("Y/m/d",strtotime("now"))))
		||  (($customer['auth']>=4 || $res->owner) && date("Y/m/d",strtotime($res->resdate)) > date("Y/m/d",strtotime("now")) && $res->active)
		)
	{

		$showActions .= '<button  type="button" class="list-group-item" ';
		$showActions .= ' onClick="'."window.location.href='/parking/garage-reservation/edit.php?id=$res->conf';\">";
		$showActions .= '<img src="/images/edit.gif" width="16" height="16" alt="" align="absmiddle"/>&nbsp;Edit This Reservation'.$dateStr.'</button>';
		//	$showActions .= '<button   type="button" class="list-group-item" ';
		//	$showActions .= ' onClick="'."window.location.href='/parking/garage-reservation/duplicate.php?id=$res->conf';\"/>";
		//	$showActions .= '<img src="/images/duplicate-icon.gif" width="25" height="16" alt="" align="absmiddle"/>&nbsp;Duplicate This Reservation</button>';


		// Find the PDF file
		$pdfShown = false;
		$pdfConfirmFile = $res->conf . '_' . ($res->conf * 13 + 846756) . '.pdf';
		if (file_exists("/var/www2/html/parking/garage-reservation/resPDF/$pdfConfirmFile")) {
			$pdfShown = true;
			$pdfConfStr  .=  '<a href="/parking/garage-reservation/resPDF/'.$pdfConfirmFile.'" target="_blank" style="font-size:12px; padding:2px; border:1px solid grey; color:#CC0000;">PDF Confirmation</a>';
			$showActions .= ' | ';
		}

		if (count($res->otherDates) > 1) {
			foreach ($res->otherDates as $k_resid => $v_date) {
				if (!$pdfShown) {
					$pdfConfirmFile = $k_resid . '_' . ($k_resid * 13 + 846756) . '.pdf';
					if (file_exists("/var/www2/html/parking/garage-reservation/resPDF/$pdfConfirmFile")) {
						$pdfConfStr .=  ' | <a href="/parking/garage-reservation/resPDF/'.$pdfConfirmFile.'" target="_blank" style="font-size:12px; padding:2px; border:1px solid grey; color:#CC0000;">PDF Confirmation</a>';
						$pdfShown = true;
					}
				}
			}
			echo "</p>\n";
		}
		$showActions .= $pdfConfStr;

	}

	$showActions .= '<input type="hidden" name="cancelres" value="" />';

	$cancel_but =  '<button  type="button" class="list-group-item" ';
	$cancel_but .= 'onclick="if(confirm(\'Click OK to cancel this reservation\')){document.form_a.cancelres.value=1; document.form_a.submit();}" >';
	$cancel_but .=  '<img src="/images/delete.png" width="16" height="16" alt="" align="absmiddle"/>&nbsp;Cancel This Reservation</button>';



	if ($cancel_show && date("Y/m/d",strtotime($res->resdate)) >= date("Y/m/d",strtotime("now")))
		$showActions .= $cancel_but;
	elseif ($res->active && $customer['auth']>=2 && date("Y/m/d",strtotime($res->resdate)) > date("Y/m/d",strtotime("now")))
		$showActions .= $cancel_but;

	// first two conditions have same value if true.
	if ($revive_show && date("Y/m/d",strtotime($res->resdate)) >= date("Y/m/d",strtotime("now"))) {
		$showActions .=  '<button  type="button" class="list-group-item" onClick="this.form.submit();" name="revive">Re-activate This Reservation</button>';
} elseif (!$res->active && $customer['auth']>=4 && date("Y/m/d",strtotime($res->resdate)) >= date("Y/m/d",strtotime("now"))) {
		$showActions .=  '<input type="hidden" name="revive" value="revive" />';
                $showActions .=  '<button  type="button" class="list-group-item" onClick="this.form.submit();" name="revive">Re-activate This Reservation</button>';
        }

	if ( 1 )
	{
		echo '<form name="form_a" method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$res->conf.'">
			<div class="list-group">
			<span style="padding:0 7px; font-style:italic;"  class="list-group-item active">Actions</span>';
			if (($res->garageName!=='South Stadium Garage') and ($res->garageName!=='Highland Avenue Garage')) {
		echo $showActions;
			}
		echo "</div></form>\n";
	}
	if ($customer['auth']>=4)
	{
		if (date("Y/m/d",strtotime($res->resdate)) >= date("Y/m/d",strtotime("now")))
			echo '<p align="center"><b><a href="edit.php?id='.$res->conf."\">Edit This Reservation$dateStr</a></b></p>\n";
		else
			echo '<div align="center" style="color:red;"><b>This reservation date can no longer be edited.<br></b></div>';
	}
	echo '<div class="resBox" style="text-align:left; font-size:16px;">';
	if ($pdfConfStr)
		echo "<div style='padding-left:44px;'><b>" . $pdfConfStr . "</b></div>\n";
	echo "\n<p><b>Reservation ID:</b> $res->conf</p>\n";

	echo "<div style='font-weight:bold;'>Date(s): &nbsp;";
	echo "<span class='thetip' title='Reservation ID ".$res->conf."'>$res->resdate</span>";

	if (count($res->otherDates) > 1)
	{
		foreach ($res->otherDates as $k_resid => $v_date)
		{
			if ($res->conf == $k_resid)
				continue;
			else
				echo ", &nbsp;&nbsp;<a href='view.php?id=$k_resid' class='thetip' title='Reservation ID ".$k_resid."'>$v_date</a>";
		}
	}
	echo "</div>\n";

	echo "<b>Enter:</b> $res->resenter &nbsp; &nbsp; <b>Exit:</b> $res->resexit</p>\n";

	$makePDF = preg_match('/(BioMedical)/i', $res->garageName);
	$garageTxt = $res->garageName;
	if ($makePDF) {
		$garageTxt = 'Phoenix BioMedical Campus '; // Don't want "Phoenix BioMedical 10003"
		$pbc_lot_num = preg_match('/(10003)/i', $res->garageName) ? '10003' : '10002';
		$pbc_lot_loc = ($pbc_lot_num=='10003') ? "Lot 10003, Located at 550 E Van Buren, 85004" : "Lot 10002, Located at 714 E Van Buren, 85004";
		$garageTxt .= " <a href='https://parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf' target='_blank'>".$pbc_lot_loc."</a>";
	}

	//xxxxxxxxxx	if (preg_match('/bio.?med/si', $res->garageName))	{
	//		$garageTxt = "Phoenix BioMedical Campus<b></b> ";
	//							  "<a href='http://parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf' target='_blank'>Parking Lot 10002" .
	//							  ",&nbsp; 714 E Van Buren</a>";
	//	}

	echo "<p><b>Garage:</b> ".$garageTxt."</p>\n";
	if ($gg=="group") echo "<p><b>Group Name:</b> {$res->guestList[0]} &nbsp; &nbsp; <b>Spaces:</b> ".$res->groupCount[0];
	else {
		$res->resinfo['guestList'] = implode(" | ",$res->guestList);
		echo "<p><b>Guest List:</b> ".$res->resinfo['guestList'];
	}
	
	$comego=($res->comego) ? 'Yes' : 'No';
	if ($garageTxt==='South Stadium Garage' || $garageTxt==='Highland Avenue Garage') {
		$comego=Yes;
	}
	//echo " &nbsp; &nbsp; <b>Additional Guests:</b> $res->addguests</p>\n";
	echo "<p><b>Guests May Come and Go:</b> ".$comego;
	//if ($customer['auth']>=3) echo " &nbsp; &nbsp; <b>Allow Extra:</b> ".(($res->allowextra) ? 'Yes' : 'No');
	echo "</p>\n<p><b>Created On:</b> ".$res->resinfo['CREATION_DATE'][0]." by $res->userName</p>\n";
	echo "</p>\n";
	echo "</div></div>\n";

	if ($receipt) echo '<p align="center"><b><a href="create.php">Make Another Reservation</a> &nbsp; :: &nbsp; <a href="index.php">Home</a></b></p>';
	else {
		echo '<p align="center"><b><a href="index.php?view='.urlencode($_GET['view']).'&searchString='.urlencode($_GET['searchString']).'&searchType='.urlencode($_GET['searchType']).'&sh_DEPT_NO_FK='.urlencode($_GET['sh_DEPT_NO_FK']).'&sh_USER_NAME='.urlencode($_GET['sh_USER_NAME']).'">&lt;&lt; Back</a></b></p>';

		echo "<h3 align=\"center\">History</h3>\n";

		//$dbConn->query("SELECT N.*,TO_CHAR(DATE_RECORDED,'MM-DD-YY HH:MI AM') AS DATERECORDED,U.USER_NAME FROM PARKING.GR_RESERVATION_NOTE N INNER JOIN PARKING.GR_USER U ON USER_ID_FK=USER_ID WHERE RESERVATION_ID_FK=$res->conf ORDER BY DATE_RECORDED DESC");
		$query = "SELECT N.*,TO_CHAR(DATE_RECORDED,'MM-DD-YY HH:MI AM') AS DATERECORDED,U.USER_NAME FROM PARKING.GR_RESERVATION_NOTE N INNER JOIN PARKING.GR_USER U ON USER_ID_FK=USER_ID WHERE RESERVATION_ID_FK=:resid ORDER BY DATE_RECORDED DESC";
		$qVars = array('resid' => $res->conf);
		$dbConn->sQuery($query, $qVars);

		if (!$dbConn->rows) echo '<p class="warning">No History</p>';
		else {
			  // Display below includes cashier display options - why for customer?
			  //  (this should only be an admin function?) - jsc - 20070916
		          //echo '<table border="0" cellpadding="0" cellspacing="0" align="center" class="resultsTable">
			echo '<table border="0" cellpadding="0" cellspacing="0" align="center" class="resultsTable">
	<tr class="title"><td>Date/Time</td><td>User</td><td>Note</td><td>Size Change</td></tr>
	';
			for ($i=0; $i<$dbConn->rows; $i++) {
			  // Display below includes cashier display options - why for customer?
			  //  (this should only be an admin function?) - jsc - 20070916
			  //echo '	<tr valign="top"><td>'.$dbConn->results['DATERECORDED'][$i].'</td><td>'.$dbConn->results['USER_NAME'][$i].'</td><td>'.$dbConn->results['NOTE'][$i].'</td><td>'.$dbConn->results['SIZE_CHANGE'][$i].'</td><td>'.yesNo($dbConn->results['CASHIER_DISPLAY'][$i])." <span class=\"note\"><a href=\"view.php?id=$res->conf&cashtoggle=".$dbConn->results['CASHIER_DISPLAY'][$i]."&date=".urlencode($dbConn->results['DATERECORDED'][$i])."\">Change</a></span></td></tr>\n";
				echo '	<tr valign="top"><td>'.$dbConn->results['DATERECORDED'][$i].'</td><td>'.$dbConn->results['USER_NAME'][$i].'</td><td>'.$dbConn->results['NOTE'][$i].'</td><td>'.$dbConn->results['SIZE_CHANGE'][$i]."</td></tr>\n";
			}
			echo "</table>\n";
		}
	}
}
?>
	 <br /> <br /> <br /> <br /> <br /><br /> <br /> <br /><br /> <br /> <br />

	 </div>
  </div>
</div>

</div>
</div>
<?php
include_once $docRoot.'/Templates/bottom_footer.php';
?>
</body>
</html>
