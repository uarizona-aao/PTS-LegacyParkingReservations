<h1>Reservations Report</h1>
<?php
	$showDate = date('l, F j, Y');
	$useDate = date('d-M-Y');
	
	$db = get_db();
	$db->query("SELECT RESERVATION_ID,TO_CHAR(ENTER_TIME,'HH:MI AM') AS ENTER,TO_CHAR(EXIT_TIME,'HH:MI AM') AS EXIT,ALLOW_EXTRA,COME_AND_GO,GUESTS_OFFCAMPUS,FRS_FK,GARAGE_NAME,GUEST_NAME,GROUP_SIZE FROM PARKING.GR_RESERVATION R INNER JOIN PARKING.GR_GARAGE ON GARAGE_ID=GARAGE_ID_FK INNER JOIN PARKING.GR_GUEST ON RESERVATION_ID_FK=RESERVATION_ID WHERE R.ACTIVE=1 AND TRUNC(RES_DATE)='$useDate' ORDER BY GARAGE_NAME,TO_CHAR(ENTER_TIME,'HH24:MI'),GUEST_NAME");
	
	$f = "Reservations Report for $showDate";
	
	function writeSpaces ($spaces) {
		$r = '';
		for ($s=1; $s<=$spaces; $s++) $r .= ' ';
		return $r;
	}
	
	$rows = $db->num_rows();
	$results = $db->get_results();
	
	if ($rows) {
		$gar = '';
		for ($i=0; $i<$rows; $i++) {
			if ($gar!=$results[$i]['GARAGE_NAME']) {
				$f .= "\n\n".$results[$i]['GARAGE_NAME']."\nCONF	NAME                    SPACES	ENTER		EXIT		A/E	C/G	O/C\n";
				$gar = $results[$i]['GARAGE_NAME'];
			}
			
			$f .= $results[$i]['RESERVATION_ID'].'	'.$results[$i]['GUEST_NAME'];
			$f .= writeSpaces(23-strlen($results[$i]['GUEST_NAME']));
			$f .= '		'.$results[$i]['GROUP_SIZE'].'	'.$results[$i]['ENTER'].'	'.$results[$i]['EXIT'].'	';
			if ($results[$i]['ALLOW_EXTRA']) $f .= ' X';
			$f .= '	';
			if ($results[$i]['COME_AND_GO']) $f .= ' X';
			$f .= '	'.$results[$i]['GUESTS_OFFCAMPUS']."\n";
		}
		
	}
	
	else $f .= 'NO RESERVATIONS FOUND';
	
	$file = fopen("reports/dailyreport.html","w+",false);
	fwrite($file,$f);
	fclose($file);
	
	$test = shell_exec("lpr -P madhatter /var/www2/html/parking/garage-reservation/administrator/reports/dailyreport.html");
	
	//unlink("dailyreport.html");
?>
<h3>Report Printed</h3>
