<h1>Garage Detail</h1>
<?php

//<authorization type="garage_reservation" level="4"/>
$protectPass = $dbConn->protectCheck(4);
if (!$protectPass)
	exitWithBottom('You are not authorized.');
?>

<dynamic content="gar"/>

<?php
function get_gar() {
    if(!isset($_POST['submit_button'])) url::redirect('garages.php');

    $id = isset($_POST['Garage']) ? $_POST['Garage'] : 0;
    if($_POST['submit_button'] == 'New Garage') $id = 0;

    $form_name = $id ? 'Edit Garage' : 'Insert Garage';
    $form = new form($form_name);
    $pop = new record_populator($form, 'GR_GARAGE', 'GARAGE_ID', 'GR_GARAGE_ID');

    $field = new data('Name');
    $field->set_renderer(new field_renderer());
    $field->set_validator(new data_validator('', 0, true));
    $gar = new database_data($field, 'GARAGE_NAME', 'GR_GARAGE');
    $form->add($gar);

    $max_field = new data('Maximum Visitors');
    $max_field->set_renderer(new field_renderer());
    $max_field->set_validator(new data_validator('/^[0-9]+$/', 3, true));
    $max = new database_data($max_field, 'VISITOR_MAX', 'GR_GARAGE');
    $form->add($max);

    $sub = field_factory::get_button('Submit', false);
    $cancel = field_factory::get_button('Cancel', false, 'garages.php');
    $form->add(field_factory::get_item_row(new collection($sub, $cancel)));

    if($id) {
        $hide = new data('xml', "<input type=\"hidden\" name=\"Garage\" value=\"$id\"/>");
        $form->add($hide);

        $pop->add_condition("GARAGE_ID = $id");
        $pop->populate();
    }

    $form->set_populator($pop);
    return $form;
}

function submit_gar($form) {
    $pop = $form->get_populator();
    $id = isset($_POST['Garage']) ? $_POST['Garage'] : 0;
    if($id) {
        $pop->update($id);
        return 'Garage information updated.';
    }
    else {
        $pop->insert();
        return 'Garage inserted.';
    }
}

function get_action() {
    return 'garages.php';
}
?>
