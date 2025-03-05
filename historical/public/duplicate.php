<?php

//if (isset($_POST['confirm']) || isset($_POST['reserve']) || isset($_POST['reserve_x'])) {
//require_once 'create.php';
//} else {
//echo "<h1>Garage Reservations - Duplicate a Reservation</h1>";
//if (!isset($_GET['id'])) header("Location: index.php");
//
//if (!isset($dbConn)) $dbConn = new database();
//
//$protectPass = $dbConn->protectCheck(2);
//
//if ($protectPass) {
//require_once 'gr/reservation_functions.php';
//require_once 'gr/form_functions.php';
//$res = new reservation();
//
//$customer = $_SESSION['cuinfo'];
//$userid 	= $customer['userid'];
//$res->getRes($_GET['id'],true);
//if (!$res->resinfo) header('Location: index.php?msg=resnotfound');
//$tmpAry = array('deptno'=>$res->resinfo['DEPT_NO_FK'][0], 'userid'=>$res->userid);
//if ($customer['auth']<3 && !$res->checkResOwner($customer, $tmpAry))
//	header("Location: index.php?msg=resallowed");
//
//// confirmation page was hit, changes were pushed
//if (isset($_POST['change'])) {
//$resInfo = array();
//if (isset($_POST['change'])) {
//	array_walk($_POST,"fixPost");
//	$resInfo = array(
//		"FRS_FK"=>$_POST['frs'],
//		"RESDATE"=>$_POST['startDate'],
//		"RESSTART"=>$_POST['enterTime'],
//		"RESEND"=>$_POST['exitTime'],
//		"GARAGE_ID_FK"=>$_POST['garage']
//	);
//	$glg = $_POST['groupGuest'];
//	if ($glg=="group") {
//		$resInfo['GUEST_NAME'] = $_POST['groupName'];
//		$resInfo['GROUP_SIZE'] = $_POST['spaces'];
//		$resInfo['GUESTS_OFFCAMPUS'] = $_POST['gaddGuests'];
//	}
//	else {
//		$resInfo['guestList'] = $_POST['guestList'];
//		$resInfo['GUESTS_OFFCAMPUS'] = $_POST['laddGuests'];
//	}
//	if (isset($_POST['allowExtra'])) $resInfo['ALLOW_EXTRA'] = 1;
//	if (isset($_POST['comeGo'])) $resInfo['COME_AND_GO'] = 1;
//	if (isset($_POST['gcomeGo'])) $resInfo['COME_AND_GO'] = 1;
//}
//else $glg = "group";
//}
//// initial form
//else {
//	$resInfo = $res->resinfo;
//	$resInfo['guestList'] = (is_array($res->guestList)) ? implode(" | ",$res->guestList) : $res->guestList;
//	$resInfo['groupCount'] = $res->groupCount[0];
//	$glg = (isset($res->groupCount[0]) && $res->groupCount[0]>1) ? "group" : "guest";
//}
//$cancelUri = "index.php";
//require_once("resform.php");
//}
//}
?>