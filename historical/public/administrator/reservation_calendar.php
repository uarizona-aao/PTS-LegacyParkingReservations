<h1>Garage Reservation Calendar</h1>
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

<h1>Garage Reservation Calendar</h1>

<?php
//spinnerWaiting();
//if ($GLOBALS['jody']) echo '~~~~~~~~~~~~ '.__FILE__.'<br>';

if ($_GET['standardLogout'])
	unset($_SESSION['standardUser']);

//$standardPWD = 'stand';
//if ($_POST['logStandard'] && $_POST['logStandard'] == $standardPWD) {
if ($_GET['standardUser']) {
	$_SESSION['standardUser'] = true;

} elseif ($_GET['standardUser'] && !$_SESSION['standardUser']) {
	?>
	<form method="POST" action="reservation_calendar.php?standardUser=1">
	Password: <input type="password" name="logStandard" value="">
	<input type="submit">
	</form>
	<?php
	exit;
}

if ($_SESSION['standardUser']) {
	?>
	<div style="padding-left:20px;">
	  <a href="reservation_calendar.php?standardLogout=1">logout</a>
	</div>
	<?php

} else {

	//<authorization type="garage_reservation" level="3"/>
	if ($auth < 3)
		exitWithBottom('You are not authorized.');
}
?>

<dynamic content="calendar"/>

<br />
<div class="reserved_bg"> . . . This day has reservations. </div>
<div class="reserved_bio_bg">. . . This day includes Phoenix BioMedical Campus reservations. </div>
<br />


<?php if (!$_SESSION['standardUser']) {	?>
	<p class="center">[<a href="index.php">Go Back</a>]</p>
<?php } ?>

<?php
function get_calendar()
{
	require_once 'gr_orig/calendar.php';

	$year = (isset($_GET['year']) ? $_GET['year'] : date('Y'));
	$month = (isset($_GET['month']) ? $_GET['month'] : date('m'));

	// Ensure that the given date checks out
	if(!checkdate($month, 1, $year)) {
		 $year = date('Y');
		 $month = date('m');
	}

	$start_timestamp = mktime(0,0,0,$month,1,$year);
	$start_date = date('d-M-Y', $start_timestamp);
	$end_timestamp = mktime(0,0,0,$month,date('t', $start_timestamp),$year);
	$end_date = date('d-M-Y', $end_timestamp);

	$db = get_db();
	$db->query("select RES_DATE, sum(GROUP_SIZE) SPACES from PARKING.GR_RESERVATION, PARKING.GR_GUEST where RES_DATE between to_date('$start_date', 'DD-Mon-YY') and to_date('$end_date', 'DD-Mon-YY') and ACTIVE = 1 and RESERVATION_ID_FK = RESERVATION_ID group by RES_DATE");
	$rows = $db->get_results();
	foreach($rows as $row) {
		$GLOBALS['res_count'][$row['RES_DATE']] = $row['SPACES'];

		// Find out if this date contains any Reservations for Phoneix BioMedical.
		$db2 = get_db();
		$db2->query("select GARAGE_NAME from PARKING.GR_RESERVATION JOIN PARKING.GR_GARAGE ON GARAGE_ID_FK = GARAGE_ID
						 where RES_DATE = '" . $row['RES_DATE'] . "' and ACTIVE = 1 and GARAGE_NAME like '%Bio%Medical%' and rownum < 2");
		$rows2 = $db2->get_results();
		$GLOBALS['res_bio'][$row['RES_DATE']] = false;
		foreach($rows2 as $row2)
			$GLOBALS['res_bio'][$row['RES_DATE']] = true;
	}

	$cal = new calendar($year, $month, 'gs_day');
	return $cal;
}

// Callback function for calendar
function gs_day($month, $day, $year, $oradate) {
	global $res_count, $res_bio;

	$class = NULL;
	$title = NULL;

	// Mark the day if there are already reservations!
	if(isset($res_count[$oradate])) {
		 $size = $res_count[$oradate];
		 //if($size > 50) $class = 'warning';
		 $class = 'reserved';
		 $title = "$size spaces reserved";
		 //$title = "onmouseover=\"return overlib('$size spaces reserved');\" onmouseout=\"return nd();\"";
		 if($res_bio[$oradate])
			 $class = 'reserved_bio';
	}

	return array('reservations_day.php', $class, $title);
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
