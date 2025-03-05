<?php
function get_reservation_list($made_on = null) {
	if($made_on) $day = $made_on;
	else $day = isset($_GET['date']) ? $_GET['date'] : date('d-M-Y');
	$stamp = strtotime($day);
	$month = date('m', $stamp);
	$year = date('Y', $stamp);
	$display = date('F j, Y', $stamp);

	$cond = $made_on ? "trunc(GR_RESERVATION.CREATION_DATE) = '$day'" : "RES_DATE = '$day'";
	$titlewords = $made_on ? 'placed on' : 'for';
	$items = new form("Reservations $titlewords $display");

	$res = new collection();

	$res_data = new recordset_populator
	(
			$res,
			array('GR_RESERVATION', 'GR_GARAGE', 'GR_DEPARTMENT', 'GR_USER'),
			array('RESERVATION_ID', 'RESERVATION_ID', 'ENTER_TIME', 'EXIT_TIME', 'DEPT_NAME', 'GARAGE_NAME', 'GROUP_SIZE', 'PRICE', "ACTIVE", "CREATION_DATE"),
			"$cond and GARAGE_ID_FK = GARAGE_ID and USER_ID_FK = USER_ID and DEPT_NO_FK = DEPT_NO",
			"GARAGE_NAME, to_date(ENTER_TIME, 'HH:MI AM') asc"
	);

	$res_data->set_select_expression('ENTER_TIME', "to_char(ENTER_TIME, 'HH:MI AM')");
	$res_data->set_select_expression('EXIT_TIME', "to_char(EXIT_TIME, 'HH:MI AM')");
	$res_data->set_select_expression('PRICE', "to_char(PRICE, '$9,999.99')");
	$res_data->set_select_expression('GROUP_SIZE', '(select sum(GROUP_SIZE) from PARKING.GR_GUEST where RESERVATION_ID_FK = RESERVATION_ID)');
	$res_data->set_select_expression('CREATION_DATE',	"to_char(GR_RESERVATION.CREATION_DATE, 'MM/DD/YY HH:MI AM')");
	$res_data->set_headings('id', 'Number', 'Enter', 'Exit', 'Department', 'Garage', 'Spaces', 'Price Each', "1=Active", "Created");
	$res_data->set_components('id', 'num', 'enter', 'exit', 'dept', 'garage', 'spaces', 'price', "active", "created");
	$link = $made_on ? "reservation.php" : "reservation.php?date=$day";
	$res_data->set_id_link('num', $link);

	if($res_data->populate()) {
		$list_data = new data('Reservations', $res);
		$list_data->set_renderer(new list_renderer('grid'));
		$items->add($list_data);
	}
	else
	{
		$items->add(field_factory::get_note('<i>No reservations for today.</i>'));
	}

    $btn_name = $made_on ? 'Go Back' : 'Back to Calendar';
    $button = new data($btn_name);
    $backlink = isset($GLOBALS['backlink']) ? $GLOBALS['backlink'] : 'reservation_calendar.php';
    $button->set_renderer(new button_renderer(true, "$backlink?month=$month&amp;year=$year"));
    $items->add($button);

    return $items;
}

function backlink($file) {
	$GLOBALS['backlink'] = $file;
}
?>
