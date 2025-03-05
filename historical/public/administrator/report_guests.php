<h1>Guest Activity Report</h1>
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
   if (isset($_GET['garage'])) $gar = $_GET['garage'];
	else $gar = $_POST['Garage'];
   if (isset($_GET['date'])) $date = substr($_GET['date'],0,2)."/".substr($_GET['date'],2,2)."/".substr($_GET['date'],-2);
	else $date = $_POST['Date'];

    $db->query("select (sum(GROUP_SIZE)+SUM(ADDON)) AS TOTAL, sum(GROUP_EXITED) PARKED, (sum(GROUP_SIZE) - sum(GROUP_EXITED)) NOSHOW,PRICE from PARKING.GR_GUEST, PARKING.GR_RESERVATION
where trunc(RES_DATE) = to_date('$date', 'mm/dd/yy') and RESERVATION_ID_FK = RESERVATION_ID and ACTIVE = 1 and GARAGE_ID_FK = $gar GROUP BY PRICE");

	$rows = $db->num_rows();
	$dbResults = $db->get_results();
	$writeDate = date("D, M j, Y",strtotime($date));
	$results .= "<h3 align=\"center\">For $writeDate</h3>\n";
	$results .= '<p class="center">';
	$total = 0;
	$parked = 0;
	$noshow = 0;
	foreach ($dbResults as $row) {
		$results .= '<b>$'.$row['PRICE'].':</b> '.$row['TOTAL'].' | ';
		$total += $row['TOTAL'];
		$parked += $row['PARKED'];
		$noshow += $row['NOSHOW'];
	}
	$results .= "<b>Total:</b> $total | <b>Parked:</b> $parked | <b>No Show:</b> $noshow</p>\n";

   /* $total = $db->get_from_top('TOTAL');
    $parked = $db->get_from_top('PARKED');
    $noshow = $db->get_from_top('NOSHOW');

    $db->query("select (sum(GROUP_SIZE)+SUM(ADDON)) AS TOTAL from PARKING.GR_GUEST, PARKING.GR_RESERVATION
where trunc(RES_DATE) = to_date('$date', 'mm/dd/yy') and ACTIVE = 1 and RESERVATION_ID_FK = RESERVATION_ID and GARAGE_ID_FK = $gar and PRICE = 4");
    $four = $db->get_from_top('TOTAL');

    $db->query("select (sum(GROUP_SIZE)+SUM(ADDON)) AS TOTAL from PARKING.GR_GUEST, PARKING.GR_RESERVATION
where trunc(RES_DATE) = to_date('$date', 'mm/dd/yy') and ACTIVE = 1 and RESERVATION_ID_FK = RESERVATION_ID and GARAGE_ID_FK = $gar and PRICE = 6");
    $six = $db->get_from_top('TOTAL');
    if(!$six) $six = '0';

    $results .= "<p class=\"center\">\$4 Daily: $four | \$6 Daily: $six | Total: $total | Parked: $parked | No Show: $noshow</p>";*/

   $query = "select RESERVATION_ID, FRS_FK, DEPT_NAME, PRICE,TO_CHAR(ENTER_TIME,'HH:MI AM') AS ENTERTIME,TO_CHAR(EXIT_TIME,'HH:MI AM') AS EXITTIME from PARKING.GR_RESERVATION, PARKING.GR_DEPARTMENT
where trunc(RES_DATE) = to_date('$date', 'mm/dd/yy') and ACTIVE = 1 and DEPT_NO_FK = DEPT_NO and GARAGE_ID_FK = $gar";

	$db->query($query);
	foreach($db->get_results() as $res) {
		$id = $res['RESERVATION_ID'];
		$dept = $res['DEPT_NAME'];
		$price = $res['PRICE'];
		$frs = $res['FRS_FK'];
		$times = $res['ENTERTIME']." to ".$res['EXITTIME'];

		$dept = preg_replace('/&(\s)/', '&amp;$1', $dept); //jjj the xml crashes if '&' is not escaped to '&amp;'.

		$results .= "<h2>$dept ($frs) \$$price - $times</h2>";

		$data_items = new collection();
		$pop = new recordset_populator
		   (
		    $data_items,
		    array('GR_GUEST'),
		    array('GUEST_NAME', 'GROUP_EXITED', 'ADDON',"GROUP_SIZE"),
		    	"RESERVATION_ID_FK = $id",
		    	"GROUP_EXITED desc, upper(SORT_NAME)"
		    );
		//$pop->set_select_expression('INI', "(select GROUP_EXITED from PARKING.GR_GUEST where GUEST_NAME = GUEST_NAME and RESERVATION_ID_FK = $id group by GUEST_NAME_FK)");

		$pop->set_components('guest', 'count', 'add', "group_size");
		$pop->set_headings('Guest', 'Initials', 'Add-On', "Spaces");
		$pop->populate();
		$report = new data('', $data_items);
		$report->set_renderer(new list_renderer('grid'));
		$results .= $report->get_xml();
	}
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
