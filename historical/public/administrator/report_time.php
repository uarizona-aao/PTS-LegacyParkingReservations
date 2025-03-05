<h1>Hourly Space Usage</h1>

<dynamic content="report"/>

<?php
function get_report() {
    $control = new form();

    $gar = database_populated_menu::get_menu('Garage', 'GARAGE_ID', 'GARAGE_ID_FK', 'GARAGE_NAME', 'GR_GARAGE', new record_populator(new collection()));
    $control->add($gar);

    $day = field_factory::get_short_date_field('Date', null, 'today');
    $control->add($day);

    $view = field_factory::get_button('View', false);
    $back = field_factory::get_button('Done', false, 'index.php');
    $control->add(field_factory::get_item_row(new collection($view, $back)));
    
    $results = $control->get_xml();

    try {
        allset('Garage', 'Date');
    } catch(Exception $e) {
        return $results;
    }

    $date = $_POST['Date'];
    $garage = $_POST['Garage'];

    $db = get_db();
    $db->query("select VISITOR_MAX from PARKING.GR_GARAGE where GARAGE_ID = $garage");
    $max = $db->get_from_top('VISITOR_MAX');
    
    $results .= '<div style="margin: 2em;">';

    $scale = 5;
    for($hour = 0; $hour < 24; $hour++) {
        $db->query("select sum(GROUP_SIZE) NUM, sum(GROUP_EXITED) EXITED from PARKING.GR_GUEST, PARKING.GR_RESERVATION where RESERVATION_ID_FK = RESERVATION_ID and
trunc(RES_DATE) = to_date('$date', 'MM/DD/YY') and ACTIVE = 1 and GARAGE_ID_FK = $garage and
to_number(to_char(ENTER_TIME, 'HH24')) <= $hour and to_number(to_char(EXIT_TIME, 'HH24')) >= $hour");
        $size = $db->get_from_top('NUM');
        $exited = $db->get_from_top('EXITED');
        $remaining = $size - $exited;

        $time = date('ha', mktime($hour));

        $percent_out = round($exited / $max * 100 * $scale);
        $percent_left = round($remaining / $max * 100 * $scale);
        
        $bar = $percent_left ? "<img src=\"/images/red.gif\" height=\"10\" width=\"$percent_left\"/>" : '';
        $bar .= $percent_out ? "<img src=\"/images/blue.gif\" height=\"10\" width=\"$percent_out\"/>" : '';
        $bar .= $remaining ? "<div style=\"color: red; display: inline; margin: 0.2em;\">$remaining</div>" : '';
        if($remaining and $exited) $bar .= "+";
        $bar .= $exited ? "<div style=\"color: blue; display: inline; margin: 0.2em;\">$exited</div>" : '';
        //if($remaining and $exited) $bar .= "= $size";

        $results .= "<br/>$time |$bar";
    }

    // Key
    $results .= '<br/><br/><div style="color: red; margin-top: 1em; font-size: x-small; display: inline; margin: 1em;"><img src="/images/red.gif" height="10" width="10"/> Reserved Spaces</div>
<div style="color: blue; font-size: x-small; display: inline; margin: 1em;"><img src="/images/blue.gif" height="10" width="10"/> Exited Spaces</div>';

    $results .= "</div>";
    

    return $results;
}

function allset() {
    for($i=0; $i<func_num_args(); $i++) {
        if(!isset($_POST[func_get_arg($i)]))
           throw new Exception();
    }
}
?>
