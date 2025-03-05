<h1>Daily Reservations</h1>
<?PHP
	$unavailable = false;
	if ($unavailable) echo "<div style=\"text-align:center;\"><b>The Visitor Reservations system is currently unavailable.</b><br/>If you need to make a reservation, please contact PTS Visitor Programs at 621-3710.</div>\n";
else {
?>
<ip_filter/>
<dynamic content="garage"/>

<?php
// Automatically redirect to appropriate garage, if possible
$ip = new ip_tracker();
$garage = $ip->get_garage();
if($garage) {
    $db = get_db();
    if($db->query("select GARAGE_ID from PARKING.GR_GARAGE where GARAGE_NAME = '$garage'"))
        url::redirect("daily_listing.php?id=".$db->get_from_top('GARAGE_ID'));
}

function get_garage() {
    // Garage-picker form for all non-garage PTS IPs
    $form = new form('Select a Garage');

    $gar_list = new collection();
    $pop = new recordset_populator($gar_list, 'GR_GARAGE', array('GARAGE_ID', 'GARAGE_NAME'), null, 'GARAGE_NAME');
    $pop->set_components('id', 'name');
    $pop->set_id_link('name', 'daily_listing2.php');
    $pop->populate();

    $view = new data('Garage', $gar_list);
    $view->set_renderer(new list_renderer('grid'));
    $form->add($view);

    return $form;
}

}
?>
