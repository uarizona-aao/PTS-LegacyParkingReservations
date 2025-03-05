<h1>View Exception Reports</h1>
<?php

//<authorization type="garage_reservation" level="4"/>
$protectPass = $dbConn->protectCheck(4);
if (!$protectPass)
	exitWithBottom('You are not authorized.');
?>

<p class="center">Click a date/time to mark off an exception report when you are done.</p>

<dynamic content="incident"/>

<p class="center">[<a href="index.php">Go Back</a>]</p>

<?php
function get_incident() {
    // Handle link clicks
	 if (!is_object($GLOBALS['auth'])) $GLOBALS['auth'] = new authorization_garage_reservation(); //jody
    if(isset($_GET['id'])) {
        $id = $_GET['id'];
        $userid = $GLOBALS['auth']->get_user_id();
        $db = get_db();
        $db->execute("update PARKING.GR_GUEST_EXCEPTION set COMPLETE_USER_FK = $userid where EX_ID = $id");
    }

    $items = new collection();
    $pop = new recordset_populator
    (
     $items, array('GR_GUEST_EXCEPTION', 'GR_GARAGE', "GR_USER"),
     array('EX_ID', 'EXTIME', 'GARAGE_NAME', 'EX_NAME', 'EX_CONTACT', 'EX_PHONE', 'EX_NOTES', 'EX_INITIALS', "NETID"),
     'EX_GARAGE_FK = GARAGE_ID AND USER_ID(+)=COMPLETE_USER_FK',
     "TO_CHAR(EX_TIME,'YYYY-MM-DD') DESC"
     );
    $pop->set_select_expression('EXTIME', "TO_CHAR(EX_TIME, 'MM/DD/YY HH:MI AM')");
    $pop->set_select_expression('EX_INITIALS', 'UPPER(EX_INITIALS)');

    $pop->set_components('id', 'time', 'gar', 'name', 'cont', 'phone', 'notes', 'initials', "user");
    $pop->set_headings('id', 'Time', 'Garage', 'Guest', 'Contact', 'Phone', 'Notes', 'Initials', "Reviewed By");

    $pop->set_id_link('time');

    $pop->populate();

    $view = new data('Test', $items);
    $view->set_renderer(new list_renderer('grid'));
    return $view;
}
?>
