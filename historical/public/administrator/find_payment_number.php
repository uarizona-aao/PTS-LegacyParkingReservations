<h1>Find Payment Number</h1>
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

<dynamic content="search"/>

<?php
function get_search() {
    $form = new form();

    $num = new data('IBF or Check Number');
    $num->set_validator(new data_validator('/^[0-9]+$/', 0, true));
    $num->set_renderer(new field_renderer());
    $form->add($num);

    $form->add(field_factory::get_item_row(new collection(field_factory::get_button('Search', false), field_factory::get_button('Go Back', false, 'index.php'))));

    return $form;
}

function submit_search($form) {
    $num = $_POST['IBF_or_Check_Number'];
    $db = get_db();
    if($db->query("select RESERVATION_ID from PARKING.GR_PAYMENT, PARKING.GR_RESERVATION where PAYMENT_NUMBER = '$num' and PAYMENT_ID_FK = PAYMENT_ID and ACTIVE = 1"))
        url::redirect("reservation.php?id=".$db->get_from_top('RESERVATION_ID'));
}
?>
