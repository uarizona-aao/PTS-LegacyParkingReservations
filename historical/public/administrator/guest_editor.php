<h1>Guest Editor</h1>
<?php

//<authorization type="garage_reservation" level="4"/>
$protectPass = $dbConn->protectCheck(4);
if (!$protectPass)
	exitWithBottom('You are not authorized.');
?>

<dynamic content="guest_edit"/>

<?php

require_once '../reservation_lib.php';

function get_guest_edit() {
    if(!isset($_GET['id']) or !isset($_GET['list']) or !isset($_GET['date']))
    	url::redirect('index.php');

    global $session;
    $form = $session->get_object('guestlist');
    if($form)
    	return $form;

    $form = get_guestlist($_GET['list'], $form, true, true);

    // Populate guest list data
    $db = get_db();
    $query = "select * from PARKING.GR_GUEST where RESERVATION_ID_FK = ".$_GET['id']." order by upper(SORT_NAME)";
    $db->query($query);
    if($_GET['list']) {
			$list = array();
			foreach($db->get_results() as $result)
				$list[] = $result['GUEST_NAME'];
			$form->get_by_name('Guests')->get_value()->set_items(new collection($list));
    }
    else {
		$form->get_by_name('Group Name')->set_value(htmlspecialchars($db->get_from_top('GUEST_NAME')), ENT_QUOTES);
		$form->get_by_name('Spaces')->set_value($db->get_from_top('GROUP_SIZE'));
    }

    return $form;
}

function validate_guest_edit($form) {
    if(!$_GET['list']) return true;

    global $session;
    $session->set_object($form, 'guestlist');

    $guests = $form->get_by_name('Guests')->get_value();
    if($_POST['submit_button'] == 'Remove Selected') {
        if(!isset($_POST['Guests'])) return false;
        $name = $_POST['Guests'];
        $guests->remove_name($name);
        $session->set_object($form, 'guestlist');
        return false;
    }
    else if($_POST['submit_button'] == 'Add Guest') {
        $form->update();
        $fname = trim($form->get_by_name('First Name')->get_value());
        $lname = trim($form->get_by_name('Last Name')->get_value());
        $new_item = "$fname $lname";

        if($fname and $lname) {
            if($guests->get_by_name($new_item)) return false;
            $guests->add(new data_item($new_item));
            $session->set_object($form, 'guestlist');
        }
        return false;
    }
    else {
        if($guests->size() == 0) return false;
        return true;
    }
}

function submit_guest_edit($form) {
    $db = get_db();
    $id = $_GET['id'];
    $query = "select sum(GROUP_SIZE) NUM from PARKING.GR_GUEST where RESERVATION_ID_FK = $id";
    $db->query($query);
    $oldsize = $db->get_from_top('NUM');
    if($_GET['list']) {
        $guests = $form->get_by_name('Guests')->get_value()->get_items();
        $spaces = sizeof($guests);
        $names = array();
        foreach($guests as $item) {
            list($firstname, $lastname) = explode(' ', $item->get_name());
            $name = "'$firstname $lastname'";
            // colin changed the below query adding the reservation id to fix error with guest list additions not going in on 7-19
			$db->query("select * from PARKING.GR_GUEST where GUEST_NAME = $name AND RESERVATION_ID_FK = $id");
            if(!$db->num_rows()) $db->execute("insert into PARKING.GR_GUEST (GUEST_NAME,RESERVATION_ID_FK,GROUP_EXITED,GROUP_SIZE,ADDON,SORT_NAME) values ($name, $id, 0, 1, 0, '$lastname')");
            $names[] = $name;
        }
        $names = implode(',', $names);
        $db->execute("delete from PARKING.GR_GUEST where RESERVATION_ID_FK = $id and GUEST_NAME not in ($names)");
    }
    else {
        $group = $form->get_by_name('Group Name')->get_database_value();
        $spaces = $form->get_by_name('Spaces')->get_database_value();
        $db->execute("update PARKING.GR_GUEST set GUEST_NAME = $group, GROUP_SIZE = $spaces where RESERVATION_ID_FK = $id");
    }
    $change = $spaces - $oldsize;
    $guestword = (abs($change) == 1) ? 'guest' : 'guests';
    if($change > 0) $note = "$change $guestword added";
    else if ($change < 0) $note = abs($change)." $guestword removed";
    else $note = "Guestlist updated";
    save_note($id, "'$note'", $change);
    return 'Guestlist Updated';
}

function get_action() {
    return "reservation.php?date=".$_GET['date']."&amp;id=".$_GET['id'];
}
?>
<div align="center" style="padding-left:330px; padding-top:20px; font-weight:bold; white-space:nowrap;">* After you Remove any guests, you then need to click Update Guestlist</div>
