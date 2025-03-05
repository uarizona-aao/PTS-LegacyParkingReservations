<?php
/*
 * Code to view, insert, or update a reservation
 */
//error_reporting (E_ALL);
//ini_set ('display_errors', 1);

stripBadChars();

require_once '/var/www2/include/gr/garage_reservation.php'; // in folder include/

$pdfMsg = $email_addr = '';

function stripBadChars() {
	if ($_POST['Notes'])
		$_POST['Notes'] = preg_replace('/[^ \d\w\.\,\-]/i', ' ', $_POST['Notes']);
	if ($_POST['Guests'])
		$_POST['Guests'] = preg_replace('/[^ \d\w]/i', '', $_POST['Guests']);
	if ($_POST['Group Name'])
		$_POST['Group Name'] = preg_replace('/[^ \d\w]/i', '', $_POST['Group Name']);

}


function get_reservation($allow_fake_frs = false)
{
	global $auth;
	$db = get_db();
	if (!is_object($auth)) $auth = new authorization_garage_reservation(); //jody

	$user_id = isset($auth) ? $auth->get_user_id() : null;

	$adminAuth = ($auth->get_authorization() >= 3);

	$id = isset($_GET['id']) ? $_GET['id'] : null;
	$edit = (isset($GLOBALS['updateres']));
	$del = (isset($GLOBALS['deleteres']));

	if($del and !$id) $del = false;
	if($edit and !$id) url::redirect('index.php');

	if(isset($GLOBALS['canceledres']))
	  $title = 'View Cancelled Reservation';
	else
	  $title = $id ? "View Reservation" : "Place a Reservation Request";

	$form = new form($title);

	$pop = new record_populator($form, 'GR_RESERVATION', 'RESERVATION_ID', 'GR_RESERVATION_ID');
	//$pop->add_condition('VISITOR_MAX>0');

	$resid = new database_data('Confirmation No.', 'RESERVATION_ID', 'GR_RESERVATION');
	$resid->set_renderer(new form_display_renderer());
	$form->add($resid);

	$frs = new database_data(field_factory::get_frs_field($allow_fake_frs), 'FRS_FK', 'GR_RESERVATION');
	$form->add($frs);

	$KFS_SUB_ACCOUNT_FK = new database_data(field_factory::get_sub_act_field(), 'KFS_SUB_ACCOUNT_FK', 'GR_RESERVATION');
	$form->add($KFS_SUB_ACCOUNT_FK);

	$KFS_SUB_OBJECT_CODE_FK = new database_data(field_factory::get_sub_obj_field(), 'KFS_SUB_OBJECT_CODE_FK', 'GR_RESERVATION');
	$form->add($KFS_SUB_OBJECT_CODE_FK);

	$gar = database_populated_menu::get_menu('Garage', 'GARAGE_ID', 'GARAGE_ID_FK', 'GARAGE_NAME', 'GR_GARAGE', $pop, false, "Select a Garage");
	$form->add($gar);

	$form->add(get_resdate_field());
	$form->add(get_enter_field());
	$form->add(get_exit_field());
	if($id or isset($GLOBALS['updprice'])) {
	  $price = new database_data(field_factory::get_money_field('Price Each', true), 'PRICE', 'GR_RESERVATION');
	  $form->add($price);
	}
	else if(!$id) {
	  $opts = new single_select_group(new data_item('Guest List'), new data_item('Group'));
	  $groups = new data('Guests', $opts);
	  $groups->set_renderer(new list_renderer('radio_group'));
	  $form->add($groups);
	}

	// Display supplementary guest info
	if($id)
	{
		$db->query("select * from PARKING.GR_GUEST where RESERVATION_ID_FK = $id order by upper(SORT_NAME)");
		$size = $db->get_from_top('GROUP_SIZE');
		$is_group = ($db->num_rows() == 1 and $size > 1);

		// Show guest options
		if(!$is_group && $auth->get_authorization()==4) {
			 $comego = new database_data(new data_item('Guests May Come and Go'), 'COME_AND_GO', 'GR_RESERVATION');
			 $comego->set_renderer(new checkbox_renderer());
			 $form->add($comego);
		}

		 $extra = new database_data(new data_item('Allow Extra Guests'), 'ALLOW_EXTRA', 'GR_RESERVATION');
		 $extra->set_renderer(new checkbox_renderer());
		 $form->add($extra);

		// Display estimated attendance figures
		if($edit) {
			 $offc = new database_data('Additional Guests', 'GUESTS_OFFCAMPUS', 'GR_RESERVATION');
			 $offc->set_renderer(new form_display_renderer());
			 $form->add($offc);
		}

		// Show guest list
		if($is_group) {
			 $name = new database_data(new data('Group Name'), 'GUEST_NAME', 'GR_GUEST');
			 $name->set_renderer(new form_display_renderer());
			 $form->add($name);

			 $spaces = new database_data(new data('Spaces'), 'GROUP_SIZE', 'GR_GUEST');
			 $spaces->set_renderer(new form_display_renderer());
			 $form->add($spaces);
			 $pop->add_condition('RESERVATION_ID = RESERVATION_ID_FK');
		}
		else {
			 $guests = '';
			 foreach($db->get_results() as $result) {
				  if($guests != '') $guests .= "\n";
				  $guests .= $result['GUEST_NAME'];
			 }
			 $guests = new data('Guests', $guests);
			 $guests->set_renderer(new form_display_renderer());
			 $form->add($guests);
		}

		// Add link to edit guest list
		if($edit) {
			 $date = isset($_GET['date']) ? $_GET['date'] : '';
			 $type = $is_group ? 0 : 1;
			 $link = field_factory::get_button('Edit Guests', true, "guest_editor.php?date=$date&amp;id=$id&amp;list=$type");
					//$link = field_factory::get_button('Edit Guests', true, "guest_editor.php?date=$date&id=$id&list=$type");
			 $form->add($link);
		}

		$contact = new database_data('Request By', 'USER_NAME', 'GR_USER');
		$contact->set_query_field("USER_NAME||' ('||PHONE||')'");
		$contact->set_renderer(new form_display_renderer());
		$form->add($contact);
		$pop->add_condition('USER_ID_FK = USER_ID');

		// Add payment note if any exists
		if($db->query("select * from PARKING.GR_RESERVATION, PARKING.GR_PAYMENT, PARKING.GR_PAYMENT_TYPE, PARKING.GR_USER
					where RESERVATION_ID = $id and PAYMENT_ID_FK = PAYMENT_ID and PAYMENT_TYPE_FK = PAY_TYPE_ID and ACCOUNTING_USER = USER_ID"))
		{
	      $paydate = $db->get_from_top('PAYMENT_DATE');
	      $paytype = $db->get_from_top('PAY_TYPE_NAME');
	      $paynum = $db->get_from_top('PAYMENT_NUMBER');
	      $payuser = $db->get_from_top('USER_NAME');
	      $payamt = $db->get_from_top('PAYMENT_AMOUNT');

	      // Full data for administrators
	      if($auth->get_authorization() >= 3) $paytext = "Paid on $paydate<br/>$paytype number $paynum for \$$payamt<br/>Recorded by $payuser";

	      // Quick description for customers
	      else $paytext = "Paid on $paydate";

	      $form->add(field_factory::get_note($paytext));
		}

		// Add administrative history tools
		// 20151007  if($edit || $user_id==56) {
		if($edit || $adminAuth)
		{
			if($edit)
			{
				$notes = new data('Notes');
				$notes->set_renderer(new textarea_renderer());
				$form->add($notes);
				$cashnote = new data_item('Show Notes to Cashier');
				$cashnote->set_renderer(new checkbox_renderer());
				$form->add($cashnote);
			}
			// List prior modification records, if any
			$mod_data = new collection();
			$mod_pop = new recordset_populator($mod_data, array('GR_RESERVATION_NOTE', 'GR_USER'), array('DATE_RECORDED', 'USER_NAME', 'NOTE'), "USER_ID_FK = USER_ID and RESERVATION_ID_FK = $id", 'DATE_RECORDED desc');
			$mod_pop->set_components('date', 'name', 'note');
			$mod_pop->set_headings('Date', 'User', 'Note');
			//$mod_pop->set_select_expression('DATE_RECORDED', "to_char(DATE_RECORDED, 'mm/dd/yy hh:miam')");
			$mod_pop->populate();
			$mod_display = new data('notes', $mod_data);
			$mod_display->set_renderer(new list_renderer('grid'));
			$form->add($mod_display);
			//	if ($GLOBALS['jody']) {
			//		echo '<pre>';
			//		var_dump($mod_data);
			//		echo '<hr>';
			//		var_dump($mod_pop);
			//		echo '<hr>';
			//		var_dump($mod_display);
			//		echo '</pre>';
			//	}
		}
	}
	else if(isset($GLOBALS['setuserid']) and $GLOBALS['setuserid'])
	{
	  $user_id = $GLOBALS['setuserid'];

	  // Hack to add supplementary info
	  $dept_no = $GLOBALS['setcustid'];
	  $db = get_db();
	  $db->query("select USER_NAME||' ('||PHONE||')' USER_NAME, DEPT_NAME from PARKING.GR_USER, PARKING.GR_DEPARTMENT where DEPT_NO = '$dept_no' and USER_ID = $user_id");
	  $results = $db->get_results();

	  $contact = new data('Request By', $results[0]['USER_NAME']);
	  $contact->set_renderer(new form_display_renderer());
	  $form->add($contact);

	  $cust = new data('Department', $results[0]['DEPT_NAME']);
	  $cust->set_renderer(new form_display_renderer());
	  $form->add($cust);

	  $notes = new data('Notes');
	  $notes->set_renderer(new textarea_renderer());
	  $form->add($notes);

	  $cashnote = new data_item('Show Notes to Cashier');
	  $cashnote->set_renderer(new checkbox_renderer());
	  $form->add($cashnote);
	}

	$buttons = new collection();

	if(!$id) {
	  $submit = new data('Continue');
	  $buttons->add(field_factory::get_button('Continue', false));
	}
	if($edit) $buttons->add(field_factory::get_button('Update', false));
	if($del) {
	  // Don't allow deletion of reservations that have already been paid or are in the past
	  // Also, don't allow anybody but the maker of the reservation to cancel
	  // Admin users are exempt from this rule.
	  if($auth->get_authorization() < 4) {
	      $db = get_db();
	      $db->query("select RESERVATION_ID from PARKING.GR_RESERVATION where
	              RESERVATION_ID = $id and PAYMENT_ID_FK is null and trunc(RES_DATE) > trunc(sysdate) and USER_ID_FK = ".$auth->get_user_id());
	      $make_button = $db->num_rows();
	  }
	  else $make_button = true;


	//******************************************************************************************************************//
	//// Sal working
	$day = get_resdate_field();
	//$buttons->add(field_factory::get_button("$day", false));

	if($make_button) $buttons->add(field_factory::get_button('Cancel Reservation', false));
	  //if($make_button && ($day > date("m/d/Y",strtotime("now")))) $buttons->add(field_factory::get_button('Cancel Reservation', false));
	////if($make_button && (date("/m/d/y",strtotime($day) > date("m/d/y",strtotime("now"))))) $buttons->add(field_factory::get_button('Cancel Reservation', false));
	}

	//******************************************************************************************************************//


	if($id) {
	  $link = isset($GLOBALS['goback']) ? $GLOBALS['goback'] : 'index.php';
	  $buttons->add(field_factory::get_button('Go Back', false, $link));
	}

	$form->add(field_factory::get_item_row($buttons));

	// Associate the specified customer with the request
	if(isset($GLOBALS['setuserid']))
	  $pop->set_insert_extra('USER_ID_FK', $user_id);

	$form->set_populator($pop);

	// Make form read-only when viewing a specific reservation ID
	if($id or $edit or $del) {
	  $pop->add_condition("RESERVATION_ID = $id");
	  if(!$edit) $pop->set_readonly();
	  if(!$pop->populate()) throw new error('Not Found', 'No reservation was found.');
	}

	return $form;
}

function get_resdate_field() {
	// Date should never be in the past
	global $auth;
	if (!is_object($auth)) $auth = new authorization_garage_reservation(); // jody
	$never_before = ($auth->get_authorization() > 3) ? 'last year' : 'yesterday';
	$datefield = field_factory::get_short_date_field('Date', $never_before,'');
	$day = new database_data($datefield, 'RES_DATE', 'GR_RESERVATION');
	$day->set_query_field("to_char(RES_DATE, 'MM/DD/YY')");
	return $day;
}

function get_enter_field() {
	$enter_time = new database_data(field_factory::get_time_field('Enter Time'), 'ENTER_TIME', 'GR_RESERVATION');
	$enter_time->set_query_field("to_char(ENTER_TIME, 'HH:MI AM')");
	return $enter_time;
}

function get_exit_field() {
	$exit_time = new database_data(field_factory::get_time_field('Exit Time'), 'EXIT_TIME', 'GR_RESERVATION');
	$exit_time->set_query_field("to_char(EXIT_TIME, 'HH:MI AM')");
	return $exit_time;
}

// Checks the KFS number, and stores the associated department number for later reference
function validate_frs($uid, $frs, $steps) {
	$db = get_db();
	$query = "select ACTIVE, PARKING.GR_FRS.DEPT_NO_FK DEPT_NO_FK from PARKING.GR_FRS, PARKING.GR_USER, PARKING.GR_USER_DEPARTMENT where FRS = '$frs' and USER_ID = $uid and PARKING.GR_FRS.DEPT_NO_FK = PARKING.GR_USER_DEPARTMENT.DEPT_NO_FK";
	$db->query($query);
	if($db->num_rows()) {
	  if($db->get_from_top('ACTIVE') == 0)
	      throw new step_exception("The KFS number is no longer active.");
	}
	else throw new step_exception("The KFS number is not valid.");

	$steps->store('dept_no', $db->get_from_top('DEPT_NO_FK'));
}

function get_guestlist($is_list, $guests, $admin = false, $editor = false) {
	global $auth;

	if (!is_object($auth)) $auth = new authorization_garage_reservation(); // jody

	//$is_list = ($steps->get_previous_form()->get_by_name('Guests')->get_value()->get_selected_item()->get_name() == 'Guest List');
	if($is_list) {
	  $form = new form('Add Guests');

	  $fname = new data('First Name');
	  $fname->set_renderer(new field_renderer(15));
	  $fname->get_renderer()->set_nodefault();
	  $fname->set_validator(new data_validator('/^[a-zA-Z\-]+$/', 15));
	  $form->add($fname);

	  $lname = new data('Last Name');
	  $lname->set_renderer(new field_renderer(15));
	  $lname->get_renderer()->set_nodefault();
	  $lname->set_validator(new data_validator('/^[a-zA-Z\-]+$/', 15));
	  $form->add($lname);

	  $more = field_factory::get_button('Add Guest', false);
	  $remove = field_factory::get_button('Remove Selected', false);
	  $form->add(field_factory::get_item_row(new collection($more, $remove)));

	  // Get the last object before it's destroyed
	  //$guests = $steps->get_current_form_cache();

	  if($guests) $guests = $guests->get_by_name('Guests');
	  if($guests) $guests = $guests->get_value();
	  else $guests = new single_select_group();
	  $guest_data = new data('Guests', $guests);
	  $guestbox = new list_renderer('select_box');
	  $guestbox->set_empty_note('Please Add a Guest');
	  $guest_data->set_renderer($guestbox);
	  $form->add($guest_data);

	  if(!$admin) {
	      if ($auth->get_authorization()==4) {
			$comego = new database_data(new data_item('Guests May Come and Go'), 'COME_AND_GO', 'GR_RESERVATION');
			$comego->set_renderer(new checkbox_renderer(true, true, ' ($2 Service Charge Per Guest)'));
			$form->add($comego);
		}

		$extra = new database_data(new data_item('Allow Extra Guests'), 'ALLOW_EXTRA', 'GR_RESERVATION');
		//$extra->set_renderer(new checkbox_renderer());
	   $extra->set_value(0);
		$extra->set_renderer(new hidden_renderer());
		$form->add($extra);
	  }
	}
	else{
	  $form = new form('Group Info');

	  $name = new database_data(new data('Group Name'), 'GUEST_NAME', 'GR_GUEST');
	  $name->set_renderer(new field_renderer());
	  $name->set_validator(new data_validator('', 0, true));
	  $form->add($name);

	  $spaces = new database_data(new data('Spaces'), 'GROUP_SIZE', 'GR_GUEST');
	  $spaces->set_renderer(new field_renderer(3));
	  $spaces->set_validator(new data_validator('/([1-9]{1})|([1-9]{1}[0-9]{1,2})/', 3, true, 'Group must be 2 or more people.'));
	  $form->add($spaces);

	  $comego = new database_data(new data_item('Guests May Come and Go'), 'COME_AND_GO', 'GR_RESERVATION');
	  $comego->set_value(0);
	  $comego->set_renderer(new hidden_renderer());
	  $form->add($comego);

	  if(!$admin) {
		   $extra = new database_data(new data_item('Allow Extra Guests'), 'ALLOW_EXTRA', 'GR_RESERVATION');
		   //$extra->set_renderer(new checkbox_renderer());
		   $extra->set_value(0);
		   $extra->set_renderer(new hidden_renderer());
		   $form->add($extra);
	  }
	}

	if(!$editor) {
	  // Add special "event size" box per management request
	  //$form->add(field_factory::get_note('<div style="color: #449;">How many guests not listed here<br/>do you estimate will attend this event?</div>'));

	  $offc = new database_data('Additional Guests', 'GUESTS_OFFCAMPUS', 'GR_RESERVATION');
	  //$offc->set_renderer(new field_renderer());
	  $offc->set_value(0);
	  $offc->set_renderer(new hidden_renderer());
	  $offc->set_validator(new number_validator(4));
	  $form->add($offc);
	}

	$buttons = new collection();

	// Add "back" button for guest list editor
	if($admin) $buttons->add(field_factory::get_button('Cancel', false, "reservation.php?date=".$_GET['date']."&amp;id=".$_GET['id']));
	$name = $admin ? 'Update Guestlist' : 'Continue';

	$buttons->add(field_factory::get_button($name, false));

	$form->add(field_factory::get_item_row($buttons));

	return $form;
}

function verify_guestlist($steps, $admin = false) {
	global $auth;
	if (!is_object($auth)) $auth = new authorization_garage_reservation(); // jody

	// Get current garage-customer-day reservation count
	$used = $steps->retrieve('used');
	if(!$used) {
	  $form = $steps->get_form_cache(1);
	  $date = date('d-M-y',strtotime($form->get_by_name('Date')->get_value()));
	  $garage_id = $form->get_by_name('Garage')->get_value();
	  $customer_id = $steps->retrieve('dept_no');

	  $db = get_db();
	  $db->query("select sum(GROUP_SIZE) USED from PARKING.GR_GUEST, PARKING.GR_DEPARTMENT, PARKING.GR_RESERVATION where RESERVATION_ID_FK = RESERVATION_ID and GARAGE_ID_FK = $garage_id and DEPT_NO_FK = DEPT_NO and DEPT_NO = '$customer_id' and RES_DATE = '$date'");
	  $used = $db->get_from_top('USED');
	  $steps->store('used', $used);
	}

	$form = $steps->get_form_cache(2);
	if($form->get_name() == 'Add Guests') {
	  $guests = $form->get_by_name('Guests')->get_value();
	  if($_POST['submit_button'] == 'Remove Selected') {
	      if(!isset($_POST['Guests'])) throw new step_exception('Please select a name to remove from the list.');
	      $name = $_POST['Guests'];
	      $guests->remove_name($name);
	      throw new step_exception(null);
	  }
	  else if($_POST['submit_button'] == 'Add Guest') {
	      // colin added second condition on 10-20-05 so admin could add more than 25
		if ($guests->size() == 25 && $auth->get_authorization()!=4)
	          throw new step_exception('Cannot add more than 25 guests.');

	      $fname = trim($form->get_by_name('First Name')->get_value());
	      $lname = trim($form->get_by_name('Last Name')->get_value());
	      $new_item = "$fname $lname";

	      if($fname and $lname) {
	          if($guests->get_by_name($new_item))
	              throw new step_exception('That name already appears on the guest list.');
	          $guests->add(new data_item($new_item));
	      }
	      else throw new step_exception("Please enter the guest's first and last name.");
	      throw new step_exception(null);
	  }
	  else {
	      if($guests->size() == 0)
	          throw new step_exception('Please add some guests to the guest list.');

	      // Check "Event Size" box
	      else if(!is_numeric($form->get_by_name('Additional Guests')->get_value()) )
	          throw new step_exception('Please enter the expected number of visitors, or a zero if there are none.');

	      else return;
	  }
	}

	// Check "Event Size" box (again for groups)
	else if(!is_numeric($form->get_by_name('Additional Guests')->get_value()))
	  throw new step_exception('Please enter the expected number of visitors, or a zero if there are none.');

	// Check group size
	/*
	else {
	  $size = $form->get_by_name('Size');
	  $used = $steps->retrieve('used');
	  if($form->get_by_name('Allow Extra Guests')->is_selected() and $size >= (25 - $used))
	      throw new step_exception('You may not allow extra guests when reserving at maximum capacity.');
	}
	*/
}

function get_recurring($res_form, $date_form) {
	$form = new form('Recurring Dates (Optional)');

	if(!$date_form or !$date_form->get_by_name('dates')->get_value()->size())
	  $form->add(field_factory::get_note('<div style="font-weight: bold; color: #944;">If this reservation is for only one date,<br/>click the &quot;Continue&quot; button.</div>'));

	$date = get_resdate_field();
	$date->get_validator()->set_required(false);
	$date->set_value($res_form->get_by_name('Date')->get_formatted_value());
	$form->add($date);

	$entertime = get_enter_field();
	$entertime->get_validator()->set_required(false);
	$entertime->set_value($res_form->get_by_name('Enter Time')->get_formatted_value());
	$form->add($entertime);

	$exittime = get_exit_field();
	$exittime->get_validator()->set_required(false);
	$exittime->set_value($res_form->get_by_name('Exit Time')->get_formatted_value());
	$form->add($exittime);

	if(!$date_form) {
	  $grid = new data('dates', new collection());
	  $grid->set_renderer(new list_renderer('grid'));
	}
	else $grid = $date_form->get_by_name('dates');
	$form->add($grid);

	$btns = new collection(field_factory::get_button('Add Date', false), field_factory::get_button('Continue', false));
	$form->add(field_factory::get_item_row($btns));

	return $form;
}

function verify_recurring($form, $res_form) {
	if($_POST['submit_button'] == 'Continue') return;

	$date = $form->get_by_name('Date')->get_formatted_value();
	$entertime = $form->get_by_name('Enter Time')->get_formatted_value();
	$exittime = $form->get_by_name('Exit Time')->get_formatted_value();

	if(!$date or !$entertime or !$exittime) throw new step_exception('Please enter a date, enter time, and exit time.');

	// Skip the original date
	if($date == $res_form->get_by_name('Date')->get_formatted_value() and
	 $entertime == $res_form->get_by_name('Enter Time')->get_formatted_value() and
	 $exittime == $res_form->get_by_name('Exit Time')->get_formatted_value())
	  throw new step_exception('Cannot use the same time as the original reservation.');

	// Don't allow duplicates
	$all_dates = $form->get_by_name('dates')->get_value();
	$items = $all_dates->get_items();
	$skip = true;
	foreach($items as $item) {
	  if($skip) { $skip = false; continue; }
	  $fields = $item->get_value();
	  if($date == $fields->get_by_name('date')->get_formatted_value() and
	     $entertime == $fields->get_by_name('entertime')->get_formatted_value() and
	     $exittime == $fields->get_by_name('exittime')->get_formatted_value())
	      throw new step_exception(null);
	}

	// Add a header if the table is empty
	if(!$all_dates->size()) {
	  $head = new data('header', new collection(new data('', 'Date'), new data('', 'Enter'), new data('', 'Exit')));
	  $head->set_renderer(new grid_row_renderer('heading'));
	  $all_dates->add($head);
	}

	$datefield = new data('date', $date);
	$entertimefield = new data('entertime', $entertime);
	$exittimefield = new data('exittime', $exittime);
	$row = new data('', new collection($datefield, $entertimefield, $exittimefield));
	$row->set_renderer(new grid_row_renderer());

	$all_dates->add($row);
	throw new step_exception(null);
}


function get_confirmation($send_email = false) {
	// don't think this is being used at all.
	global $pdfMsg, $email_addr;
	$tmpMsg = '';
	if ($email_addr)
		$tmpMsg = " (to $email_addr)";
	$form = new form('Reservation Submitted');
	$form->add(field_factory::get_note('<p>Your garage reservation has been placed.<br/>You have been emailed a confirmation notice'.$tmpMsg.'.</p>'.$pdfMsg.'<br/>'));
	$sub = new data('Finish');
	$sub->set_renderer(new button_renderer());
	$form->add($sub);
	return $form;
}


function make_reservation(form $res_form, form $guest_form, form $recurring_form, $dept_no, $admin=0) {

	global $auth, $pdfConfirmFile, $pdfMsg, $email_addr;

	if (!is_object($auth)) $auth = new authorization_garage_reservation(); // jody

	$pop = $res_form->get_populator();

	$db = get_db();
	$gar = $res_form->get_by_name('Garage')->get_value(); // GARAGE_ID_FK
	$day = date('d-M-y',strtotime($res_form->get_by_name('Date')->get_value()));
	if ($auth->get_authorization()==4) $comeandgo = $guest_form->get_by_name('Guests May Come and Go')->get_value();
	else $comeandgo = 0;

	if ($guest_form->get_by_name('Additional Guests')->get_value())
		$offcampus = $guest_form->get_by_name('Additional Guests')->get_value();
	else
		$offcampus = 0;

	$space_obj = $guest_form->get_by_name('Spaces');
	$is_group = ($space_obj != null);
	if($is_group) $requested_spaces = $space_obj->get_value();
	else $requested_spaces = $guest_form->get_by_name('Guests')->get_value()->size();

	// Test garage space
	$err = res_error($dept_no, $day, $gar, $requested_spaces, $admin);
	if($err) {
	  if($err instanceof Exception) throw $err;
	  else return $err;
	}

	// Set department info
	$pop->set_insert_extra('DEPT_NO_FK', "'$dept_no'");

	// Inserting price
				if (($gar==7) || ($gar==13) ) {
					$new_price =9;
					$COME_AND_GO_SQL = ",0,";
				} else {
					
	if ($comeandgo)
		$new_price = ($gar==3) ? $_SESSION['G_price_comeandgo_second'] : $_SESSION['G_price_comeandgo'];
	else
		$new_price = ($gar==3) ? $_SESSION['G_price_second'] : $_SESSION['G_price_regular'];
	
				}
	// PBC Lot 10003 (id 12) does not have comego
	$new_price = ($gar==12) ? $_SESSION['G_price_pbc_10003'] : $new_price;

	$pop->set_insert_extra('PRICE', $new_price);

	// Mark the current date and time
	$pop->set_insert_extra('CREATION_DATE', 'sysdate');


	// Add database_data values from guest form
	$res_form->add($guest_form->get_by_name('Guests May Come and Go'));
	$res_form->add($guest_form->get_by_name('Allow Extra Guests'));
	$res_form->add($guest_form->get_by_name('Additional Guests'));

	$id = $pop->insert(true);

	// Insert guests
	if($is_group) {
	  $name = $guest_form->get_by_name('Group Name')->get_database_value();
	if (strstr($name,"&")) $name = str_replace("&","&amp;",$name);
	  $db->execute("insert into PARKING.GR_GUEST values ($name, $id, 0, $requested_spaces, 0, $name)");
	}
	else {
	  $guestlist = $guest_form->get_by_name('Guests')->get_value()->get_items();
	  foreach($guestlist as $guest) {
	      list($firstname, $lastname) = explode(' ', $guest->get_name());
	      $db->execute("insert into PARKING.GR_GUEST values ('$firstname $lastname', $id, 0, 1, 0, '$lastname')");
	  }
	}

	// Prepare user ID data
	$uid = $pop->get_insert_extra('USER_ID_FK');
	$uid = $uid ? $uid : $_SESSION['cuinfo']['userid']; // jody

	// Log administrative reservations
	if($admin) {
	  $note = $res_form->get_by_name('Notes')->get_database_value();
	  $showcash = $res_form->get_by_name('Show Notes to Cashier')->is_selected() ? 1 : 0;
	  save_note($id, $note, $requested_spaces, $showcash);
	}

	// Make duplicates as necessary
	$dates = $recurring_form->get_by_name('dates')->get_value()->get_items();
	array_shift($dates);
	$duplicate_display = array();
	if($dates) {
	  // Extract template reservation details
	  $db = get_db();
	  $db->query("select PRICE, USER_ID_FK, GARAGE_ID_FK, ALLOW_EXTRA, COME_AND_GO, FRS_FK, KFS_SUB_ACCOUNT_FK, KFS_SUB_OBJECT_CODE_FK,
	to_char(RES_DATE, 'MM/DD/YY') RES_DATE, to_char(ENTER_TIME, 'HH:MI AM') ENTER_TIME, to_char(EXIT_TIME, 'HH:MI AM') EXIT_TIME
	from PARKING.GR_RESERVATION where RESERVATION_ID = $id");
	  $price = $db->get_from_top('PRICE');
	  // added by colin
	  $orig_price = $price;
	  $user = $db->get_from_top('USER_ID_FK');
	  $garage = $db->get_from_top('GARAGE_ID_FK');
	  $extra = $db->get_from_top('ALLOW_EXTRA');
	  $comego = $db->get_from_top('COME_AND_GO');
	  $frs = $db->get_from_top('FRS_FK');
	  $KFS_SUB_ACCOUNT_FK = $db->get_from_top('KFS_SUB_ACCOUNT_FK');
	  $KFS_SUB_OBJECT_CODE_FK = $db->get_from_top('KFS_SUB_OBJECT_CODE_FK');

	  $db->query("select sum(GROUP_SIZE) GUESTS from PARKING.GR_GUEST where RESERVATION_ID_FK = $id group by RESERVATION_ID_FK");
	  $requested_spaces = $db->get_from_top('GUESTS');

	  // Validation variables to avoid excatly copying the original
	  $checkdate = $db->get_from_top('RES_DATE');
	  $checkenter = $db->get_from_top('ENTER_TIME');
	  $checkexit = $db->get_from_top('EXIT_TIME');

	  foreach($dates as $incident) {
	      $fields = $incident->get_value();
	      $date = $fields->get_by_name('date')->get_database_value();

			$price = $orig_price;
			//------------------------------------------------------------------------ added by colin, remove after price change
			//if (strtotime(str_replace("'","",$date))>=strtotime("2006-07-01") && ($price==4 || $price==6))
			//	 $price++;

	      $entertime = $fields->get_by_name('entertime')->get_database_value();
	      $exittime = $fields->get_by_name('exittime')->get_database_value();

	      if($date == $checkdate and $entertime == "'$checkenter'" and $exittime == "'$checkexit'") continue;

	      // Test garage capacity for this date
	      $testday = date("d-M-y", strtotime($fields->get_by_name('date')->get_value()));
	      if(res_error($dept_no, $testday, $gar, $requested_spaces, $admin)) {
	          $duplicate_display[] = "$date: Cannot Reserve";
	          continue;
	      }

	      if (!$extra)
	      	$extra = 0;

	      $db->execute("insert into PARKING.GR_RESERVATION values (PARKING.GR_RESERVATION_ID.NEXTVAL, to_date($entertime, 'HH:MI AM'), to_date($exittime, 'HH:MI AM'), $price, to_date($date, 'MM/DD/YY'), $user, $garage, $extra, $comego, null, sysdate, $offcampus, '$dept_no', 1, '$frs', '$KFS_SUB_ACCOUNT_FK', '$KFS_SUB_OBJECT_CODE_FK')");
	      $db->query("select PARKING.GR_RESERVATION_ID.CURRVAL NUM from DUAL");
	      $newid = $db->get_from_top('NUM');

	      // Copy guest list
	      $db->query("select * from PARKING.GR_GUEST where RESERVATION_ID_FK = $id and ADDON = 0");
	      foreach($db->get_results() as $guest) {
	          $gname = $guest['GUEST_NAME'];
	          $gsize = $guest['GROUP_SIZE'];
	          $gsort = $guest['SORT_NAME'];
	          $db->execute("insert into PARKING.GR_GUEST values ('$gname', $newid, 0, $gsize, 0, '$gsort')");
	      }


	      $db->execute("insert into PARKING.GR_RESERVATION_NOTE values ($newid, $uid, 'Duplicated from $id', sysdate, null, null)");

	      $duplicate_display[] = "$date: $entertime to $exittime";
	  }
	}
	$duplicate_display = str_replace("'", "", implode("\n", $duplicate_display));
	if($duplicate_display)
		$duplicate_display = "\nThis reservation will recur at the following times:\n$duplicate_display";


	if ($requested_spaces == 1) {
		$space_word = 'space';
		$recurAppend = '';
	} else {
		$space_word = 'spaces';
		$recurAppend  = ''; // " (each day)"; // got rid of this - SR https://www.pts.arizona.edu/servicerequest/index.php?rqid=c12958611
	}



	// Send confirmation email if the user has one
	$mail = new email();

	if($db->query("select EMAIL from PARKING.GR_USER where USER_ID = $uid")) {

		$email_addr = $db->get_from_top('EMAIL');
		// Query the data we just inserted for reservation details. Not nice, but it works.
		// Get reservation details
		$db->query("select to_char(RES_DATE, 'MM/DD/YY') DAY, to_char(ENTER_TIME, 'HH:MI AM') ENTER, to_char(EXIT_TIME, 'HH:MI AM') EXIT,
						GARAGE_NAME, FRS_FK, KFS_SUB_ACCOUNT_FK, KFS_SUB_OBJECT_CODE_FK
						from PARKING.GR_RESERVATION, PARKING.GR_GARAGE where RESERVATION_ID = $id and GARAGE_ID_FK = GARAGE_ID");
		$day = $db->get_from_top('DAY');
		$entertime = $db->get_from_top('ENTER');
		$exittime = $db->get_from_top('EXIT');
		$garage = $db->get_from_top('GARAGE_NAME');
		$frs = $db->get_from_top('FRS_FK');
		$KFS_SUB_ACCOUNT_FK = $db->get_from_top('KFS_SUB_ACCOUNT_FK');
		$KFS_SUB_OBJECT_CODE_FK = $db->get_from_top('KFS_SUB_OBJECT_CODE_FK');

		$makePDF = preg_match('/(BioMedical)/i', $garage);

		$garageTxt = $garage;
		$pbc_lot_num2 = preg_match('/(10003)/i', $garageTxt) ? '10003' : '10002';
					if ($pbc_lot_num2=='10003') {  // added br drw on 9/20/2017 so 10003 will not get pdf 
						$makePDF=false;
					} // added by DRW - don't show PDF link for 10003
		$pdfMapLink = '';
		if ($makePDF) {
			$garageTxt = 'Phoenix BioMedical Campus'; // Don't want "Phoenix BioMedical 10003"
			$pbc_lot_num = preg_match('/(10003)/i', $garage) ? '10003' : '10002';
			$pbc_lot_loc = ($pbc_lot_num=='10003') ? " Lot 10003, Located at 550 E Van Buren, 85004," : " Lot 10002, Located at 714 E Van Buren, 85004,";
			// Just make some random-ish pdf file name so that it will be hard to find it.
			$pdfConfirmFile = $id . '_' . ($id * 13 + 846756) . '.pdf';
			$pdfMsg = "\nYou can print your [PDF] confirmation here: \nhttps://parking.arizona.edu/parking/garage-reservation/resPDF/$pdfConfirmFile ";
			$pdfMapLink = "\nTo view a map of Phoenix BioMedical parking lots, please visit out web site:\nhttps://parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf\n";
			$garageTxt .= $pbc_lot_loc;
		} else {
			if ($garageTxt=="South Stadium Garage" || $garageTxt=="Highland Avenue Garage" ) {
				// $pdfMsg = "You will receive a separate email with validation codes.";
			}
		}

		$guests = array();
		$db->query("select GUEST_NAME from PARKING.GR_GUEST where RESERVATION_ID_FK = $id");
		foreach($db->get_results() as $result)
		   $guests[] = $result['GUEST_NAME'];
		$guests = implode(', ', $guests);

		$mail->set_recipient($email_addr);
		$mail->set_subject('Garage Reservation Confirmation');
		$mail->set_bcc('PTS-IT-Emails@email.arizona.edu');

		$msg = "This message is to confirm that your parking reservation has been placed. \n\n$requested_spaces $space_word
			 will be reserved in the $garageTxt\n
			$day from $entertime to $exittime.$duplicate_display\n
			Guest List/Group Name: $guests.\n\n 
			This reservation will be billed to KFS account $frs. \nYour confirmation number is $id.\n\n
			To view the details of this reservation, please visit our Web site:\n https://parking.arizona.edu/parking/garage-reservation/ \n";
		//	Please share the following instructions with your guest: \n https://parking.arizona.edu/pdf/gated-garage-instructions.pdf \n\n $pdfMapLink \n";
					if ($garageTxt!=="South Stadium Garage" && $garageTxt!=="Highland Avenue Garage"  && $garageTxt!=="Second Street Garage") {
							$msg .= "Please share the following instructions with your guest: \n https://parking.arizona.edu/pdf/garage-instructions.pdf  \n\n ";
						}
			
			// add text for 6th Street Garage here  drw 9/22/2019
			if ($garageTxt!=="Second Street Garage") {
				$msg=$msg."\n\nAfter your visit, return to your vehicle and proceed to one of the exit lanes. Push the \"Assistance\" button. Advise that you have a reservation and give your name OR group name (whichever applies). Once the reservation is confirmed, the exit gate will be raised for you to exit.";
				$msg=$msg."\n\nPlease note If you arrive and the garage is FULL, press the assistance button and advise that you have a reservation. Upon confirmation of the reservation, you will be able to push the button for a ticket to enter.";
			} else {
					$msg .= "Please share the following instructions with your guest: \n https://parking.arizona.edu/pdf/second-street-garage-guest-instructions.pdf  \n\n ";	
			}
			



			// This is in add_pts_signature: "Visitor Programs\nUA Parking & Transportation Services\n1117 E. Sixth Street\nTucson, AZ 85721-0181\n(520) 621-3710\n";
					if ($garageTxt=="South Stadium Garage" || $garageTxt=="Highland Avenue Garage" ) {
		        $from = "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\nBcc:<PTS-IT-Emails@email.arizona.edu>\r\n";
   $recipient="jennyb@arizona.edu";
   $subject=$garageTxt.' New Garage Reservation';
   $text=$msg;
   $result=mail($recipient, $subject, $text, "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\nBcc:<PTS-IT-Emails@email.arizona.edu>\r\n");
		} else {
		$mail->set_message($msg);
		$mail->set_sender('PTS Visitor Programs <PTS-ParkingReservations@email.arizona.edu>');
		$mail->add_pts_signature('Visitor Programs', '(520) 621-3710');
		$mail->send();

		}

	}

	$makePDF = preg_match('/(BioMedical)/i', $garage);

	$garageTxt = $garage;
		$pbc_lot_num2 = preg_match('/(10003)/i', $garageTxt) ? '10003' : '10002';
		if ($pbc_lot_num2=='10003') {  // added br drw on 9/20/2017 so 10003 will not get pdf 
			$makePDF=false;
		} // added by DRW - don't show PDF link for 10003
	if ($makePDF) {
		$pbc_lot_num = preg_match('/(10003)/i', $garage) ? '10003' : '10002';
		$pbc_lot_loc = ($pbc_lot_num=='10003') ? " Lot 10003,\nLocated at 550 E Van Buren, 85004" : " Lot 10002,\nLocated at 714 E Van Buren, 85004";

		$garageTxt .= $pbc_lot_loc;

		// Get department name.
		$deptNameTmp = '';
		if ($dept_no) {
			$db->query("SELECT DEPT_NAME FROM PARKING.GR_DEPARTMENT WHERE DEPT_NO='$dept_no'");
			$deptNameTmp = $db->get_from_top('DEPT_NAME');
		}
		// Just make some random-ish pdf file name so that it will be hard to find it.
		$pdfConfirmFile = $id . '_' . ($id * 13 + 846756) . '.pdf';

		// Used in the admin reserve.php after printout.
		$pdfMsg = 'You can print your (PDF) confirmation here: https://parking.arizona.edu/parking/garage-reservation/resPDF/'.$pdfConfirmFile.' ';

		require('/var/www2/include/pdf/class.ezpdf.php');

		$pdf = new Cezpdf();
		$pdf->selectFont('/var/www2/include/pdf/fonts/Helvetica.afm');
		$pdf->ezText($garageTxt, 21, array("right"=>150, "justification"=>"center"));
		$pdf->ezText("\n\nDepartment: $deptNameTmp", 16, array("right"=>150));
		$pdf->ezText("\nconfirmation number(s): $id", 20, array("right"=>150));

		$pdf->ezText("\n$day from $entertime to $exittime", 20, array("right"=>150));
		$pdf->ezText("$duplicate_display\n\n", 18, array("right"=>150));
		$pdf->ezText("Spaces: $requested_spaces"."$recurAppend", 18, array("right"=>150));
		$pdf->ezText("\n\n\n\n\nPlace on driver side dashboard of vehicle, without obstruction", 12, array("justification"=>"center"));
		$pdf->ezText("\n- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - \n", 10, array("justification"=>"center"));
		//$pdf->ezText("\n\n\nBusiness Office\nParking & Transportation Services \n1117 E. Sixth Street \nPO Box 210181 \nTucson, AZ 85721-0181 \nPH: (520) 621-6912\n", 10);
		$pdf->addPngFromFile('/var/www2/html/parking/garage-reservation/administrator/pts_logo.png', 460, 735, 90);
		$pdf->addPngFromFile('/var/www2/html/parking/garage-reservation/administrator/ptsAddress.png', 450, 640, 130);
		$pdf->selectFont('/var/www2/include/pdf/fonts/Times-BoldItalic.afm');
		$pdf->saveState();
		$pdf->setColor(0.9,0.9,0.9);
		file_put_contents("/var/www2/html/parking/garage-reservation/resPDF/$pdfConfirmFile", $pdf->ezOutput());
	}
}


function res_error($dept_no, $day, $gar, $requested_spaces, $admin) {
	// added by colin on 12-14 to fix message not showing up
	$dayStamp = strtotime($day);
	$startDate = date("m/d/y",$dayStamp);
	//$endDate = date("m/d/y",mktime(0,0,0,date("m"),date("d")+1,date("y")));

	// Test that this customer has not made over 25 reservations for the day
	$db = get_db();
	$db->query("select SUM(GROUP_SIZE) AS TOTALCOUNT from PARKING.GR_RESERVATION, PARKING.GR_DEPARTMENT, PARKING.GR_GUEST where DEPT_NO_FK = DEPT_NO and DEPT_NO = '$dept_no' and RESERVATION_ID_FK = RESERVATION_ID and TRUNC(RES_DATE)=TO_DATE('$startDate','MM/DD/YY') and ACTIVE = 1 and GARAGE_ID_FK = $gar");
	$spaces = $db->get_from_top('TOTALCOUNT');

	if($requested_spaces + $spaces > 25 and !$admin) {
	  if($spaces) $note = "You already have $spaces spaces reserved at this garage on $day.";
	  else $note = "You cannot reserve over 25 spaces in one garage.";
	  return new step_exception("$note To reserve more spaces, please select a different garage or call us at 621-3710.");
	}

	// Test that total spaces reserved does not exceed the garage maximum
	$db->query("select SUM(GROUP_SIZE) AS TOTALCOUNT from PARKING.GR_RESERVATION, PARKING.GR_GUEST where TRUNC(RES_DATE)=TO_DATE('$startDate','MM/DD/YY') and ACTIVE = 1 and GARAGE_ID_FK = $gar and RESERVATION_ID_FK = RESERVATION_ID");
	$spaces = $db->get_from_top('TOTALCOUNT');
	if(!$spaces) $spaces = 0;

	$db->query("select VISITOR_MAX from PARKING.GR_GARAGE where GARAGE_ID = $gar");
	$max = $db->get_from_top('VISITOR_MAX');

	// colin made this a step_exception on 12-14 to fix garage capacity error failure
	if (($requested_spaces + $spaces)>$max && !$admin) return new step_exception ("<p class=\"accent\">The reservation has not been placed.</p><p>This garage cannot accommodate $requested_spaces more spaces on $day.<br/>To make special arrangements, please call 621-3710.</p>");

	return false;
}

function save_note($res_id, $note, $change = 'null', $display = 'null') {
	 if (!is_object($GLOBALS['auth'])) $GLOBALS['auth'] = new authorization_garage_reservation(); //jody
    $user_id = $GLOBALS['auth']->get_user_id();
	 $user_id = $user_id ? $user_id : $_SESSION['cuinfo']['userid']; // jody
    $db = get_db();
	 //$note .= " ----- ";
    if (substr($note,0,1)!="'") $note = "'" . str_replace("'","''",substr($note,0,2048)) . "'";
	$db->execute("insert into PARKING.GR_RESERVATION_NOTE values ($res_id, $user_id, $note, sysdate, $change, $display)");
}

function goback($link) { $GLOBALS['goback'] = $link; }
function updprice() { $GLOBALS['updprice'] = true; }
function updateres() { $GLOBALS['updateres'] = true; }
function deleteres() { $GLOBALS['deleteres'] = true; }
function setuserid($id = 0) { $GLOBALS['setuserid'] = $id; }
function setcustid($id) { $GLOBALS['setcustid'] = $id; }
function canceledres() { $GLOBALS['canceledres'] = true;  }
?>
