<h1>Exceptions</h1>

<dynamic content="exception"/>
<?php 
if (isset($_POST['Notes']))
	unmake_htmlentities($_POST['Notes']);
?>

<?php
function get_exception() {
    $garage = isset($_GET['id']) ? $_GET['id'] : 0;

    $form = new form('Exception Report');
    $pop = new record_populator($form, 'GR_GUEST_EXCEPTION', 'EX_ID', 'GR_EXCEPTION_ID');
    $pop->set_insert_extra('EX_GARAGE_FK', $garage);
    $pop->set_insert_extra('EX_TIME', 'sysdate');
    $form->set_populator($pop);

    $name = new database_data('Guest Name', 'EX_NAME', 'GR_GUEST_EXCEPTION');
    $name->set_renderer(new field_renderer());
    $name->set_validator(new data_validator('/^[a-zA-Z\- ]+$/', 0, true));
    $form->add($name);

    $contact = new database_data('Contact or Department', 'EX_CONTACT', 'GR_GUEST_EXCEPTION');
    $contact->set_renderer(new field_renderer());
    $contact->set_validator(new data_validator('', 0, true));
    $form->add($contact);

    $phone = new database_data(field_factory::get_phone_field('Contact Phone', true), 'EX_PHONE', 'GR_GUEST_EXCEPTION');
    $form->add($phone);

    $form->add(field_factory::get_note('<p>Please list any other information available,<br/>including event, group, or account number:</p>'));

    $notes = new database_data('Notes', 'EX_NOTES', 'GR_GUEST_EXCEPTION');
    $notes->set_renderer(new textarea_renderer()); // $_POST['Notes']
    $form->add($notes);

    $initials = new database_data('Your Initials', 'EX_INITIALS', 'GR_GUEST_EXCEPTION');
    $initials->set_renderer(new field_renderer(2));
    $initials->set_validator(new data_validator('/[A-Za-z]{2}/', 2, true));
    $form->add($initials);

    $sub = field_factory::get_button('Submit Form', false);
    $cancel = field_factory::get_button('Cancel', false, "daily_listing.php?id=$garage");
    $form->add(field_factory::get_item_row(new collection($sub, $cancel)));

    return $form;
}

function submit_exception($form) {
    $form->get_populator()->insert();
    return 'This incident report has been submitted.';
}

function get_action() {
    return "daily_listing.php?id=".$_GET['id'];
}
?>
