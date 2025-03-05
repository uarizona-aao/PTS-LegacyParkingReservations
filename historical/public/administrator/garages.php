<h1>Edit Garages</h1>
<?php

//<authorization type="garage_reservation" level="4"/>
$protectPass = $dbConn->protectCheck(4);
if (!$protectPass)
	exitWithBottom('You are not authorized.');
?>

<dynamic content="garage"/>

<?php
function get_garage() {
    $form = new form('Select a Garage');
    $form->set_action('garage_detail.php');

    $gar_list = new single_select_group();
    $pop = new recordset_populator($gar_list, 'GR_GARAGE', array('GARAGE_NAME', 'GARAGE_ID'), null, 'GARAGE_NAME');
    $pop->set_components('name', 'id');
    $pop->set_pairs();
    $pop->populate();

    $view = new data('Garage', $gar_list);
    $view->set_renderer(new list_renderer('select_box'));
    $form->add($view);

    $edit = new data('Edit');
    $edit->set_renderer(new button_renderer(false));

    $ins = new data('New Garage');
    // 20140805 - jsc - removed "New Garage" button
    // new garages must have a KUALI account
    // add directly into database
    //$ins->set_renderer(new button_renderer(false));

    $back = new data('Cancel');
    $back->set_renderer(new button_renderer(false, 'index.php'));

    $form->add(field_factory::get_item_row(new collection($edit, $ins, $back)));

    return $form;
}


function submit_garage($form) {
    $pop = $form->get_populator();
    $id = $_POST['Garage'];
    $submit = $_POST['submit_button'];
}
?>
