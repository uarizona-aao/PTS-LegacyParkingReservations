<refresh/>
<ip_filter/>

<dynamic content="namelist"/>

<?
function get_namelist() {
    $ip = new ip_tracker();
    $ip->redirect_outside();

    require_once 'list_lib.php';

    record_initials();

    $garage = isset($_GET['id']) ? $_GET['id'] : 0;
    $schedule = get_cashier_header($garage);

    // Person List
    $schedule .= "<h2>Guests</h2>";
    $query = "select GUEST_NAME, GROUP_SIZE, GROUP_EXITED, INITIALS, RESERVATION_ID, COME_AND_GO from PARKING.GR_GUEST, PARKING.GR_RESERVATION, PARKING.GR_GUEST_INITIALS where GR_GUEST.RESERVATION_ID_FK = RESERVATION_ID and GR_GUEST.RESERVATION_ID_FK = GR_GUEST_INITIALS.RESERVATION_ID_FK (+) and GUEST_NAME = GR_GUEST_INITIALS.GUEST_NAME_FK (+) and trunc(RES_DATE) >= (trunc(sysdate))-30 and ACTIVE = 1 and GROUP_SIZE = 1 and GARAGE_ID_FK = $garage order by upper(SORT_NAME)";
    $schedule .= get_listing($query, $garage);

    // Group List
    $schedule .= "<h2>Groups</h2>";
    $query = "select GUEST_NAME, GROUP_SIZE, GROUP_EXITED, RESERVATION_ID from PARKING.GR_GUEST, PARKING.GR_RESERVATION where RESERVATION_ID_FK = RESERVATION_ID and trunc(RES_DATE) >= (trunc(sysdate))-30 and ACTIVE = 1 and GROUP_SIZE > 1 and GARAGE_ID_FK = $garage order by upper(SORT_NAME)";
    $schedule .= get_listing($query, $garage);

    //$schedule .= get_initial_stats($garage);
    
    return $schedule;
}

function get_listing($query, $garage) {
    $db = get_db();
    $db->query($query);
    if(!$db->num_rows()) return "<p class=\"center\">(None)</p>";

    $grid_rows = new collection();
    $grid = new data('', $grid_rows);
    $grid->set_renderer(new list_renderer('grid_inline', array('grid_inline')));

    $name_head = new data('', 'Name');
    $init_head = new data('', 'Initial');
    $heading = new data('', new collection($name_head, $init_head));
    $heading->set_renderer(new grid_row_renderer('heading'));
    $grid_rows->add($heading);

    foreach($db->get_results() as $guest) {
        $comego = false;
        if($guest['GROUP_SIZE'] > 1) {
            $name = new data('', $guest['GUEST_NAME']);
            $hiddenname = 'count';
            $hiddenval = $guest['GROUP_EXITED'] + 1;
        }
        else {
            if (strpos(' ',$guest['GUEST_NAME'])) {
				list($firstname, $lastname) = explode(' ', $guest['GUEST_NAME']);
				$name = new data('', "$lastname, $firstname");
			}
			else $name = new data('',$guest['GUEST_NAME']);
            $hiddenname = 'guest';
            $hiddenval = urlencode($guest['GUEST_NAME']);
            $comego = $guest['COME_AND_GO'];
        }

        $res_id = $guest['RESERVATION_ID'];
        if($guest['GROUP_EXITED'] == $guest['GROUP_SIZE'] and !$comego)
            $initials = new data('', isset($guest['INITIALS']) ? $guest['INITIALS'] : '--');
        else $initials = new data('xml', get_initial_field($garage, $res_id, $hiddenname, $hiddenval));
        if($comego and $guest['GROUP_EXITED']) $initials->set_value($initials->get_value() . " <img src=\"/images/check.gif\"/>");
        
        $row = new data('', new collection($name, $initials));
        $row->set_renderer(new grid_row_renderer());
        $grid_rows->add($row);
    }
    return $grid->get_xml();
}
?>
