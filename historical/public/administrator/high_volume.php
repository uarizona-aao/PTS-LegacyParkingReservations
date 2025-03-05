<h1>High Visitor Volume</h1>
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

<dynamic content="volume"/>

<?php
function get_volume() {
    $limit = 25;

    $items = new form("High Visitor Volume Reservations");

    $res = new collection();
    $res_data = new recordset_populator
    (
     $res,
     array('GR_RESERVATION', 'GR_GARAGE', 'GR_GUEST'),
     array('RESERVATION_ID', 'RES_DATE', 'ENTER_TIME', 'EXIT_TIME', 'GARAGE_NAME', 'GUESTS_OFFCAMPUS', 'GROUP_SIZE'),
     "GARAGE_ID_FK = GARAGE_ID and (GUESTS_OFFCAMPUS + GROUP_SIZE > $limit or GROUP_SIZE > $limit) and RES_DATE > trunc(sysdate) and ACTIVE = 1 and RESERVATION_ID_FK = RESERVATION_ID",
     "TO_DATE(RES_DATE,'MM/DD/YYYY'), GARAGE_NAME, to_date(ENTER_TIME, 'HH:MI AM') asc"
     );
    $res_data->set_select_expression('RES_DATE', "to_char(RES_DATE, 'MM/DD/YY')");
    $res_data->set_select_expression('ENTER_TIME', "to_char(ENTER_TIME, 'HH:MI AM')");
    $res_data->set_select_expression('EXIT_TIME', "to_char(EXIT_TIME, 'HH:MI AM')");
    $res_data->set_select_expression('GROUP_SIZE', 'sum(GROUP_SIZE)');
    $res_data->set_headings('id', 'Date', 'Enter', 'Exit', 'Garage', 'Estimated Guests', 'Reserved');
    $res_data->set_components('id', 'resdate', 'enter', 'exit', 'garage', 'offc', 'res');
    $res_data->set_id_link('resdate', "reservation.php?volume=true");
    $res_data->set_group('RESERVATION_ID, RES_DATE, ENTER_TIME, EXIT_TIME, GARAGE_NAME, GUESTS_OFFCAMPUS');

    if($res_data->populate()) {
        $list_data = new data('Reservations', $res);
        $list_data->set_renderer(new list_renderer('grid'));
        $items->add($list_data);
    }
    else $items->add(field_factory::get_note('<i>No reservations.</i>'));

    $items->add(field_factory::get_button('Done', true, 'index.php'));

    return $items;

}
?>
