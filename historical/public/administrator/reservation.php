<h1>Reservation Details</h1>
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
if (!$_SESSION['standardUser'])
{
	if ($auth < 3)
		exitWithBottom('You are not authorized.');
}
?>

<dynamic content="reservation_protected"/>

<?php
require_once '../reservation_lib.php';

if(isset($_GET['volume'])) goback('high_volume.php');
else if(!isset($_GET['date'])) goback('report_placement.php');
else goback('reservations_day.php?date='.$_GET['date']);

global $session;
$session->unset_object('guestlist');
$session->unset_object('duplicates');

function get_reservation_protected() {
    // Check for cancelation (can't edit if canceled)
    $db = get_db();
	 if (!is_object($GLOBALS['auth'])) $GLOBALS['auth'] = new authorization_garage_reservation(); //jody
    $id = $_GET['id'];
    $db->query("select ACTIVE from PARKING.GR_RESERVATION where RESERVATION_ID = $id");
    $active = $db->get_from_top('ACTIVE');
    if($GLOBALS['auth']->get_authorization() > 3 and $active) {
			if (!$_SESSION['standardUser']) {
				updprice();
				updateres();
				deleteres();
			}
    }
    else if(!$active) canceledres();
    return get_reservation(true);
}

function submit_reservation_protected($form) {
	$pop = $form->get_populator();
	$id = $_GET['id'];
	$submit = $_POST['submit_button'];
	if($submit == 'Update') {
		$pop->update($id);

		$note = $form->get_by_name('Notes')->get_database_value();
		//colin added below line and commented this out on 7-18-05 to fix error//$showcash = $res_form->get_by_name('Show Notes to Cashier')->is_selected() ? 1 : 0;
		$showcash = $form->get_by_name('Show Notes to Cashier')->is_selected() ? 1 : 0;
		save_note($id, $note, 'null', $showcash);

		return 'Reservation updated.';
	}
	else if($submit == 'Cancel Reservation')
	{
		$db = get_db();
		$db->execute("update PARKING.GR_RESERVATION set ACTIVE = 0 where RESERVATION_ID = $id");

		if (!is_object($GLOBALS['auth'])) $GLOBALS['auth'] = new authorization_garage_reservation(); //jody
		// 20151007 jody.
		$user_n = $GLOBALS['auth']->get_user_name();
		$note = "Cancelled at " . date('Y-m-d H:i:s');
		$note .= $_POST['Notes'] ? "\n".$_POST['Notes'] : '';
		$showcash = $form->get_by_name('Show Notes to Cashier')->is_selected() ? 1 : 0;
		save_note($id, $note, 'null', $showcash);

		//$pop->delete($id);
		return 'Reservation canceled.';
   }
}

function get_action() {
	$date = (isset($_GET['date'])) ? $_GET['date'] : strtoupper(date("d-M-y"));
	return 'reservations_day.php?date='.$date;
}
?>