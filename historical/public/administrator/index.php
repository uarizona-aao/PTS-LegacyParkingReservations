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

if ($auth < 3)
	exitWithBottom('You are not authorized.');

?>

<h2>Reservations</h2>
<p><a href="reservation_calendar.php">Reservation Viewer/Editor</a></p>
<p><a href="http://www.pts.arizona.edu/web/reservations_fso.php">Reservation Viewer/Editor by KFS</a></p>
<p><a href="reserve.php">Make New Reservation</a></p>
<p><a href="confirmation_lookup.php">Check Confirmation Number</a></p>
<p><a href="view_exceptions.php">View Exception Reports</a>
[<b><?php
if($dbConn->query("select count(*) NUM from PARKING.GR_GUEST_EXCEPTION where COMPLETE_USER_FK is null"))
{
	$num = $dbConn->results['NUM'];
	echo $num ? $num : 'No';
}
?> New</b>]
</p>

<p><a href="high_volume.php">Expected High Visitor Volumes</a>

<!-- [<b>
<?php
$limit = 25;
	//$hv = $dbConn->query("
	//select count(*) NUM from PARKING.GR_RESERVATION where RES_DATE > trunc(sysdate) and ACTIVE = 1
	//AND (((select sum(group_size) from parking.gr_guest where reservation_id_fk = reservation_id) > $limit)
	//OR GUESTS_OFFCAMPUS + (select sum(group_size) from parking.gr_guest where reservation_id_fk = reservation_id) > $limit
	//)
	//");
	//if($hv) $num = $dbConn->get_from_top('NUM');
	//echo $num ? $num : 'None';

?>
Upcoming
</b>] -->

</p>

<h2>Billing</h2>
<p><a href="payment.php">Accept Payment</a> [<b><?php
if($dbConn->query("select count(*) NUM from PARKING.GR_RESERVATION where PAYMENT_ID_FK is null")) {
	$num = $dbConn->results['NUM'];
	echo $num ? $num : 'None';
}

?> Outstanding</b>]</p>
<p><a href="invoice.php">Invoices</a></p>
<p><a href="find_payment_number.php">Find IBF or Check Number</a></p>


<h2>Reports</h2>
<p><a href="../report_users.php">NEW - Online Reservation Users</a></p>
<p><a href="report_reservation.php">Reservation Report</a></p>
<p><a href="report_time.php">Hourly Space Usage</a></p>
<p><a href="report_placement.php">Reservations Made Daily</a></p>
<p><a href="report_initials.php">Garage Attendant Initials</a></p>
<p><a href="report_guests.php">Daily Guest Activity Report</a></p>
<p><a href="https://www.pts.arizona.edu/web/guest_monthly.php">Monthly Guest Activity Report</a></p>
<p><a href="http://www.pts.arizona.edu/web/report_fso.php" target="_blank">FRS Totals Report</a></p>

<div style="background-color: #EEF; border: 1px solid #DDE; width: 25em; margin-left: 1em;">

<p><b>Reservations for Today:</b>
<?php
if($dbConn->query("select GARAGE_ID, GARAGE_NAME, sum(GROUP_SIZE) num, VISITOR_MAX from parking.gr_guest, parking.gr_reservation, parking.gr_garage where reservation_id_fk = reservation_id and garage_id_fk = garage_id and trunc(res_date) = trunc(sysdate) and ACTIVE = 1 group by garage_id, garage_name, visitor_max order by garage_name")) {
	for ($i=0; $i<$dbConn->rows; $i++) {
		$remaining = $dbConn->results['VISITOR_MAX'][$i] - $dbConn->results['NUM'][$i];
		echo '<br/><a href="/parking/garage-reservation/cashier/daily_listing.php?id='.$dbConn->results['GARAGE_ID'][$i].'">'.$dbConn->results['GARAGE_NAME'][$i] .'</a>: '. $dbConn->results['NUM'][$i] .' / '. $dbConn->results['VISITOR_MAX'][$i]." ($remaining free)";
	}
}
else
{
	echo "<br/><i>No Reservations</i>";
}
?>
</p></div>


<h2>Setup</h2>
<p><a href="users.php">System Users</a></p>
<p><a href="customers.php">Departments and Accounts</a></p>
<p><a href="find_frs_dept.php">Find Department by KFS Number</a></p>
<p><a href="garages.php">Garages</a></p>

<table></table>