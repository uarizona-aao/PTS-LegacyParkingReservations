<h1>Guests in Other Garages</h1>
<ip_filter/>

<dynamic content="list"/>

<?php
function get_list() {
    $ip = new ip_tracker();
    $ip->redirect_outside();

    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $db = get_db();
    $db->query("select GARAGE_ID, GARAGE_NAME from PARKING.GR_GARAGE where GARAGE_ID != $id");
    $xml = "<p class=\"center\"><a href=\"daily_listing.php?id=$id\">Go Back</a></p>";
    foreach($db->get_results() as $garage) {
        $garage_id = $garage['GARAGE_ID'];
        if(!$db->query("select GUEST_NAME from PARKING.GR_GUEST, PARKING.GR_RESERVATION where RESERVATION_ID_FK = RESERVATION_ID 
and GROUP_SIZE = 1 and trunc(RES_DATE) = trunc(sysdate) and ACTIVE = 1 and GARAGE_ID_FK = $garage_id order by SORT_NAME")) continue;
        $xml .= "<h2>".$garage['GARAGE_NAME']."</h2><ul>";
        foreach($db->get_results() as $guest) {
			 if (strpos(' ',$guest['GUEST_NAME'])) {
				list($firstname, $lastname) = explode(' ', $guest['GUEST_NAME']);
				$name = new data('', "$lastname, $firstname");
				 $xml .= "<li>$lastname, $firstname</li>";
			}
			else {
				$name = new data('',$guest['GUEST_NAME']);
				$xml .= '<li>'.$guest['GUEST_NAME']."</li>";
           }
        }
        $xml .= "</ul>";
    }
    return $xml;
}
?>
