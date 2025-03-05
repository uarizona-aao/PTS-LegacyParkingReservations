<h1>Accept Payment</h1>
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

<dynamic content="payment" steps="4"/>

<?php
function get_payment_1() {
    $form = new form('Date Range');
    $range = new data('Range', 60);
    $r = new field_renderer(3);
    $r->set_leading_text('Display reservations older than ');
    $r->set_trailing_text(' days');
    $range->set_renderer($r);
    $range->set_validator(new data_validator('/^[0-9]+$/', 3, true));
    $form->add($range);
    $form->add(field_factory::get_button('Set Range'));
    return $form;
}

function get_payment_2($steps) {
    $range = $steps->get_form_cache(1)->get_by_name('Range')->get_value();

    require_once 'customer_selector.php';
    owing($range);
    return get_cust();
}

function get_payment_3($steps) {
    require_once '../reservation_list.php';
    return get_list($steps->get_form_cache(2)->get_by_name('Customer')->get_value()->get_selected_item()->get_value());
}

function verify_payment_3($steps) {
    if(!sizeof($steps->get_form_cache(3)->get_checks()))
        throw new step_exception('Please select at least one reservation.');
}

function get_payment_4($steps) {
    $id_list = implode(',', $steps->get_form_cache(3)->get_checks());

    $form = new form('New Payment');
    $pop = new record_populator($form, 'GR_PAYMENT', 'PAYMENT_ID', 'GR_PAYMENT_ID');
    $pop->set_insert_extra('PAYMENT_DATE', 'sysdate');
    $pop->set_insert_extra('ACCOUNTING_USER', $GLOBALS['auth']->get_user_id());
    $form->set_populator($pop);

    $db = get_db();
    $db->query("select sum(PRICE * (select sum(GROUP_SIZE) from PARKING.GR_GUEST where RESERVATION_ID_FK = RESERVATION_ID)) AMT from PARKING.GR_RESERVATION where RESERVATION_ID in ($id_list) and ACTIVE = 1");
    $amt = $db->get_from_top('AMT');
    $pop->set_insert_extra('PAYMENT_AMOUNT', $amt);
    $amount = new data('Amount Paid', $amt);
    $amount->set_formatter(new money_formatter());
    $amount->set_renderer(new form_display_renderer());
    $form->add($amount);

    $type = database_populated_menu::get_menu('Type', 'PAY_TYPE_ID', 'PAYMENT_TYPE_FK', 'PAY_TYPE_NAME', 'GR_PAYMENT_TYPE', $pop);
    $form->add($type);

    $num = new database_data('IBF or Check Number', 'PAYMENT_NUMBER', 'GR_PAYMENT');
    $num->set_validator(new data_validator('/^[0-9]+$/', 0, true));
    $num->set_renderer(new field_renderer());
    $form->add($num);

    $sub = new data('Submit');
    $sub->set_renderer(new button_renderer());
    $form->add($sub);

    return $form;
}

function submit_payment_4($steps) {
    $pop = $steps->get_form_cache(4)->get_populator();
    $pay_id = $pop->insert(true);
    $id_list = implode(',', $steps->get_form_cache(3)->get_checks());
    $db = get_db();
    $db->execute("update PARKING.GR_RESERVATION set PAYMENT_ID_FK = $pay_id where RESERVATION_ID in ($id_list)");
}

function get_payment_5() {
    $form = new form('Payment Complete');
    $form->add(field_factory::get_note('<p>The payment has been recorded.</p>'));
    $button = new data('Done');
    $button->set_renderer(new button_renderer());
    $form->add($button);
    return $form;
}

class steps_payment extends form_steps {
    protected $form_name = 'payment';
    protected $steps = array
    (
     1 => 'Dates',
     2 => 'Department',
     3 => 'Reservation',
     4 => 'Payment',
     5 => 'Done'
    );
    protected $descriptions = array
    (
     1 => 'Select a date range. Use zero to display all outstanding balances.',
     2 => 'Select a department to make a payment.',
     3 => 'Select the reservations to be paid.',
     4 => 'Enter the payment information.',
     5 => 'The reservation has been paid.'
    );
}
?>