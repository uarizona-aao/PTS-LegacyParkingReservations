
<?php
function get_cashier_header($garage) {
    $db = get_db();
    $db->query("select GARAGE_NAME, VISITOR_MAX from PARKING.GR_GARAGE where GARAGE_ID = $garage");
    $gname = $db->get_from_top('GARAGE_NAME');
    $capacity = $db->get_from_top('VISITOR_MAX');
    $db->query("select sum(GROUP_SIZE) SPACES from PARKING.GR_GUEST, PARKING.GR_RESERVATION where RESERVATION_ID = RESERVATION_ID_FK and GARAGE_ID_FK = $garage and trunc(RES_DATE) = trunc(sysdate) and ACTIVE = 1 group by RES_DATE");
    $spaces = $db->get_from_top('SPACES');
    if(!$spaces) $spaces = 'No Spaces';
    else if($spaces == 1) $spaces = 'One Space';
    else $spaces = "$spaces Spaces";

    // Last-minute reservations
    $db->query("select sum(SIZE_CHANGE) NUM from PARKING.GR_RESERVATION_NOTE, PARKING.GR_RESERVATION where RESERVATION_ID_FK = RESERVATION_ID and trunc(RES_DATE) = trunc(sysdate) and ACTIVE = 1 and trunc(DATE_RECORDED) = trunc(sysdate) and GARAGE_ID_FK = $garage");
    $change = $db->get_from_top('NUM');
    if($change < 0) $changenote = " - <div style=\"display: inline; color: #944;\">$change Removed Today</div>";
    else if($change > 0) $changenote = " - <div style=\"display: inline; color: #944;\">$change Added Today</div>";
    else $changenote = '';

    $link = basename($_SERVER['PHP_SELF']) == 'name_listing.php' ? "<a href=\"daily_listing.php?id=$garage\">Schedule View</a>" : "<a href=\"name_listing.php?id=$garage\">Name View</a>";

    return "<h1>$gname<br/>".date('l, F d, Y')."<br/>$spaces Reserved$changenote</h1><p class=\"center\">$link | <a href=\"other_list.php?id=$garage\">Other Garages</a> | <a href=\"exception.php?id=$garage\">Exception Report</a></p>";
}

function get_initial_field($garage, $res_id, $hidden_name, $hidden_value) {
    $page = $_SERVER['PHP_SELF'];
    $initial = "<form action=\"$page?id=$garage\" hide=\"true\"><input type=\"hidden\" name=\"res\" value=\"$res_id\"/>";
    $initial .= "<input type=\"hidden\" name=\"$hidden_name\" value=\"$hidden_value\"/>";
    $initial .= "<field name=\"initials\" nolabel=\"true\" size=\"2\" maxlength=\"2\"/></form>";
    return $initial;
}

function record_initials() {
    $db = get_db();
    if(isset($_POST['res'])) {
        try {
            $initials = strtoupper(trim($_POST['initials']));
            if(strlen($initials) != 2) throw new Exception();
            
            $res = $_POST['res'];
            
            // A guest has exited
            if(isset($_POST['guest'])) {
                $guest = urldecode($_POST['guest']);
                $db->execute("update PARKING.GR_GUEST set GROUP_EXITED = 1 where RESERVATION_ID_FK = $res and GUEST_NAME = '$guest'");

                // Record cashier's initials. Note integrity constraint exceptions are caught below
                $db->query("select * from PARKING.GR_GUEST_INITIALS where RESERVATION_ID_FK = $res and GUEST_NAME_FK = '$guest' and INITIALS = '$initials'");
                if($db->num_rows()) $db->execute("update PARKING.GR_GUEST_INITIALS set GROUP_COUNT = GROUP_COUNT + 1 where RESERVATION_ID_FK = $res and GUEST_NAME_FK = '$guest' and INITIALS = '$initials'");
                else $db->execute("insert into PARKING.GR_GUEST_INITIALS values ('$initials', '$guest', $res, 1)");
            }
            
            // A member of a group has exited
            else if(isset($_POST['count'])) {
                $count = $_POST['count'];
                $db->query("select GROUP_SIZE, GROUP_EXITED, GUEST_NAME from PARKING.GR_GUEST where RESERVATION_ID_FK = $res");
                $size = $db->get_from_top('GROUP_SIZE');
                $exited = $db->get_from_top('GROUP_EXITED');
                $guest = $db->get_from_top('GUEST_NAME'); // For use with initialing below
                $extra = ($size == $exited and $size+1 == $count) ? "GROUP_SIZE = $count, ADDON = ADDON + 1," : '';

                $db->query("select * from PARKING.GR_GUEST where RESERVATION_ID_FK = $res and GROUP_EXITED + 1 = $count and ADDON < 25");
                if($db->num_rows()) {
                    $db->execute("update PARKING.GR_GUEST set $extra GROUP_EXITED = $count where RESERVATION_ID_FK = $res and GROUP_EXITED + 1 = $count");

                    // Record cashier's initials. Note integrity constraint exceptions are caught below
                    $where = "INITIALS = '$initials' and GUEST_NAME_FK = '$guest' and RESERVATION_ID_FK = $res";
                    $db->query("select * from PARKING.GR_GUEST_INITIALS where $where");
                    if($db->num_rows()) $db->execute("update PARKING.GR_GUEST_INITIALS set GROUP_COUNT = GROUP_COUNT + 1 where $where");
                    else $db->execute("insert into PARKING.GR_GUEST_INITIALS values ('$initials', '$guest', $res, 1)");
                }
            }

        } catch(Exception $e) { }
    }

    // Addon
    else if(isset($_POST['res_extra'])) {
        try {
            // Copied from above
            $initials = strtoupper(trim($_POST['initials']));
            if(!preg_match('/^[a-zA-Z]{2}$/', $initials)) throw new Exception();

            $guest = trim($_POST['extra_guest']);
            if(!$guest or !preg_match('/^[a-zA-Z]+ [a-zA-Z]+$/', $guest)) throw new Exception();

            list($firstname, $lastname) = explode(' ', $guest);

            $res = $_POST['res_extra'];
            try {
                $db->execute("insert into PARKING.GR_GUEST values ('$guest', $res, 1, 1, 1, '$lastname')");
                $db->execute("insert into PARKING.GR_GUEST_INITIALS values ('$initials', '$guest', $res, 1)");
            } catch (Exception $e) { var_dump($e); exit; }
        } catch (Exception $e) { }
    }
}

function get_initial_stats($garage) {
    $db = get_db();
    $db->query("select INITIALS, sum(GROUP_COUNT) NUM from PARKING.GR_GUEST_INITIALS, PARKING.GR_RESERVATION where RESERVATION_ID = RESERVATION_ID_FK AND trunc(RES_DATE) = trunc(sysdate) and ACTIVE = 1 and GARAGE_ID_FK = $garage group by INITIALS order by NUM desc");
    if($db->num_rows()) {
        $results = $db->get_results();
        $items = array();
        foreach($results as $result) {
            $initials = $result['INITIALS'];
            $num = $result['NUM'];
            $items[] = "<b>$initials</b>: $num";
        }
        return '<h2>Guests Signed Out</h2><p>'.implode(' | ', $items).'</p>';
    }
    return '';
}
?>