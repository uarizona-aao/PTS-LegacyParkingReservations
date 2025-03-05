<h1>Department Detail</h1>
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

<dynamic content="cust"/>

<?php
function get_cust() {
    $id = isset($_POST['Customer']) ? $_POST['Customer'] : 0;
    if(!isset($_POST['submit_button']) or !$id) url::redirect('customers.php');

    $form = new form('Department and Account Detail');
    $pop = new record_populator($form, 'GR_ADDRESS', 'DEPT_NO_FK');
    $pop->add_condition("DEPT_NO = '$id'");
    $pop->set_insert_extra('DEPT_NO_FK', "'$id'");

    $cust = new database_data('Name', 'DEPT_NAME', 'GR_DEPARTMENT');
    $cust->set_renderer(new form_display_renderer());
    $form->add($cust);

    $deptid = new database_data('Department No.', 'DEPT_NO', 'GR_DEPARTMENT');
    $deptid->set_renderer(new form_display_renderer());
    $form->add($deptid);

    $db = get_db();
    $db->query("select DEPT_NO_FK from PARKING.GR_ADDRESS where DEPT_NO_FK = '$id'");

    $new_addr = ($_POST['submit_button'] == 'Insert Alternate Address' or $_POST['submit_button'] == 'Insert Address');

    // Address
    if($db->num_rows() or $new_addr) {
        if($new_addr) {
            $pop->populate();
            $btn_name = 'Insert Address';
        }
        else $btn_name = 'Save Address';
        $street = new database_data('Street', 'STREET', 'GR_ADDRESS');
        $street->set_renderer(new field_renderer());
        $form->add($street);
        $city = new database_data('City', 'CITY', 'GR_ADDRESS');
        $city->set_renderer(new field_renderer());
        $form->add($city);
        $state = new database_data('State', 'STATE', 'GR_ADDRESS');
        $state->set_renderer(new field_renderer());
        $form->add($state);
        $zip = new database_data('Zip', 'ZIP', 'GR_ADDRESS');
        $zip->set_renderer(new field_renderer());
        $form->add($zip);

        $pop->add_condition('DEPT_NO_FK = DEPT_NO');
        if(!$new_addr) $pop->populate();
    }

    // PO Box
    else {
        $pobox = new database_data('Campus PO Box', 'PO_BOX', 'GR_DEPARTMENT');
        $pobox->set_renderer(new form_display_renderer());
        $form->add($pobox);
        $btn_name = 'Insert Alternate Address';
        $pop->populate();
    }



    $acct_coll = new collection();
    $acct_pop = new recordset_populator($acct_coll, 'GR_FRS', array('FRS', 'DESCRIPTION'), "DEPT_NO_FK = '$id' and ACTIVE = 1", 'FRS');
    $acct_pop->set_headings('FRS', 'Description');
    $acct_pop->set_components('frs', 'desc');
    $acct_pop->populate();
    $acct_data = new data('account', $acct_coll);
    $acct_data->set_renderer(new list_renderer('grid'));
    $form->add($acct_data);

    $hidey = new data('Customer', $id);
    $hidey->set_renderer(new hidden_renderer());

    $editbtn = field_factory::get_button($btn_name, false);
    $addr = isset($_GET['view']) ? 'customers.php?view='.$_GET['view'] : 'customers.php';
    $cancel = field_factory::get_button('Go Back', false, $addr);

    $form->add(field_factory::get_item_row(new collection($hidey, $editbtn, $cancel)));

    $form->set_populator($pop);
    return $form;
}

function validate_cust() {
    if($_POST['submit_button'] == 'Insert Address' or $_POST['submit_button'] == 'Save Address') return true;
    return false;
}

function submit_cust($form) {
    $pop = $form->get_populator();
    if($_POST['submit_button'] == 'Insert Address') $pop->insert();
    else $pop->update("'".$_POST['Customer']."'");
    return get_cust();
}

function resubmit_cust($form) {
    return submit_cust($form);
}

function get_action() {
    if(validate_cust()) return 'customers.php';
    return 'customer_detail.php';
}
?>
