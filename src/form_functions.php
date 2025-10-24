<?php
use App\Infrastructure\Database\database;

# Godawful pricing fix.
$_SESSION['G_price_pbc_10003']	= 16.25; // not used customer-side, but useful for reference; 10003 is an admin-only lot.a
$_SESSION['G_price_pbc_10002']	= 9; // might be 11.25; watch for a ticket on this one.
$_SESSION['G_price_regular']		= 9; // was 7
$_SESSION['G_price_comeandgo']	= 9; // was 8
$_SESSION['G_price_second']		= 9; // 2'nd st garage
$_SESSION['G_price_comeandgo_second']	= 9; // was 8


function isChecked ($key,$true,$false) {
	if (isset($_POST[$key]) && $_POST[$key]) return $true;
	else return $false;
}

function yesNo ($val) {
	if ($val) return 'Yes';
	else return 'No';
}

function getVal ($array,$search,$index=false,$false='') {
	if (isset($array[$search]) && ($index===false || !is_array($array[$search]))) return $array[$search];
	elseif ($index!==false && isset($array[$search][$index])) return $array[$search][$index];
	else return $false;
}

function writeHelp ($topic) {
	return "<a href=\"javascript:needHelp('$topic');".'"><img src="/images/icons/help.gif" width="20" height="20" alt="Click for Help" align="absmiddle" border="0"/></a>';
}

function fixPost (&$val,$key) {
	$search = array("\""," & ","''");
	$replace = array("&quot;"," &amp; ",'"');
	$val = str_replace($search,$replace,stripslashes($val));
}

function breakPost (&$val,$key) {
	$val = str_replace('"',"''",stripslashes($val));
}

function writeCal ($type) {
	$return = "<a href=\"javascript:openCal('$type');\"><img src=\"calendar.gif\" border=\"0\"/></a>\n";
	return $return;
}

function garageOptions ($init="", $adminOnly="") {
	// Returns a list of <option> tags, of garage id's as values, and garage names - for select pulldown in resform.php
	global $dbConn;
	if (!isset($dbConn)) $dbConn = new database();
	$return = "";
	$dbConn->query("SELECT GARAGE_ID, GARAGE_NAME FROM PARKING.GR_GARAGE ORDER BY GARAGE_NAME");
	if ($dbConn->rows) {
		for ($i=0; $i<$dbConn->rows; $i++) {
			$gid = $dbConn->results["GARAGE_ID"][$i];
			$adminOnly = preg_replace('/,/si', '|', $adminOnly);
			if (!preg_match('/('.$adminOnly.')/i', $dbConn->results["GARAGE_NAME"][$i])) {
				$return .= "	<option value=\"$gid\"";
				if ($gid==$init) $return .= " selected";
				$return .= ">".$dbConn->results["GARAGE_NAME"][$i]."</option>\n";
			}
		}
	}
	return $return;
}

function getGarageByIP ($ip) {
	if ($ip) {
		$last = intval(substr($ip,strrpos($ip,'.')+1));

		if (substr($ip,0,12)=='128.196.81.' && ($last>=194 && $last<=203)) return "Main Gate";
		elseif (substr($ip,0,12)=='150.135.133.' && ($last>=97 && $last<=121)) return "Cherry Ave";
//		elseif (substr($ip,0,12)=='128.196.47.' && ($last>=194 && $last<=198)) return "Cherry Ave";
//		elseif (substr($ip,0,12)=='128.196.24.' && ($last>=2 && $last<=11)) "Park Ave";
		elseif (substr($ip,0,12)=='150.135.133.' && ($last>=2 && $last<=11)) "Park Ave";
//		elseif (substr($ip,0,12)=='128.196.47.' && ($last>=2 && $last<=5)) return "Second St";
		elseif (substr($ip,0,12)=='128.135.133.' && ($last>=65 && $last<=95)) return "Second St";
		elseif (substr($ip,0,12)=='128.196.3.' && ($last>=130 && $last<=180)) return "Tyndall Ave";
//		elseif (substr($ip,0,12)=='128.196.47.' && ($last>=162 && $last<=163)) return "Tyndall Ave";
		elseif (substr($ip,0,12)=='128.196.71.' && ($last>=98 && $last<=99)) return "Sixth St";
		elseif (substr($ip,0,12)=='150.135.80.' && ($last>=162 && $last<=187)) return "Highland Ave";
	}
}

function getGarageByID ($id) {
	global $dbConn;
	if (!isset($dbConn)) $dbConn = new database();
	//$dbConn->query("SELECT GARAGE_NAME FROM PARKING.GR_GARAGE WHERE GARAGE_ID=$id");
	$query = "SELECT GARAGE_NAME FROM PARKING.GR_GARAGE WHERE GARAGE_ID=:id";
	$qVars = array('id' => $id);
	$dbConn->sQuery($query, $qVars);
	if ($dbConn->rows)
		return $dbConn->results['GARAGE_NAME'][0];
}

function getGarageByName ($name) {
	global $dbConn;
	if (!isset($dbConn)) $dbConn = new database();
	//$dbConn->query("SELECT GARAGE_ID FROM PARKING.GR_GARAGE WHERE GARAGE_NAME LIKE '%$name%'");
	$query = "SELECT GARAGE_ID FROM PARKING.GR_GARAGE WHERE GARAGE_NAME LIKE :gname";
	$qVars = array('gname' => "%$name%");
	$dbConn->sQuery($query, $qVars);
	if ($dbConn->rows)
		return $dbConn->results['GARAGE_ID'][0];
}
?>