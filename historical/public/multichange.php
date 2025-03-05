<h1>Garage Reservations - Edit Multiple Reservations</h1>
<?php


/***************
 *
 *
 * This file is broken, I'm sure of it.
 *
 */

if (!isset($_GET['id'])) header("Location: index.php");

if (!isset($dbConn)) $dbConn = new database();

$protectPass = $dbConn->protectCheck(2);
require_once("reservation_functions.php");
require_once("form_functions.php");
$res = new reservation();

$error = false;
$errors = array(
	'none'=>'No reservations were found which were made at the same time as this one. Please use the edit function to make changes to this specific reservations.',
	'noaccess'=>'You do not have access to edit this reservation. Only the person who originally made the reservation or an administrator can edit a reservation.',
	'cancelled'=>"This reservation has been cancelled and cannot be edited. Please contact PTS Visitor Programs at $res->phone."
	//'multinoaccess'=>"You do not have access to edit one or more of the selected reservations. You may not edit reservation number(s):"
);

function renderPage () {
	global $dbConn,$res,$error;

	$resid = $_GET['id'];

	$customer = $_SESSION['cuinfo'];
	$userid = $customer['userid'];

	$stage = 1;

	//$dbConn->query("SELECT RESERVATION_ID_FK,TO_CHAR(RES_DATE,'MM-DD-YYYY') AS RESDATE FROM PARKING.GR_RESERVATION_NOTE INNER JOIN PARKING.GR_RESERVATION ON RESERVATION_ID_FK=RESERVATION_ID WHERE NOTE='Duplicated from $resid' ORDER BY RESERVATION_ID_FK");
	$query = "SELECT RESERVATION_ID_FK,TO_CHAR(RES_DATE,'MM-DD-YYYY') AS RESDATE FROM PARKING.GR_RESERVATION_NOTE INNER JOIN PARKING.GR_RESERVATION ON RESERVATION_ID_FK=RESERVATION_ID WHERE NOTE=:dupresid ORDER BY RESERVATION_ID_FK";
	$qVars = array('dupresid' => "Duplicated from $resid");
	$dbConn->sQuery($query, $qVars);

	$queryResults = $dbConn->results;

	if (!$dbConn->rows) {
		$error = 'none';
		return false;
	}

	if (isset($_POST['editThese']) || isset($_POST['editSubmit'])) {
		$stage = 2;
		$resEdits = array();
		foreach ($_POST as $field=>$val) {
			if (substr($field,0,7)=='resedit') $resEdits[] = $val;
		}

		$res->getRes($resid);
		$resInfo = $res->resInfo;

		if (isset($_POST['editSubmit'])) $stage = 3;
	}

	else $resEdits = $dbConn->results['RESERVATION_ID_FK'];

	if ($customer['auth']>=4) $resTest = array('pass'=>$resEdits,'fail'=>array());
	else $resTest = checkMultiResOwner($userid,$resEdits);
	if ($stage>1 && count($resTest['fail'])) {
		$stage = 1;
		$error = 'multinoaccess';
	}

	if ($stage==1) {
		if ($error=='multinoaccess') echo '<p class="warning">You do not have edit access to the following reservations:<br/>'.implode(', ',$resTest['fail'])."<br/>Please select again and click 'Continue'.</p>\n";
		echo "<h3 align=\"center\">&nbsp;<br/>Check the reservation(s) you would like to edit and click &quot;Continue&quot; below</h3>\n";
		echo "<p style=\"text-align:center; font-weight:bold; color:#003366;\">Reservations duplicated from $resid:</p>\n";
		echo '<form action="multichange.php?id='.$resid.'" method="post"><table align="center" class="resultsTable">'."\n";
		$count = 0;
		foreach ($resEdits as $resEdit) {
			echo '	<tr valign="middle"><td>';
			if (in_array($resEdit,$resTest['pass'])) echo "<input type=\"checkbox\" name=\"resedit$count\" value=\"$resEdit\"/>";
			else echo '<img src="/images/icons/invalid.gif" alt=""/>';
			echo "</td><td><b>$resEdit</b> - ".$queryResults['RESDATE'][array_search($resEdit,$queryResults['RESERVATION_ID_FK'])]."</td></tr>\n";
		}
		echo "</table>\n";
		echo '<p align="center"><input type="submit" class="submitter" value="Continue &gt;&gt;"/> &nbsp; <input type="button" value="Cancel and Go Back" class="submitter" onClick="window.location.href=\'index.php\';"/></p>';
		echo "</form>\n";
	}

	elseif ($stage==2) {

	}

	elseif ($stage==3) {
		// get the edits
		$edits = array();
		$ignores = array('startDate','multiDateBox','dates');
		foreach ($_POST as $field=>$val)
		{

		}

		// first edit the main res
		$res->editRes($resid,$edits);
		foreach ($resEdits as $resEdit) {
			$res->editRes($resEdit,$edits);
		}
	}

}

if ($protectPass) renderPage();

if ($error && isset($errors[$error])) {
	echo '<p class="warning">'.$errors[$error];
	if (is_array($res)) echo "\n<br/>".implode(", ",$res);
	echo "</p>\n";
}
?>