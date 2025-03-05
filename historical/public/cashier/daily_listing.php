<refresh/>
<ip_filter/>

<dynamic content="daily"/>

<?php

function get_daily() {
	require_once 'list_lib.php';

	record_initials();

	$garage = isset($_GET['id']) ? $_GET['id'] : 0;

	if (ctype_alnum($garage) || !$garage)
		;
	else
		$garage = '';

	$schedule = get_cashier_header($garage);

	$db = get_db();

	// query is safe!
	$db->query("SELECT COUNT(EX_ID) AS TALLY FROM PARKING.GR_GUEST_EXCEPTION WHERE TRUNC(EX_TIME)=TRUNC(SYSDATE) AND EX_GARAGE_FK=$garage");

	$results = $db->get_results();

	$schedule .= '<p class="center"><b>Current Exception Tally:</b> '.$results[0]['TALLY']."</p>\n";

	// query is safe!
	$db->query("select RESERVATION_ID, DEPT_NAME,DEPT_NO_FK, (select sum(GROUP_SIZE) from PARKING.GR_GUEST where RESERVATION_ID_FK = RESERVATION_ID) SPACES, to_char(ENTER_TIME, 'HH:MI AM') ENTER_TIME, to_char(EXIT_TIME, 'HH:MI AM') EXIT_TIME, ALLOW_EXTRA, COME_AND_GO from PARKING.GR_RESERVATION, PARKING.GR_DEPARTMENT, PARKING.GR_USER where USER_ID_FK = USER_ID and DEPT_NO = DEPT_NO_FK and GARAGE_ID_FK = $garage and trunc(RES_DATE) = trunc(sysdate) and ACTIVE = 1 order by to_date(ENTER_TIME, 'HH:MI AM')");

	if(!$db->num_rows()) return "$schedule<p><i>There are no reservations today.</i></p>";

	$results = $db->get_results();
	foreach($results as $result) {

		$schedule .= '<h2>'.makeHtmlSpecial($result['ENTER_TIME']).' - '.makeHtmlSpecial($result['EXIT_TIME']).'</h2>';
		$spaceword = $result['SPACES'] == 1 ? 'space' : 'spaces';
		/*if ($garage!=3) */
		$schedule .= '<p>'.makeHtmlSpecial($result['DEPT_NAME']).' ('.makeHtmlSpecial($result['SPACES'])." $spaceword) (Conf #: ".makeHtmlSpecial($result['RESERVATION_ID']).")</p>";
		//else $schedule .= "<p>".$result['DEPT_NO_FK']."</p>";
		$comeandgo = makeHtmlSpecial($result['COME_AND_GO']);
		$allow_extra = makeHtmlSpecial($result['ALLOW_EXTRA']);
		if($comeandgo) $schedule .= '<p class="accent">Guests may come and go</p>';
		if($allow_extra) $schedule .= '<p class="accent">Allow Extra Guests</p>';

		$id = makeHtmlSpecial($result['RESERVATION_ID']);
		// query is safe!
		$db->query("select REPLACE(GUEST_NAME,' & ',' &amp; ') AS GUEST_NAME, GROUP_EXITED, GROUP_SIZE, ADDON from PARKING.GR_GUEST where RESERVATION_ID_FK = $id order by GROUP_EXITED, upper(SORT_NAME)");
		$group = $db->get_results();

		$schedule .= '<ul>';

		// It's a group
		if($db->num_rows() == 1 and $group[0]['GROUP_SIZE'] > 1) {
			$name = $group[0]['GUEST_NAME'];
			$exited = $group[0]['GROUP_EXITED'];
			$size = $group[0]['GROUP_SIZE'];
			$addon = $group[0]['ADDON'];
			$new_count = $exited + 1;
			$field = ($exited < $size or ($allow_extra and $addon < 10)) ? get_initial_field($garage, $id, 'count', $new_count) : '';
			$schedule .= "<li>$name (<b>$exited</b> / <b>$size</b> exited) $field</li>";
			$schedule .= '</ul>';
	  }

		// It's a guest list
		else {
			foreach($group as $person) {
				$name = makeHtmlSpecial($person['GUEST_NAME']);
				$encname = urlencode($name);
				$exited = makeHtmlSpecial($person['GROUP_EXITED']);
				$size = makeHtmlSpecial($person['GROUP_SIZE']);
				$field = get_initial_field($garage, $id, 'guest', $encname);
				$item = (!$exited or $comeandgo) ? "<b>$name</b> $field" : $name;
				if($exited and $comeandgo) $item .= " <img src=\"/images/check.gif\"/>";
				$schedule .= "<li>$item</li>";
			}
			$schedule .= '</ul>';
			if($allow_extra) {
				// query is safe!
				$db->query("select sum(ADDON) ADDS from PARKING.GR_GUEST where RESERVATION_ID_FK = $id");
				if($db->get_from_top('ADDS')<25)
					$schedule .= "<p><form action=\"daily_listing.php?id=$garage\" hide=\"true\">Extra Guest: <field nolabel=\"true\" name=\"extra_guest\"/><input type=\"hidden\" name=\"res_extra\" value=\"$id\"/> Initial: <field name=\"initials\" nolabel=\"true\" size=\"2\" maxlength=\"2\"/> <button type=\"submit\" name=\"Add\"/></form></p>";
	      }
	  }

		// Add notes if required
		// query is safe!
		if($db->query("SELECT REPLACE(NOTE,' & ',' &amp; ') AS NOTE FROM PARKING.GR_RESERVATION_NOTE WHERE RESERVATION_ID_FK=$id AND CASHIER_DISPLAY=1")) {
			   //$schedule .= '<p>$db->get_from_top('NOTE').'</p>';
			// colin change above to following on 20070215 because only one notes instance was showing
			$schedule .= '<p>';
			$all_notes = $db->get_results();
			foreach ($all_notes as $row=>$notation) {
				if ($row)
					$schedule .= "<br/>\n";
				if (!preg_match('/^'.preg_quote('Removed Guest(s): ').'/i', $notation['NOTE']) && !preg_match('/^'.preg_quote('Added Guest(s): ').'/i', $notation['NOTE'])) {
					$schedule .= $notation['NOTE'];
				}
			}
			$schedule .= "</p>\n";
		}
	}

	$schedule .= get_initial_stats($garage);

	return $schedule;
}


function makeHtmlSpecial($str) {
	return htmlspecialchars(htmlspecialchars_decode($str, ENT_QUOTES), ENT_QUOTES);
}


?>
<p style="text-align:center;"><a href="/parking/garage-reservation/cashier/">&lt;&lt; Back to Main Cashier Screen</a></p>
<p style="text-align:center;"><a href="https://www.pts.arizona.edu/garage_reservation/administrator/">&lt;&lt; Back to Administrator Screen</a></p>
