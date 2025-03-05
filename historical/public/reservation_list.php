<?php
function get_list($admin = null) {
    if($admin) {
        $customer_id = $admin;
        $db = get_db();
        $db->query("select DEPT_NAME from PARKING.GR_DEPARTMENT where DEPT_NO = '$customer_id'");
        $customer = $db->get_from_top('DEPT_NAME');
        $cond = "and PAYMENT_ID_FK is null and PRICE > 0";
        $link = "pay.php";
    }
    else {
        global $auth;
		  if (!is_object($auth)) $auth = new authorization_garage_reservation(); //jody
        $customer_id = $auth->get_customer_id();
        $customer = "Your Department";
        $cond = "and PAYMENT_ID_FK is null";
        $link = "show_reservation.php";
    }

    $form = new form("Reservations for $customer");

    // By request: button at top
    if(!$admin) {
        $addlink = new data('Request a Reservation');
        $addlink->set_renderer(new button_renderer(true, 'reservation.php'));
        $form->add($addlink);
    }

    // Special customer id fixer
    if(substr($customer_id,0,1) != "'") $customer_id = "'$customer_id'";

    // By request: descending order
    $res = new collection();
    $res_data = new recordset_populator
    (
     $res,
     array('GR_RESERVATION', 'GR_GARAGE', 'GR_USER'),
     array('RESERVATION_ID', 'RESERVATION_ID', 'RES_DATE', 'ENTER_TIME', 'EXIT_TIME', 'GARAGE_NAME', 'GROUP_SIZE', 'PRICE', 'USER_NAME', 'FRS_FK', 'KFS_SUB_ACCOUNT_FK', 'KFS_SUB_OBJECT_CODE_FK'),
     "DEPT_NO_FK in ($customer_id) and GARAGE_ID_FK = GARAGE_ID and USER_ID_FK = USER_ID and ACTIVE = 1 $cond",
     "to_date(RES_DATE, 'Mon DD, YYYY') desc, to_date(ENTER_TIME, 'HH:MI AM') desc"
     );
    $res_data->set_select_expression('RES_DATE', "to_char(RES_DATE, 'Mon DD, YYYY')");
    $res_data->set_select_expression('ENTER_TIME', "to_char(ENTER_TIME, 'HH:MI AM')");
    $res_data->set_select_expression('EXIT_TIME', "to_char(EXIT_TIME, 'HH:MI AM')");
    $res_data->set_select_expression('PRICE', "(select to_char(PRICE * sum(GROUP_SIZE), '$9,999.99') from parking.gr_guest where reservation_id_fk = reservation_id)");
    $res_data->set_select_expression('GROUP_SIZE', '(select sum(GROUP_SIZE) from PARKING.GR_GUEST where RESERVATION_ID_FK = RESERVATION_ID)');
    $res_data->set_headings('id', 'Number', 'Date', 'Enter', 'Exit', 'Garage', 'Spaces', 'Price', 'Reserved By', 'Account', 'Sub Acct.', 'Sub Obj. Code');
    $res_data->set_components('id', 'num', 'date', 'enter', 'exit', 'garage', 'spaces', 'price', 'user', 'frs', 'KFS_SUB_ACCOUNT_FK', 'KFS_SUB_OBJECT_CODE_FK');
    if(!$admin) $res_data->set_id_link('num', $link);
    //$res_data->set_heading_links();

    if($admin) $res_data->set_selector('checkbox');

    if($res_data->populate()) {
        if(!$admin) $form->add(field_factory::get_note('<div style="font-weight: bold; color: #944;">Click a reservation number below to view reservation details or make a cancellation.</div>'));

        $list_data = new data('Reservations', $res);
        $list_data->set_renderer(new list_renderer('grid'));
        $form->add($list_data);
    }
    else $form->add(field_factory::get_note('<i>No reservations.</i>'));

    if($admin) {
        $addlink = new data('Continue');
        $addlink->set_renderer(new button_renderer());
        $form->add($addlink);
    }
    else {
    }


    return $form;
}
?>
