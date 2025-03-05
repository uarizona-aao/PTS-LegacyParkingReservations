<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once $docRoot.'/parking/garage-reservation/garage-reservation-include.php';
?>
<div id="main">
 <br />
 <div class="container" >
	<div class="row">
	 <div class="col-sm-4 col-md-4 col-lg-4 hidden-xs">
	 <?php
	 include_once $docRoot.'/parking/parking-menu-include.php';
	 ?>
	 </div>
	 <!-- end side nav menu -->
	 <div id="mainContent" class="col-sm-8 col-md-8 col-lg-8"  >
	 <ol class="breadcrumb">
		<li><a href="/">Home</a></li>
		<li><a href="/parking/">Parking & Permits</a></li>
		<li class="active">Garage Reservation</li>
	 </ol>
	 <h1  class="page-heading">Department Visitor Garage Reservation</h1>
	 <hr />
	 <div id="editableContent">


<?php
spinnerWaiting();
if ($auth >= 2)
{
	include_once 'gr/reservation_functions.php';
	include_once 'gr/form_functions.php';

	$res = new reservation();

	// set local variables
	$customer = $_SESSION['cuinfo'];
	$userid = $customer['userid'];
	$res->conf = $_GET['id'];

	// get reservation info, info is stored in resInfo var in class
	$res->getRes($res->conf,true);

	// If editing PBC, have them call offoce. (see also view.php and cancel.php and edit.php)
	if (preg_match('/bio.?med/si', $res->resinfo['GARAGE_NAME'][0]))
		locationHref('/parking/garage-reservation/index.php?msg=nopbc');

	// if the reservation failed, redirect to an error message
	if (!$res->resinfo)
		locationHref('/parking/garage-reservation/index.php?msg=resnotfound');

	$tmpAry = array('deptno'=>$res->resinfo['DEPT_NO_FK'][0], 'userid'=>$res->userid);

	if ($GLOBALS['DEBUG_DEBUG']) {
		$_SESSION['jjjj'] = '<hr>\n**GET[id]: '.@$_GET['id'].'****\n$res->active: '.$res->active.' <br>\naaaa ('.strtotime($res->resdate).' <='.strtotime("now").')<br>\nbbbbb '.(strtotime($res->resdate) <= strtotime("now")).'<br>cccccc'.(strtotime($res->resdate) - strtotime("now")).'<br>\nddddddddd ('.date("Y/m/d",strtotime($res->resdate)).' <= '.date("Y/m/d",strtotime("now")).' ********<hr><pre>'."\n\n";
	}


	// if not an administrator or the reservation's owner, redirect to an error message
	if ($customer['auth']<4 && !$res->checkResOwner($customer, $tmpAry))
		locationHref('/parking/garage-reservation/index.php?msg=resallowed');

	// if the reservation is not active, redirect to an error message (since you can't edit an inactive res)
	if (!$res->active)
		locationHref('/parking/garage-reservation/index.php?msg=notactive');

	////////// Added by Sal - 20070515
	// if reservation date is past, redirect to an error message (since you can't edit a reservation past its reservation date)
	if (date("Y/m/d",strtotime($res->resdate)) <= date("Y/m/d",strtotime("now")))
		locationHref('/parking/garage-reservation/index.php?msg=notactive');

	// if 'Save' was pushed
	if (isset($_POST['reserve']) || isset($_POST['reserve_x']))
	{
		// the edits array will be sent with the function
		$edits = array();
		// get the guest information
		$res->getGuests($res->conf);
		// the guests array will be sent with the function
		$guests = array();
		// add or subtract size (for group reservations)
		$sizeChange = 0;
		// set the group/guest list toggle var
		if (isset($_POST['groupGuest'])) $glg = $_POST['groupGuest'];
		// frs has changed
		if ($res->frs!=$_POST['frs']) $edits['FRS_FK'] = $_POST['frs'];

		if (@$_POST['dates'])
			$dates = explode(",",$_POST['dates']);
		else if (@$_POST['startDate']) // old school, probably never used, but just in case.
			$dates[0] = $_POST['startDate'];

		// res_date, enter_time, and/or exit_time has changed
		if ($res->resdate!=date("m/d/Y",strtotime($dates[0]))) $edits['RES_DATE'] = $dates[0];
		if ($res->resenter!=date("h:i A",strtotime($_POST['enterTime']))) $edits['ENTER_TIME'] = $_POST['enterTime'];
		if ($res->resexit!=date("h:i A",strtotime($_POST['exitTime']))) $edits['EXIT_TIME'] = $_POST['exitTime'];


		//			// res_date, enter_time, and/or exit_time has changed
		//			if ($res->resdate!=date("m/d/Y",strtotime($dates[0]))) $edits['RES_DATE'] = "TO_DATE('".$dates[0]."','MM/DD/YYYY')";
		//			if ($res->resenter!=date("h:i A",strtotime($_POST['enterTime']))) $edits['ENTER_TIME'] = "TO_DATE('01/01/05 ".$_POST['enterTime']."','MM/DD/YY HH:MI AM')";
		//			if ($res->resexit!=date("h:i A",strtotime($_POST['exitTime']))) $edits['EXIT_TIME'] = "TO_DATE('01/01/05 ".$_POST['exitTime']."','MM/DD/YY HH:MI AM')";

		// garage has changed
		if ($res->garageid!=$_POST['garage'])
			$edits['GARAGE_ID_FK'] = $_POST['garage'];

		// if a group
		if ($glg=="group") {
			// for whatever reason, it's a group res but there was no group name
			if (!isset($res->guestList[0]) || (isset($res->guestList[0]) && !$res->guestList[0])) {
				// set spaces
				$guests['GROUP_SIZE'] = $_POST['spaces'];
				// add the group to the guest table
				$guests['add'] = array(stripslashes($_POST['groupName']));
			}
			else {
				// if the group name has changed
				if (stripslashes($_POST['groupName'])!=$res->guestList[0]) {
					$guests['edit'] = stripslashes($_POST['groupName']);
					$guests['orig'] = $res->guestList[0];
				}
				// if the number of spaces have changed
				if ($res->groupCount[0]!=$_POST['spaces']) {
					// the sizeedit index sends the difference in spaces
					$guests['GROUP_SIZE'] = $_POST['spaces'];
					$guests['sizeedit'] = $_POST['spaces'];
					// the sizeorig index sends the original value (for the entry into the notes)
					$guests['sizeorig'] = $res->groupCount[0];
					// get the size change
					$sizeChange = intval($_POST['spaces']) - $res->groupCount[0];
				}
				// if the number of additional guests has changed
				if ($res->addguests!=$_POST['gaddGuests'])
					$edits['GUESTS_OFFCAMPUS'] = $_POST['gaddGuests'];
			}
		}

		// allow extra toggle has changed
		if ($res->allowextra && (!isset($_POST['allowExtra']) && !isset($_POST['ALLOW_EXTRA'])))
			$edits['ALLOW_EXTRA'] = "0";
		elseif (!$res->allowextra && isset($_POST['allowExtra']))
			$edits['ALLOW_EXTRA'] = "1";

		// if a guest list
		if ($glg=="guest") {
			// get the guest list passed from the form
			$glist = array_unique(explode(" | ",$_POST['guestList']));
			// total number of guests
			$guests['totalSize'] = count($glist);
			// calculate the guests to remove (kill index)
			$guests['kill'] = array_diff($res->guestList,$glist);
			// calculate the guests to add (add index)
			$guests['add'] = array_diff($glist,$res->guestList);
			// group size in a guest list is always 1
			$guests['GROUP_SIZE'] = 1;
			// calculate size change for notes
			$sizeChange = count($glist) - count($res->guestList);
			// if the number of additional guests has changed
			if ($res->addguests!=$_POST['laddGuests'])
				$edits['GUESTS_OFFCAMPUS'] = $_POST['laddGuests'];
		}

		// if come and go has changed, price also must change
		//		if ($res->comego && (!isset($_POST['comeGo']) && !isset($_POST['gcomeGo']))) {
		//			$edits['COME_AND_GO'] = "0";
		//			$edits['PRICE'] = "5";
		//		}
		//		elseif (!$res->comego && (isset($_POST['comeGo']) || isset($_POST['gcomeGo']))) {
		//			$edits['COME_AND_GO'] = "1";
		//			$edits['PRICE'] = "7";
		//		}


		if ($res->comego) {
			//!!!!!!!!!!!!!!!!!!!!!!! Actually, customers can't edit comeGo.
			$edits['COME_AND_GO'] = "1";
			// second st garage is 3
			$edits['PRICE'] = ($_POST['garage']==3) ? $_SESSION['G_price_comeandgo_second'] : $_SESSION['G_price_comeandgo'];
		}
		elseif (!$res->comego) {
			$edits['COME_AND_GO'] = "0";
			// second st garage is 3
			$edits['PRICE'] = ($_POST['garage']==3) ? $_SESSION['G_price_second'] : $_SESSION['G_price_regular'];
		}
		// PBC Lot 10003 (id 12) does not have comego
		$edits['PRICE'] = ($_POST['garage']==12) ? $_SESSION['G_price_pbc_10003'] : $edits['PRICE'];

		/************
		// recursive: to edit all reservations duplicated from this one
		if (isset($_POST['recursive'])) {
			$resids = $res->getOtherRes($res->conf);
			$resids[] = $res->conf;
		}
		// otherwise, set the resids array to send with one index
		else $resids = array($res->conf);
		*************/
		$resids = array($res->conf);

		//20150928 jody - people were changing reservations, and allowed to switch to a garage and bypass checkGarageMax checker.
		$maxGar = '';
		if (@$edits['GARAGE_ID_FK'])
		{
			// editing garage, so don't even factor in $_POST['spacesOrig'] in the checkGarageMax check.
			unset($_POST['spacesOrig']);
			$res->checkGarageMax($edits['GARAGE_ID_FK'], $dates[0], intval($_POST['spaces']));
			$maxGar = $res->error;
		}
		if ($maxGar)
		{
			$res->errorOut($res->error);
			// notify the customer that there were no edits made
			locationHref("/parking/garage-reservation/index.php?msg=garageMax");
		}
		else if (count($edits) || (isset($guests['kill']) && count($guests['kill'])) || (isset($guests['add']) && count($guests['add']))
			|| (isset($guests['edit']) && $guests['edit']) || (isset($guests['sizeedit']) && $guests['sizeedit']))
		{
			// if there were: edits, guests to remove, guests to add, group name to edit, size change (for groups)
			$test = $res->editRes($resids,$edits,$guests,$sizeChange);
			if (!$test && $res->error)
				locationHref("/parking/garage-reservation/edit.php?id=$res->conf&error=$res->error");
			else
				locationHref("/parking/garage-reservation/index.php?msg=edited");
		}
		else
		{
			// otherwise, notify the customer that there were no edits made
			locationHref('/parking/garage-reservation/index.php?msg=nochanges');
		}
	}
	// if cancelling
	elseif (isset($_POST['cancelres']) || isset($_POST['cancelres_x']))
	{
		// If editing PBC, have them call offoce. (see also view.php and cancel.php and edit.php)
		if (preg_match('/bio.?med/si', $res->resinfo['GARAGE_NAME'][0]))
			locationHref('/parking/garage-reservation/index.php?msg=nopbc');

		$res->cancelRes($res->conf);
		locationHref('/parking/garage-reservation/index.php?msg=cancelled');
	}

	// otherwise bring up the edit form
	else {
		// get the guest information
		$res->getGuests($res->conf);
		// set the local resInfo var
		$resInfo = $res->resinfo;
		// if a group, this will be a string with the group's name, otherwise, it will the guest list deliminated by ;
		$resInfo['guestList'] = (is_array($res->guestList)) ? implode(" | ",$res->guestList) : $res->guestList;
		// set the group count, will always be 1 for guest lists
		$resInfo['groupCount'] = $res->groupCount[0];
		// set the guest list/group toggle, groupCount>1 means that it is a group reservation
		$glg = (isset($res->groupCount[0]) && $res->groupCount[0]>1) ? "group" : "guest";
		// tells the form where to go when 'Cancel and Don't Save' is clicked
		$cancelUri = "index.php";

		if (isset($_GET['error'])) {
			echo $res->errorOut($_GET['error']);
		}

		// bring in the reservation form
		include_once 'resform.php';
	}

	// write a back link (to the main interface)
	echo '<p align="center"><b><a href="index.php">&lt;&lt; Back</a></b></p>';

}
?>

	 <br /> <br /> <br /> <br /> <br />

	 </div>
  </div>
</div>

</div>
</div>
<?php
include_once $docRoot.'/Templates/bottom_footer.php';
?>
</body>
</html>
