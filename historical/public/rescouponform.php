<?php
//xxx include_once 'include/gr/garage_reservation.php'; // $_SESSION['G_price_regular'] and $_SESSION['G_price_comeandgo']
?>

<script language="JavaScript" type="text/javascript" src="/js/forms.js"></script>
<script language="JavaScript" type="text/javascript" src="/js/base.js"></script>
<script language="JavaScript" type="text/javascript">

function checkForm () {

	var dateRe = /[0-9]{2}\/[0-9]{2}\/20[0-9]{2}/;
	var timeRe = /[0-9]{1,2}\:[0-9]{2}\s[AM|am|PM|pm]{2}/;
	var numRe = /[0-9]+/;
	var dateReturn = "";
	var alerts = "";
	var daDates = new Array();

	with (document.resForm) {

		if ((window.RegExp && !frsRe.test(frs.value)))
			alerts += "Please enter a valid KFS number\n";
		else if (frames['frsCheckFrame'].document.images.validity.src.indexOf("/invalid.gif")>0)
			alerts += "You do not have access to this KFS number\n";

		if (!startDate.value || startDate.value=="MM/DD/YYYY" || !dateRe.test(startDate.value) || startDate.value.indexOf("/")!=2) {
			alerts += "Please enter a valid start date (MM/DD/YYYY)\n";
		} else {
			/*var monthCheck = parseInt(startDate.value.substr(0,2));
			var dateCheck = parseInt(startDate.value.substr(3,2));
			if (isNaN(monthCheck) || monthCheck==0 || monthCheck>12) alerts += "Please enter a valid start date (MM/DD/YYYY)\n";
			else if (isNaN(dateCheck)) alerts += "Please enter a valid start date (MM/DD/YYYY)\n";
			else if (dateCheck>31) alerts += "Please enter a valid start date (MM/DD/YYYY)\n";
			else if ((monthCheck==1 || monthCheck==3 || monthCheck==5 || monthCheck==7 || monthCheck==8 || monthCheck==11 || monthCheck==12) && dateCheck>31) alerts += "Please enter a valid start date (MM/DD/YYYY)\n";
			else if (monthCheck==2 && dateCheck>29) alerts += "Please enter a valid start date (MM/DD/YYYY)\n";
			else if (dateCheck>30) alerts += "Please enter a valid start date (MM/DD/YYYY)\n";
			else {*/
			if (document.resForm.dates) {
				dates.value = startDate.value;
				daDates[0] = startDate.value;
			}
			//}
		}

		if (document.resForm.multiDateBox && multiDateBox.options[0].text!="Enter a Date") {
			for (var i=0; i<multiDateBox.options.length; i++) {
				if (daDates.toString().indexOf(multiDateBox.options[i].text)==-1) {
					dates.value += ","+multiDateBox.options[i].text;
					daDates[daDates.length] = multiDateBox.options[i].text;
					//alert(daDates.toString().indexOf(multiDateBox.options[i].text));
				}
				else alerts += "One or more dates are duplicated\n";
			}
		}

		// Trim spaces, and add, if nessassary
		var enterTimeStr = String(enterTime.value);
		enterTimeStr = enterTimeStr.replace(/\s+$/i, ""); // trim space
		enterTimeStr = enterTimeStr.replace(/^\s+/i, ""); // trim space
		enterTimeStr = enterTimeStr.replace(/(\d)([AP]M)$/i, "$1 $2"); // inset a space
		enterTime.value = enterTimeStr;
		var exitTimeStr = String(exitTime.value);
		exitTimeStr = exitTimeStr.replace(/\s+$/i, ""); // trim space
		exitTimeStr = exitTimeStr.replace(/^\s+/i, ""); // trim space
		exitTimeStr = exitTimeStr.replace(/(\d)([AP]M)$/i, "$1 $2"); // inset a space
		exitTimeStr = exitTimeStr.replace(/12:00 AM/i, "11:59 PM"); // inset a space
		exitTime.value = exitTimeStr;


		// Make sure start time is not before end time.
		var dtStart	= new Date("1/1/2007 " + enterTime.value);
		var dtEnd	= new Date("1/1/2007 " + exitTime.value);
		diff_in_millisec_a = dtEnd - dtStart;
		if (diff_in_millisec_a < 0)
			alerts += "End time is before Start time!\n";
		// Make sure times are within range - when the garage is open.
		var defaultStart	= new Date("1/1/2007 <?php echo $_SESSION['default_start_time'];?>");
		var defaultEnd		= new Date("1/1/2007 <?php echo $_SESSION['default_end_time'];?>");
		diff_in_millisec_b = dtStart - new Date("1/1/2007 <?php echo $_SESSION['max_start_time'];?>");
		diff_in_millisec_c = dtStart - new Date("1/1/2007 <?php echo $_SESSION['max_end_time'];?>");
		if (diff_in_millisec_b < 0 || diff_in_millisec_c > 0)
			alerts += "Start time is out of range!\n";
		diff_in_millisec_b = dtEnd - new Date("1/1/2007 <?php echo $_SESSION['max_start_time'];?>");;
		diff_in_millisec_c = dtEnd - new Date("1/1/2007 <?php echo $_SESSION['max_end_time'];?>");;
		if (diff_in_millisec_b < 0 || diff_in_millisec_c > 0)
			alerts += "End time is out of range!\n";

		if ((window.RegExp && !timeRe.test(enterTime.value)) || enterTime.value.indexOf(".")>-1 || (enterTime.value.length==5 && enterTime.value.indexOf(":")!=2) || (enterTime.value.length==4 && enterTime.value.indexOf(":")!=1))
			alerts += "Please enter a valid 'Enter Time' (HH:MI AM)\n";

		if ((window.RegExp && !timeRe.test(exitTime.value)) || exitTime.value.indexOf(".")>-1 || (exitTime.value.length==5 && exitTime.value.indexOf(":")!=2) || (exitTime.value.length==4 && exitTime.value.indexOf(":")!=1))
			alerts += "Please enter a valid 'Exit Time' (HH:MI AM)\n";

		if (!garage.value)
			alerts += "Please select a garage\n";

		// guest list
		if (groupGuest[0].checked) {
			if (guestName.value)
				alerts += "Please click 'Add Guest' to add the guest name to your guest list, or please remove the guest name from the box\n";
			else if (!guestListBox.options.length || guestListBox.options[0].text=="Please Add Guests to Continue") {
				if (guestName.value)
					alerts += "Please click 'Add Guest' to add the guest to your guest list\n";
				else
					alerts += "Please enter at least one guest in the guest list\n";
			}
			else {
				for (var i=0; i<guestListBox.options.length; i++) {
					if (guestList.value)
						guestList.value += " | ";
					guestList.value += guestListBox.options[i].text;
				}
			}
			//if (!laddGuests.value || laddGuests.value==' ') alerts += "Please enter a number for Additional Guests\n";
			//else if ((window.RegExp && !numRe.test(laddGuests.value))) alerts += "Numbers only in the Additional Guests field\n";
		}
		// group
		else {
			if (!groupName.value)
				alerts += "Please enter the group name\n";
			if ((window.RegExp && !numRe.test(spaces.value)) || !spaces.value.length || spaces.value==' '){
				alerts += "Please enter a number of spaces\n";
			}else if (!spaces.value || spaces.value=="0" || spaces.value=="00"){
				alerts += "Number of spaces must be greater than 0\n";
			}
			//if (!gaddGuests.value) alerts += "Please enter a number for Additional Guests\n";
			//else if ((window.RegExp && !numRe.test(gaddGuests.value)) || gaddGuests.value==' ') alerts += "Numbers only in the Additional Guests field\n";
		}
	}

	if (alerts) {
		alert(alerts);
		return false;
	}
}
</script>

<?php
echo '<h2>';

$inEdit = false;
if (strpos($_SERVER['PHP_SELF'],'create.php')) {
	echo 'Make a Reservation';

} elseif (strpos($_SERVER['PHP_SELF'],'edit.php')) {
	echo 'Edit Reservation '.getVal($resInfo,'RESERVATION_ID',0);
	$inEdit = true;
}
//} elseif (strpos($_SERVER['PHP_SELF'],'duplicate.php')) {
//	echo 'Duplicate Reservation '.getVal($resInfo,'RESERVATION_ID',0);
//}
echo "</h2>\n";

echo $errMsg;

?>
<div class="resBox">


<?php
// ***********************************  CALENDAR AND TIME   ******************************
// First insert this php code BEFORE the <form> tag, then you can insert a calendar(s) anywhere within the <form> tag(s):
//    <script language="JavaScript" type="text/javascript">
//    c_showTime = true; // to show or not to show time fields
//    c_autoSubmit = false; // when you click a day in the calendar, should the form submit itself.
//    </script>
//    include "$c_calRootDir/dateAndTime.php";
//
// Update the root directory (where dateAndTime.php is located):
$c_calRootDir  = '../javaCal'; // NO trailing slash!!!
$numMonthsPast_   = -3; // see timeSlots.php
$numMonthsFuture_ = 8;  // see timeSlots.php
include "$c_calRootDir/simplecalendar.js.php";

$c_minuteInc = 5; // is minute increment for time pulldown
echo "<link rel='STYLESHEET' type='text/css' href='$c_calRootDir/styles/calendar.css'>";
echo "<script language=\"JavaScript\" type=\"text/javascript\">\nvar c_calRootDir='$c_calRootDir';\n</script>\n";
?>




<form name="resForm" method="post" action="<?php echo $_SERVER['PHP_SELF']; /* 20141218 used to be REQUEST_URI */ ?>" onSubmit="return checkForm();">

<p class="title">General Info:</p>
	<table border="0" cellpadding="0" cellspacing="0" width="670">

	<tr valign="middle">
		<td class="req">Name:</td>
		<td colspan="3"><?php echo (getVal($resInfo,"USER_NAME",0)) ? getVal($resInfo,"USER_NAME",0) : $customer['username'] ?></td>
	</tr>

	<tr valign="middle">
	 <td class="req"><?php echo writeHelp('kfs'); ?> KFS Number:</td>
	 <td class="field" colspan="3" width="380">
	  <div style="float:left; width:65px;">
	   <input type="text" name="frs" size="7" maxlength="7" onBlur="checkFrs(this.value,'<?php echo $customer['userid']; ?>');" value="<?php echo getVal($resInfo,"FRS_FK",0) ?>"/>
	  </div>
	  <div style="float:left; display:<?php echo (getVal($resInfo,"FRS_FK",0)) ? "block" : "none" ?>; width:10px; height:12px;" id="frsCheckDiv">
	   <iframe src="frscheck.php<?php if (getVal($resInfo,"FRS_FK",0)){ echo '?frs='.getVal($resInfo,"FRS_FK",0).'&cust='.$customer['userid'];} ?>" frameborder="0" width="310" height="22" scrolling="no" marginheight="0" marginwidth="0" name="frsCheckFrame"></iframe>
	  </div>
	 </td>
	</tr>

	<tr valign="middle">
		<td class="noreq">KFS Sub Acct.: </td>
		<td colspan="3" class="field" style="white-space:nowrap;">
		 <input type="text" name="KFS_SUB_ACCOUNT_FK" value="<?php echo (getVal($resInfo,"KFS_SUB_ACCOUNT_FK",0)) ? getVal($resInfo,"KFS_SUB_ACCOUNT_FK",0) : ''; ?>" size="5" maxlength="5" />
		 &nbsp;<strong>Sub Obj. Code:</strong>
		 <input type="text" name="KFS_SUB_OBJECT_CODE_FK" value="<?php echo (getVal($resInfo,"KFS_SUB_OBJECT_CODE_FK",0)) ? getVal($resInfo,"KFS_SUB_OBJECT_CODE_FK",0) : ''; ?>" size="3" maxlength="3" />
		</td>
	</tr>

	<tr valign="middle">
	<td class="req" style="white-space:nowrap;"><?php echo writeHelp('resdate'); ?> Reservation Date: </td>
	<td colspan="3">
		<table style="border:0; padding:0; margin:0;"><tr><td style="border:0; padding:0; margin:0;">
		<input type="text" readonly name="startDate" id="startDate" size="10" maxlength="10" value="<?php echo (getVal($resInfo,"RESDATE",0)) ? getVal($resInfo,"RESDATE",0) : '' ?>" onclick="document.getElementById('cal_border_1').style.backgroundColor='#f03';" /></td>
		<td style="border:0; padding:0; margin:0;" id="cal_border_1">
		<input type="image" src="<?php echo $c_calRootDir;?>/images/calendar.gif" name="imgCalendar" id="endImgCal" width="34" height="21" border="0" alt="" onmouseover="doTimeOut()" onmouseout="doTimeOutDelay()" onclick="c_autoSubmit=false; g_Calendar.show(event,this.form.name,'startDate','',false,'mm/dd/yyyy','<?php echo date('m/d/Y', (time()+(24*60*60)));?>'); return false;"></td>
		</tr>
		</table>
	</td>
	</tr>

	<tr valign="middle">
		<td class="req"><?php echo writeHelp('entertime'); ?> Enter Time:</td>
		<td class="field">
			 <input type="text" name="enterTime" value="<?php echo (getVal($resInfo,"RESSTART",0)) ? getVal($resInfo,"RESSTART",0) : $_SESSION['default_start_time']; ?>" onBlur="checkTime(this);" size="8" maxlength="8" /></td>
		<td class="req"><?php echo writeHelp('exittime'); ?> Exit Time:</td>
		<td class="field">
			 <input type="text" name="exitTime" value="<?php echo (getVal($resInfo,"RESEND",0)) ? getVal($resInfo,"RESEND",0) : $_SESSION['default_end_time']; ?>" onBlur="checkTime(this);" size="8" maxlength="8" /></td>
	</tr>

	<tr valign="middle">
		<td class="req">
		  <?php
		  echo writeHelp('garage');
		  ?>
		  Garage:
		  <?php
		  $tmpOpt = garageOptions(getVal($resInfo,"GARAGE_ID_FK",0), "9006,USA");
		  if (!$tmpOpt)
			  echo '<span style="font-weight:bold; color:#903;">Error: No Garages</span>';
		  ?>
		</td>
		<td class="field" colspan="3">
		 <select name="garage" onchange="if(this.value==9){warnBioMsg();}">
			<option value="" selected>Select a Parking Area</option>
			<?php
			// Only allow admins to use Lot 9006 and USA
			echo $tmpOpt;
			?>
		 </select>
		</td>
	</tr>
	</table><br/>


	<span id="biomed" style="padding:0; color:#C03; font-weight:normal; font-size:18px; background-color:white;"></span>

	<script type="text/javascript">
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ See also function warnBioMsg in create.php
	function warnBioMsg() {
		// document.getElementById('biomed').innerHTML = 'NOTE: Beginning September 1, 2010 reservations are not available for the
		// Phoenix BioMedical Campus Lot, until further notice. For further assistance please call Visitor Parking at (520) 621-3710.';
	}
	</script>

	<script type="text/javascript">
	document.write('		<p align="center"><input type="image" src="/images/<?php echo (strpos($_SERVER['PHP_SELF'],'edit.php')) ? 'save' : 'reserve' ?>-button.gif" width="120" height="25" alt="<?php echo (strpos($_SERVER['PHP_SELF'],'edit.php')) ? 'Save' : 'Reserve' ?>" align="absmiddle" name="reserve"/></p>');
	document.write('		<p align="center"> <?php if (strpos($_SERVER['PHP_SELF'],"edit.php") && getVal($resInfo,"RESDATE",0)!=date("m/d/Y") && ($customer['auth']>=4 || getVal($resInfo,"GROUP_SIZE",0)<=25)) { ?></p><hr width="50%" align="center" size="2"/><p align="center"><input type="image" src="/images/cancel-button.gif" onMouseOver="self.status=\'Cancel Reservation\'; return true;" onMouseOut="self.status=\'\'; return true;" width="120" height="25" alt="Cancel Reservation" align="absmiddle" name="cancelres" value="Cancel Reservation"/><?php } ?> </p>');
	</script>

	<noscript><p class="warning" align="center">Please enable JavaScript and reload this page to continue</p></noscript>
	</form>

</div>

<!-- frstest -->
<div style="display:none;"><iframe src="frscheck.php" name="frstest"></iframe></div>

