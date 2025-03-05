<h1>Guest Activity By Month</h1>
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

<form method="post" action="/parking/garage-reservation/administrator/report_monthlyguest.php">
<table class="form_table"><tbody>
<tr class="form_item">
<td class="label">Garage</td>
<td class="field_label"><select name="Garage"><option value="1">Main Gate Garage</option>
<option value="2">Park Avenue Garage</option>
<option value="3">Second Street Garage</option>
<option value="4">Sixth Street Garage</option>
<option value="5">Tyndall Avenue Garage</option>

<option value="7">Highland Avenue Garage</option></select></td>
</tr>
<tr class="form_item">
<td class="label">Date</td>
<td class="field_label">
<input type="text" name="Date" value="08/08/05" class="field" maxlength="8" size="8"/><span class="note"> MM/DD/YY</span>
</td>
</tr>
<tr class="form_item"><td colspan="2" class="form_raw">
<input name="submit_button" type="submit" value="View" class="default_button"/>
<input name="submit_button" type="submit" value="Done" class="default_button"/><input type="hidden" name="form_goto_done" value="index.php"/>
</td></tr>
</tbody></table>

<input type="hidden" name="form_submitted" value=""/>
</form>

<?php
/*function get_report() {
    $control = new form();

    $gar = database_populated_menu::get_menu('Garage', 'GARAGE_ID', 'GARAGE_ID_FK', 'GARAGE_NAME', 'GR_GARAGE', new record_populator(new collection()));
    $control->add($gar);

    $month = field_factory::get_months_menu('Month', null, 'today');
    $control->add($month);

    $view = field_factory::get_button('View', false);
    $back = field_factory::get_button('Done', false, 'index.php');
    $control->add(field_factory::get_item_row(new collection($view, $back)));

    $results = $control->get_xml();

    try {
        allset('Garage', 'Date');
    } catch(Exception $e) {
        return $results;
    }*/

	if (isset($_POST['Garage'])) {
	$db = get_db();
    $gar = $_POST['Garage'];
    $datePieces = explode("/",$_POST['Date']);
	$startdate = $datePieces[0]."/01/".$datePieces[1];
	$enddate = $datePieces[0]."/".date("t",strtotime($startdate))."/".$datePieces[1];

    $db->query("SELECT COUNT(RESERVATION_ID) AS DAILYTOTAL,GARAGE_ID_FK,TO_CHAR(RES_DATE,'MM/DD/YYYY') AS RESDATE,PRICE FROM PARKING.GR_RESERVATION WHERE GARAGE_ID_FK=$gar RES_DATE BETWEEN TO_DATE('$startdate','MM/DD/YYYY') AND TO_DATE('$enddate','MM/DD/YYYY') GROUP BY GARAGE_ID_FK,RES_DATE,PRICE");

	$results .= '<tr class="form_item"><td colspan="2"><table class="data_grid"><thead><tr class="heading">\n';
	for ($i=1; $i<=date("t",$startdate); $i++) {
		$results .= '<td class="grid_item">'.date("m/d",strtotime($datePieces[0]."/$i/".$datePieces[1])).'aa</td>\n';
	}
	$results .= '</tr></thead>\n<tbody>';
	$currPrice = 0;
	$dateCount = 1;
	$totals = array();
	foreach($db->get_results() as $res) {
		if ($res['PRICE']!=$currPrice) {
			if ($currPrice!=0) $results .= "</tr>";
			$results .= '<tr class="grid_row">
		<td class="grid_item">'.$res['PRICE'].'bb</td>';
			$dateCount = 1;
		}
		if ($res['RESDATE']==$datePieces[0]."/$dateCount/".$datePieces[1]) $results .= '<td class="grid_item">'.$res['DAILYTOTAL'].'cc</td>\n';
		else $results .= '<td>&nbsp;</td>\n';
		$currPrice = $res['PRICE'];
		$dateCount++;
		if (isset($totals[$res['RESDATE']])) $totals[$res['RESDATE']] += $res['DAILYTOTAL'];
		else $totals[$res['RESDATE']] = $res['DAILYTOTAL'];
    }

	$results .= '</tr><tr class="grid_row"><td class="grid_item">&nbsp;</td>';
	for ($i=1; $i<=date("t",$startdate); $i++) {
		$results .= '<td class="grid_item">'.$totals[$datePieces[0].'/$i/'.$datePieces[1]].'</td>';
	}
	$results .=  "</tr>";
	$results .= "</tbody></table>\n";
		/*$data_items = new collection();
		$report = new data('', $data_items);
        $report->set_renderer(new list_renderer('grid'));
        $results .= $report->get_xml();*/
		echo $results;
	}
/*}

function allset() {
    for($i=0; $i<func_num_args(); $i++) {
        if(!isset($_POST[func_get_arg($i)]))
           throw new Exception();
    }
}

function resubmit_report() {
    return get_report();
}*/
?>
