<?php
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
		<li class="active">Garage Reservation</li>
	 </ol>
	 <h1  class="page-heading">Department Visitor Garage Reservation</h1>
	 <hr />
	 <div id="editableContent">
		<br><br>
		<div align="center">
		  <a href="/parking/garages/visitor-parking" style="margin:0 auto; font-size:16px; padding:6px; font-weight:bold;">Garage Schedules / Visitor Parking Information</a>
		</div>
		<br/><br/><br/>
		<br /><br /><br />


<?php

if ($auth >= 2)
{
	$customer = $_SESSION['cuinfo'];

	if (!isset($_POST['killSelected']))
		locationHref('/parking/garage-reservation/index.php');

	$res = array();

	foreach ($_POST as $key=>$val) {
		if (substr($key,0,6)=='cancel') $res[] = substr($key,6);
	}

	if (!count($res))
		locationHref('/parking/garage-reservation/index.php?msg=noselect');

	require_once("gr/reservation_functions.php");
	$resObj = new reservation();
	$testOwner = $resObj->checkMultiResOwner($customer['userid'],$res);

	if (isset($_POST['killYeah']) && !count($testOwner['fail']) && count($testOwner['pass'])) {

		// If editing PBC, have them call offoce. (see also view.php and cancel.php and edit.php)
		if (preg_match('/bio.?med/si', $resObj->resinfo['GARAGE_NAME'][0])) {
			locationHref('/index.php?msg=nopbc');
		} else {
			$resObj->cancelRes($testOwner['pass']);
			locationHref('/index.php?msg=multicancel');
		}
	}

	echo "<h1>Cancelling Reservations</h1>\n";
	echo '<div style="text-align:center;"><div style="margin:0 auto; text-align:left; background-color:#F1F1F1; border:solid 4px #CCCCCC;">';
	//		if (date("Y/m/d",strtotime($res->resdate)) > date("Y/m/d",strtotime("now"))) echo '<p class="warning">Error</p>\n"';
	if (count($testOwner['fail'])) echo '<p class="warning">Error: Since you did not make the following reservations, you cannot cancel them at this time. Please contact PTS Visitor Programs during normal business hours.<br/>'.implode("<br/>\n",$testOwner['fail'])."</p>\n";
	echo '<form method="post" action="cancel.php" method="post"><input type="hidden" name="killYeah" value="NO"/>';
	if (count($testOwner['pass'])) {
		echo '<p class="warning">You have chosen to cancel the following reservations. Please confirm them and click &quot;Continue Cancellation&quot; below. To view a reservation, click on the number.</p>';
		echo "\n<ul>\n";
		foreach ($testOwner['pass'] as $resid) {
			echo "<li><a href=\"view.php?id=$resid\" target=\"_blank\"><b>$resid</b></a>";
			echo '<input type="hidden" name="cancel'.$resid.'" value="now"/>';
			echo "</li>\n";
		}
		echo "</ul>\n";
		echo '<p align="center" class="submitter"><input type="submit" name="killSelected" value="Continue Cancellation"/> &nbsp; <input type="button" value="Nevermind" ';
		echo ' onClick="window.location.href=\'/parking/garage-reservation/index.php\';"/></p>';
		echo "</form>\n";
	}
	else echo '<p class="warning">Error: There are no reservations to cancel. Please <a href="index.php">try again</a>.</p>';
	echo "</div></div>\n";

}
?>

	 <br /> <br /> <br /> <br /> <br />

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
