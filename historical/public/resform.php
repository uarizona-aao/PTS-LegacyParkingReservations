
<?php
/*************
<link rel="stylesheet" type="text/css" href="/js/mootools/jquery-ui_1.8.17.css" />
<script src="/js/mootools/jquery-ui_1.8.17.js"></script>
<script src="/js/mootools/timepicker/jquery.timepickr.js"></script>
**************/
?>

<script type="text/javascript" src="/js/jquery-timepicker-master/jquery.timepicker.js"></script>
<link rel="stylesheet" type="text/css" href="/js/jquery-timepicker-master/jquery.timepicker.css" />


<span id="biomed" style="padding:0; color:#C03; font-weight:normal; font-size:18px; background-color:white;"></span>

<!-- loads mdp -->
<script type="text/javascript" src="/js/jquery-datepicker/js/jquery-ui-1.11.1.js"></script>
<script type="text/javascript" src="/js/jquery-datepicker/jquery-ui.multidatespicker.js"></script>
<link rel="stylesheet" type="text/css" href="/js/jquery-datepicker/css/mdp-new.css">


<script language="JavaScript" type="text/javascript" src="/js/forms.js"></script>
<script language="JavaScript" type="text/javascript" src="/js/base.js"></script>
<script language="JavaScript" type="text/javascript">

function checkFormRes () {

	var dateRe = /[0-9]{2}\/[0-9]{2}\/20[0-9]{2}/;
	var timeRe = /[0-9]{1,2}\:[0-9]{2}\s[AM|am|PM|pm]{2}/;
	var numRe = /[0-9]+/;
	var dateReturn = "";
	var alerts = "";
	var daDates = new Array();
	var dates_str = $('#multiDateBox').multiDatesPicker('getDates');

	with (document.resForm) {

		if ((window.RegExp && !frsRe.test(frs.value)))
			alerts += "Please enter a valid KFS number\n";
		else if (frames['frsCheckFrame'].document.images.validity.src.indexOf("/invalid.gif")>0)
			alerts += "You do not have access to this KFS number\n";

		dates.value = dates_str;
		if (!dates.value)
			alerts += "Please select at least one Reservation Date\n";



		// Trim spaces, and add, if nessassary
		//		var enterTimeStr = String(enterTime.value);
		//		enterTimeStr = enterTimeStr.replace(/\s+$/i, ""); // trim space
		//		enterTimeStr = enterTimeStr.replace(/^\s+/i, ""); // trim space
		//		enterTimeStr = enterTimeStr.replace(/(\d)([AP]M)$/i, "$1 $2"); // inset a space
		//		enterTime.value = enterTimeStr;
		//		var exitTimeStr = String(exitTime.value);
		//		exitTimeStr = exitTimeStr.replace(/\s+$/i, ""); // trim space
		//		exitTimeStr = exitTimeStr.replace(/^\s+/i, ""); // trim space
		//		exitTimeStr = exitTimeStr.replace(/(\d)([AP]M)$/i, "$1 $2"); // inset a space
		//		exitTimeStr = exitTimeStr.replace(/12:00 AM/i, "11:59 PM"); // inset a space
		//		exitTime.value = exitTimeStr;


		// Make sure start time is not before end time.
		var dtStart	= new Date("1/1/2007 " + enterTime.value);
		var dtEnd	= new Date("1/1/2007 " + exitTime.value);
		diff_in_millisec_a = dtEnd - dtStart;
		if (diff_in_millisec_a < 0)
			alerts += "End time is before Start time!\n";
		if (!enterTime.value || !exitTime.value)
			alerts += "Please enter 'Enter Time' and 'Exit Time'\n";


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
$redDates = ''; // single date, or comma-seperated dates.
$unselectDateMsg = '';
$maxDatePicks = "";
if (strpos($_SERVER['PHP_SELF'],'create.php'))
{
	$redDates = $_POST['dates'] ? $_POST['dates'] : '';
	echo 'Make a Reservation';
}
elseif (strpos($_SERVER['PHP_SELF'],'edit.php'))
{
	$redDates = getVal($resInfo,"RESDATE",0) ? getVal($resInfo,"RESDATE",0) : '';
	$maxDatePicks = "maxPicks: 1,\n";
	$unselectDateMsg = "To change the date, <br/>first remove the active <br/>date by clicking on it.";
	echo 'Edit Reservation '.getVal($resInfo,'RESERVATION_ID',0);
	$inEdit = true;
}

//elseif (strpos($_SERVER['PHP_SELF'],'duplicate.php')) {
//	$redDates = getVal($resInfo,"RESDATE",0) ? getVal($resInfo,"RESDATE",0) : '';
//	$maxDatePicks = "maxPicks: 1,\n";
//	$unselectDateMsg = "To change the date, <br/>first remove the active <br/>date by clicking on it.";
//	echo 'Duplicate Reservation '.getVal($resInfo,'RESERVATION_ID',0);	}

echo "</h2>\n";

echo $errMsg;

?>

<div class="resBox">

<?php
$get_p = '?';
if (isset($_GET['id']) && ctype_digit($_GET['id']))
	$get_p .= 'id='.$_GET['id'];
if (isset($_GET['res']) && ctype_digit($_GET['res']))
	$get_p .= '&res='.$_GET['res'];
?>

<?php
$res_id = ctype_alnum(@$_GET['id']) ? $_GET['id'] : '';
?>
<form name="resForm" method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>?id=<?php echo $res_id;?>" onSubmit="return checkFormRes();">

<p class="title">General Info:</p>
	<table border="0" cellpadding="0" cellspacing="0" width="670">

	<tr valign="middle">
		<td class="req">Name:</td>
		<td colspan="3" style=""><?php echo (getVal($resInfo,"USER_NAME",0)) ? getVal($resInfo,"USER_NAME",0) : $customer['username'] ?></td>
	</tr>

	<tr valign="middle">
	 <td class="req"><?php echo writeHelp('kfs'); ?> KFS Number:</td>
	 <td class="field" colspan="3" width="380">
	  <div style="float:left; width:65px;">
	   <input type="text" name="frs" size="10" maxlength="7" onBlur="checkFrs(this.value,'<?php echo $customer['userid']; ?>');" value="<?php echo getVal($resInfo,"FRS_FK",0) ?>"/>
	  </div>
	  <div style="float:left; display:<?php echo (getVal($resInfo,"FRS_FK",0)) ? "block" : "none" ?>; width:10px; height:12px;" id="frsCheckDiv">
	   <iframe name="frsCheckFrame" src="frscheck.php<?php if (getVal($resInfo,"FRS_FK",0)){ echo '?frs='.getVal($resInfo,"FRS_FK",0).'&cust='.$customer['userid'];} ?>"
			style="width:350px; height:250px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>
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

	<?php

	if (isset($resInfo['guestList'])) {
		$resInfo['GUEST_NAME'] = $resInfo['guestList'];
		$resInfo['GROUP_SIZE'] = (isset($resInfo['groupCount'])) ? $resInfo['groupCount'] : '';
	}

	// These vars $rdStr1 and $rdStr2 are only used in this file.
	// $res->error!='groupSize' just in case poor customer submitted a NEW reservation and so is coming back to make changes.
	if ($customer['auth']<4 && getVal($resInfo,"GROUP_SIZE",0)>25 && $res->error!='groupSize') {
		$rdStr1 = 'alert(\'Since you have over 25 spaces, you cannot make changes, please contact PTS Visitor Programs at (520) 621-3710\'); return false; ';
		$rdStr2 = 'onclick="alert(\'Since you have over 25 spaces, you cannot make changes, please contact PTS Visitor Programs at (520) 621-3710\');" readonly ';
	} else {
		$rdStr1 = '';
		$rdStr2 = '';
	}


	if ($_SERVER['PHP_SELF'] != '/parking/garage-reservation/multichange.php')
	{
		?>
		<tr valign="middle">
		 <td class="req" style="white-space:nowrap;"><?php echo writeHelp('resdate'); ?>
			 Reservation Date(s):
			 <div style="color:orangered; padding-left: 20px;"><?php echo $unselectDateMsg;?></div>
		 </td>
		 <td colspan="3">

	<?php
	$addDatesStr = $defaultDateStr = '';
	if (@$redDates) //  && getVal($resInfo,"RESDATE",0)
	{
		$d_list = explode(',', $redDates);
		if ($d_list[0]) // Set default date string to first date found.
			$defaultDateStr = "defaultDate: '".$d_list[0]."',\n";
		foreach ($d_list as $item)
		{
			$addDatesStr .= $addDatesStr ? ',' : '';
			$addDatesStr .= "'$item'";
		}
	}
	$addDatesStrFull = $addDatesStr ? "addDates: [".$addDatesStr."],\n" : '';
	?>

<div id="multiDateBox" class="box"></div>
<input type="hidden" name="dates" value="<?php echo @$redDates;?>" />

<?php /*** $singleDayMode = "onSelect: function (chosenDate) {	$('#multiDateBox').multiDatesPicker('resetDates'); $('#multiDateBox').multiDatesPicker('addDates', chosenDate); },\n"; ***/?>
<script>
var date_mdp = new Date();
$('#multiDateBox').multiDatesPicker({
	
	minDate: 1, /* 0 is today, 1 is tomorrow, .... */
	<?php echo $addDatesStrFull . $defaultDateStr . $maxDatePicks;?>
});
</script>


			 <?php /******************************
		  <input type='text' name='startDate' id='startDate' onclick="<?php echo $rdStr1;?> "
				style='background: #fff url(/js/mootools/datepicker/Source/date.gif) no-repeat top left; padding-left:21px;'
				size='10' value="<?php echo (getVal($resInfo,"RESDATE",0)) ? getVal($resInfo,"RESDATE",0) : '';?>" />
			  *****************************/ ?>
		 </td>
		</tr>

		<?php

	}
	?>
	<tr valign="middle">
		<td class="req"><?php echo writeHelp('entertime'); ?> Enter Time:</td>
		<td class="field">
			<?php $dflt_RESSTART = getVal($resInfo,"RESSTART",0) ? preg_replace('/\s/si', '', getVal($resInfo,"RESSTART",0)) : ''; ?>
			<input id="enterTime" name="enterTime" type="text" value="<?php echo $dflt_RESSTART;?>" maxlength="8" class="time" <?php echo $rdStr2;?> />
			<script>
			// RESSTART = enterTime
			$(function() {
				 $('#enterTime').timepicker({
					 'timeFormat': 'h:i A',
					 'step': 15,
					 'minTime': '5:00am',
					 'maxTime': '11:30pm',
					 'scrollDefault': '<?php echo $dflt_RESSTART;?>',
				 });
			});
			</script>

		</td>

		<td class="req"><?php echo writeHelp('exittime'); ?> Exit Time:</td>
		<td class="field">
			<?php $dflt_RESEND = getVal($resInfo,"RESEND",0) ? preg_replace('/\s/si', '', getVal($resInfo,"RESEND",0)) : ''; ?>
			<input id="exitTime" name="exitTime" type="text" value="<?php echo $dflt_RESEND;?>" maxlength="8" class="time" <?php echo $rdStr2;?> />
			<script>
			// RESEND = exitTime
			$(function() {
				 $('#exitTime').timepicker({
					 'timeFormat': 'h:i A',
					 'step': 15,
					 'minTime': '5:15am',
					 'maxTime': '11:59pm',
					 'scrollDefault': '<?php echo $dflt_RESEND;?>',
				 });
			});
			</script>

		</td>
	</tr>

	<tr valign="middle">
		<td class="req">
		  <?php
		  echo writeHelp('garage');
		  ?>
		  Garage:
		  <?php
		  // Don't allow these garages to appear in dropdown for customer: 9006 Lot, USA Lot, Phoenix Biomedical 10003
		  $tmpOpt = garageOptions(getVal($resInfo,"GARAGE_ID_FK",0), "9006,USA,10003");
		  if (!$tmpOpt)
			  echo '<span style="font-weight:bold; color:#903;">Error: No Garages</span>';
		  ?>
		</td>
		<td class="field" colspan="3">
		 <select name="garage" id="garageSelectControl"  <?php echo $rdStr2;?>>
			<option value="" selected>Select a Parking Area</option>
			<?php
			// Only allow admins to use Lot 9006 and USA
			echo $tmpOpt;
			?>
		 </select>
		</td>
	</tr>
	</table><br/>





<script type="text/javascript">
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ See also function warnBioMsg in create.php
function warnBioMsg() {
	// document.getElementById('biomed').innerHTML = 'NOTE: Beginning September 1, 2010 reservations are not available for the
	// Phoenix BioMedical Campus Lot, until further notice. For further assistance please call Visitor Parking at (520) 621-3710.';
}
</script>


<p class="title">Group/Guests:</p>
	<div style="font-size:14px;">
		<table border="0" cellpadding="0" cellspacing="0" bgcolor="#EEEEEE" style="border:solid 2px #003366;">
		<tr valign="middle">
			<td style="background-color:#003366; color:#FFFFFF; padding-right:5px;"><input type="radio" name="groupGuest" value="guest"<?php if ($glg=="guest") echo ' checked'; ?> onClick="guestGroup('guest');"/> <img src="/images/icons/guest.gif" width="45" height="47" alt="Guest" align="absmiddle"/> <b>Guest List</b></td>
			<td style="padding:0 10px; font-size:11px;"><i>(Enter a list of people to allow in)</i></td>
			<td style="background-color:#F1F1F1;">&nbsp;</td>
			<td style="color:#FFFFFF; background-color:#003366; padding-right:5px;"><input type="radio" name="groupGuest" value="group"<?php if ($glg=="group") echo ' checked'; ?> style="color:#003366;" onClick="guestGroup('group');"/> <img src="/images/icons/group.gif" width="45" height="47" alt="Group" align="absmiddle"/> <b>Group</b></td>
			<td style="padding:10px; font-size:11px;"><i>(I do not know the guests' names<br/>and/or would not like to enter them)</i></td>
		</tr>
		</table>
	</div>

	<div id="group" style="<?php echo ($glg=="group") ? 'display:block; clear:left;' : 'display:none;' ?>">

	<table border="0" cellpadding="0" cellspacing="0">
	 <tr>
	  <td colspan="6" class="title">Group</td>
	 </tr>
	 <tr valign="middle">
		<td class="req" colspan="2"><?php echo writeHelp('group'); ?> Group Name:</td>
		<td class="field" colspan="4"><input type="text" name="groupName" size="30" maxlength="30" value="<?php if ($glg=="group") echo getVal($resInfo,"GUEST_NAME",0); ?>" onkeyup="isAlphaNumeric('groupName');" /></td>
	 </tr>
	 <tr valign="middle">
		<td class="req" colspan="2"><?php echo writeHelp('spaces'); ?> Spaces:</td>
		<td class="field" colspan="2">
			<input type="text" name="spaces" size="3" maxlength="4" value="<?php if ($glg=="group") echo getVal($resInfo,"GROUP_SIZE",0); ?>" onkeyup="isNumeric('spaces');" <?php echo $rdStr2;?> />
			<?php if ($inEdit) { ?>
				<input type="hidden" name="spacesOrig" value="<?php if ($glg=="group") echo getVal($resInfo,"GROUP_SIZE",0); ?>" />
			<?php } ?>
		</td>

		<?php
		echo '<td colspan="2">&nbsp;</td>';
		?>
	 </tr>

	</table>

	</div>
	<div id="guest" style="<?php echo ($glg=="guest") ? 'display:block; clear:left;' : 'display:none;' ?>">
	<table border="0" cellpadding="0" cellspacing="0">
	<tr valign="middle">
		<td class="title" colspan="2">Add a Guest:</td>
		<td class="title" colspan="2">Guest List:</td>
	</tr>
	<tr valign="middle">
		<td class="req"><?php echo writeHelp('guestname'); ?> Guest Name:</td>
		<td class="field">
			<input type="text" name="guestName" maxlength="25" onkeyup="isAlphaNumeric('guestName');"/>
		</td>
		<td class="req" rowspan="2"><?php echo writeHelp('guestlist'); ?> Guests:</td>
		<td class="field" align="center" rowspan="2">
			<select name="guestListBox" multiple>
			<?php
			if (isset($resInfo['guestList']))
			{
				$list = ($resInfo['guestList']) ? explode(" | ",$resInfo['guestList']) : array();
				$guestList = array();

				foreach ($list as $item)
				{
					if (!isset($guestList[$item]) && $item)
						echo "			<option value=\"$item\">$item</option>\n";
					$guestList[$item] = true;
				}
			}
			if (!isset($resInfo['guestList']) || (isset($list) && !count($list)))
				echo '			<option value="">Please Add Guests to Continue</option>';
			?>
			</select>
			<input type="hidden" name="guestList" value="" />
			<br/>
			<input type="button" value="Remove Selected Guest" class="submitter" onClick="removeGuest(this.form);"/>
		</td>
	</tr>
	<tr valign="middle">
		<td colspan="2" valign="top" align="center" style="padding: 0 0 28px 42px;">
			<!---  function addGuest in /js/forms.js --->
			<input type="button" value="Add Guest" class="submitter" onClick="if(checkGuest()){addGuest('this.form.guestName.value',this.form);}"/>
			<script language="JavaScript">
			function checkGuest() {
			  var nameformat = /\w+[,\.\']? +[,\.\']?\w+/;
			  if(nameformat.exec(document.resForm.guestName.value)==null) {
			    alert("Please enter full name.");
			    return false;
			  } else {
			  	 return true;
			  }
			}

			// check to see if input is alphanumeric
			function isAlphaNumeric(fieldName) {
				var fld = eval('document.resForm.' + fieldName);
				if (fld.value && !fld.value.match(/^[a-zA-Z0-9 ]+$/)) {
					alert('Names must only contain letters, digits, or blank spaces.');
					fld.value = fld.value.replace(/[^a-zA-Z0-9 ]/g, '');
				}
			}
			function isNumeric(fieldName) {
				var fld = eval('document.resForm.' + fieldName);
				if (fld.value && !fld.value.match(/^[0-9]+$/)) {
					alert('Only digits please.');
					fld.value = fld.value.replace(/[^0-9]/g, '');
				}
			}
			</script>
		</td>
	</tr>

	</table>
	</div>

	<script language="JavaScript" type="text/javascript">
	// Lame dynamic  /images/reserve-button.gif  and  /images/save-button.gif
	document.write('<p align="center"><input type="image" src="/images/<?php echo (strpos($_SERVER['PHP_SELF'],'edit.php')) ? 'save' : 'reserve' ?>-button.gif" width="120" height="25" alt="<?php echo (strpos($_SERVER['PHP_SELF'],'edit.php')) ? 'Save' : 'Reserve' ?>" align="absmiddle" name="reserve"/></p>');
	document.write('<p align="center"> <?php if (strpos($_SERVER['PHP_SELF'],"edit.php") && getVal($resInfo,"RESDATE",0)!=date("m/d/Y") && ($customer['auth']>=4 || getVal($resInfo,"GROUP_SIZE",0)<=25)) { ?></p><hr width="50%" align="center" size="2"/><p align="center"><input type="image" src="/images/cancel-button.gif" onMouseOver="self.status=\'Cancel Reservation\'; return true;" onMouseOut="self.status=\'\'; return true;" width="120" height="25" alt="Cancel Reservation" align="absmiddle" name="cancelres" value="Cancel Reservation"/><?php } ?> </p>');
	</script>
	<noscript><p class="warning" align="center">Please enable JavaScript and reload this page to continue</p></noscript>
	</form>

</div>

<!-- frstest -->
<div style="display:none;"><iframe src="frscheck.php" name="frstest"></iframe></div>

