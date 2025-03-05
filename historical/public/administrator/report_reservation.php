<h1>Garage Reservations</h1>
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

    $start = field_factory::get_short_date_field('Start', null, 'last monday', false);
    $start->get_renderer()->set_nolabel();
    $start->get_renderer()->set_leading_text('Display reservations from ');
    $start->get_renderer()->set_trailing_text(' to ');
    $end = field_factory::get_short_date_field('End', null, 'today', false);
    $end->get_renderer()->set_nolabel();

    $titles = array('FRS_FK' => 'Account Number', 'GARAGE_ID_FK' => 'Garage');
    $items = new single_select_group($titles);
    $menu = new data('Organize results by', $items);
    $menu->set_renderer(new list_renderer('menu'));
    $control->add($menu);

    $sub = new data('View');
    $sub->set_renderer(new button_renderer(false));

    $back = new data('Done');
    $back->set_renderer(new button_renderer(false, 'index.php'));

    $control->add(field_factory::get_item_row(new collection($start, $end, $sub, $back)));

    $results = $control->get_xml();

    try {
        allset('Start', 'End', 'Organize_results_by');
    } catch(Exception $e) {
        return $results;
    }

    $db = get_db();
    $start_date = $_POST['Start'];
    $end_date = $_POST['End'];
    $group = $_POST['Organize_results_by'];

    $data_items = new collection();
    $home_tables = array('FRS_FK' => 'GR_FRS', 'GARAGE_ID_FK' => 'GR_GARAGE');
    $home_ids = array('FRS_FK' => 'FRS', 'GARAGE_ID_FK' => 'GARAGE_ID');
    $home_names = array('FRS_FK' => 'DESCRIPTION', 'GARAGE_ID_FK' => 'GARAGE_NAME');
    $pop = new recordset_populator($data_items, array('GR_RESERVATION', 'GR_GUEST', $home_tables[$group]), array($home_names[$group], 'RESERVATIONS', 'GUESTS', 'TOTAL_COST'), "RES_DATE between to_date('$start_date', 'mm/dd/yy') and to_date('$end_date', 'mm/dd/yy') and RESERVATION_ID_FK = RESERVATION_ID and PARKING.GR_RESERVATION.ACTIVE = 1 and $group = ".$home_ids[$group]);
    $pop->set_select_expression('RESERVATIONS', 'count(distinct RESERVATION_ID)');
    $pop->set_select_expression('GUESTS', 'sum(GROUP_SIZE)');
    $pop->set_select_expression('TOTAL_COST', 'sum(GROUP_SIZE * PRICE)');

    // Combine FRS number and account description
    if($group == 'FRS_FK') {
        $pop->set_select_expression('DESCRIPTION', "FRS_FK || ' - ' || DESCRIPTION");
        $pop->set_group("FRS_FK || ' - ' || DESCRIPTION");
    }

    else $pop->set_group($home_names[$group]);
    $pop->set_components($group, 'res', 'guests', 'get_money_display');
    $pop->set_headings($titles[$group], 'Reservations', 'Guests', 'Price');
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
