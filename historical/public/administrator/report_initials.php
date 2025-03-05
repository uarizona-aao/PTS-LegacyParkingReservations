<h1>Garage Attendant Initials</h1>
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

<dynamic content="report"/>

<?php
function get_report() {
    $control = new form();

    $gar = database_populated_menu::get_menu('Garage', 'GARAGE_ID', 'GARAGE_ID_FK', 'GARAGE_NAME', 'GR_GARAGE', new record_populator(new collection()));
    $control->add($gar);

    $day = field_factory::get_short_date_field('Date', null, 'today');
    $control->add($day);

    $view = field_factory::get_button('View', false);
    $back = field_factory::get_button('Done', false, 'index.php');
    $control->add(field_factory::get_item_row(new collection($view, $back)));

    $results = $control->get_xml();

    try {
        allset('Garage', 'Date');
    } catch(Exception $e) {
        return $results;
    }

    $db = get_db();
    $gar = $_POST['Garage'];
    $date = $_POST['Date'];

    $data_items = new collection();
    $pop = new recordset_populator
    (
     $data_items,
     array('GR_GUEST_INITIALS', 'GR_RESERVATION'),
     array('INITIALS', 'COUNT'),
     "RES_DATE = to_date('$date', 'mm/dd/yy') and RESERVATION_ID_FK = RESERVATION_ID and ACTIVE = 1 and GARAGE_ID_FK = $gar"
    );
    $pop->set_select_expression('COUNT', 'sum(GROUP_COUNT)');
    $pop->set_group("INITIALS");

    $pop->set_components('ini', 'count');
    $pop->set_headings('Initials', 'Count');
    $pop->populate();
    $report = new data('', $data_items);
    $report->set_renderer(new list_renderer('grid'));
    $results .= $report->get_xml();

    return $results;
}

function allset() {
    for($i=0; $i<func_num_args(); $i++) {
        if(!isset($_POST[func_get_arg($i)]))
           throw new Exception();
    }
}

function resubmit_report() {
    return get_report();
}
?>
