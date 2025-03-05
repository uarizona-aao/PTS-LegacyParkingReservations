<h1>Add a Department</h1>
<?php

//<authorization type="garage_reservation" level="4"/>
$protectPass = $dbConn->protectCheck(4);
if (!$protectPass)
	exitWithBottom('You are not authorized.');
?>

<dynamic content="new_customer"/>

<?php
function get_new_customer() {
$form = new form('New Department Information');
$pop = new record_populator($form, 'GR_FRS', 'FRS');
$form->set_populator($pop);

// Set last parameter to "true" to allow A-Z in dept. number input
$id = new database_data(field_factory::get_dept_field(true, 'Department Number', true), 'DEPT_NO_FK', 'GR_FRS');
$form->add($id);

$name = new data('Department Name');
$name->set_renderer(new field_renderer());
$name->set_validator(new data_validator('', null, true));
$form->add($name);

$po = new data('Campus PO Box');
$po->set_validator(new data_validator('', 6, true));
$po->set_renderer(new field_renderer(6));
$form->add($po);

// Set to "true" to allow A-Z in FRS input
$acct = new database_data(field_factory::get_frs_field(true), 'FRS', 'GR_FRS');
$form->add($acct);

$desc = new database_data('Account Description', 'DESCRIPTION', 'GR_FRS');
$desc->set_validator(new data_validator('', 20, true));
$desc->set_renderer(new field_renderer(20));
$form->add($desc);

$pop->set_insert_extra('ACTIVE', 1);

$sub = field_factory::get_button('Create Department', false);
$cancel = field_factory::get_button('Cancel', false, 'customers.php');
$form->add(field_factory::get_item_row(new collection($sub, $cancel)));

return $form;
}

function submit_new_customer($form) {
$deptid = $form->get_by_name('Department Number')->get_database_value();
$deptname = $form->get_by_name('Department Name')->get_database_value();
$pobox = $form->get_by_name('Campus PO Box')->get_database_value();

try {
	 $db = get_db();
	 $db->execute("insert into PARKING.GR_DEPARTMENT values ($deptid, $deptname, 1, $pobox)");
	 $form->get_populator()->insert();
	 return 'Department Inserted.';
} catch (Exception $e) {
	 $db->execute("delete from PARKING.GR_DEPARTMENT where DEPT_NO = $deptid");
	 return 'Could not insert the new department.<br/>The department number or FRS account number may already exist in the system.';
}
}

function get_action() {
return 'customers.php';
}
?>