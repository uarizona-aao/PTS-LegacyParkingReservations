<?php
/***********************************  CALENDAR AND TIME   ******************************
// First insert this php code BEFORE the <form> tag, you can insert a calendar(s) anywhere within the <form> tag(s):
//    $c_showTime = true; // to show or not to show time fields
//    <script language='JavaScript'>
//    c_autoSubmit = false; // when you click a day in the calendar, should the form submit itself.
//    </script>
//    include "$c_calRootDir/dateAndTime.php";
//
// Update the root directory (where dateAndTime.php is located):
$c_calRootDir  = 'javaCal'; // NO trailing slash!!!
//
// The form field variables are as follows:
//     c_startDate (yyyy-mm-dd), c_endDate (yyyy-mm-dd),
//     c_hourStart, c_minuteStart, c_ampmStart, c_hourEnd, c_minuteEnd, c_ampmEnd

echo "<link rel='STYLESHEET' type='text/css' href='$c_calRootDir/styles/calendar.css'>";
echo "<script language='JavaScript'>\nvar c_calRootDir='$c_calRootDir';\n</script>\n";

include "$c_calRootDir/simplecalendar.js.php";

$c_minuteInc = 5;  // minute increment, for time

// ********************  Initialize time (if not submitted via POST) ****************
if (!isset($_POST['c_hourStart'])){
   $_POST['c_minuteStart'] = '00'; // MAKE sure this is a multiple of $c_minuteInc
   $_POST['c_hourStart']   = '12'; // 12 hour format HH
   $_POST['c_ampmStart']   = 'am';
}
if (!isset($_POST['c_hourEnd'])){
   $_POST['c_minuteEnd']   = '00'; // MAKE sure this is a multiple of $c_minuteInc
   $_POST['c_hourEnd']     = '09'; // 12 hour format HH
   $_POST['c_ampmEnd']     = 'pm';
}

// convert a 12-Hour format into a 24-hour var:
$c_hourStart24 = date("H", strtotime("Jan 01, 2000 ".$_POST['c_hourStart'].":00 ".$_POST['c_ampmStart']));
$c_hourEnd24   = date("H", strtotime("Jan 01, 2000 ".$_POST['c_hourEnd'].":00 ".$_POST['c_ampmEnd']));
// Create format of  yyyy-mm-dd hh:mm:ss
$c_startTime = $c_hourStart24.":".$_POST['c_minuteStart'].":00";
$c_endTime   = $c_hourEnd24.":".$_POST['c_minuteEnd'].":00";

// *****************************  Extract month, day, and year ***************************
if($_POST['c_startDate']) {
   $c_startYear  = preg_replace("/(\d\d\d\d).\d\d.\d\d/", "$1", $_POST['c_startDate']);
   $c_startMonth = preg_replace("/\d\d\d\d.(\d\d).\d\d/", "$1", $_POST['c_startDate']);
   $c_startDay   = preg_replace("/\d\d\d\d.\d\d.(\d\d)/", "$1", $_POST['c_startDate']);
}
if($_POST['c_endDate']) {
   $c_endYear  = preg_replace("/(\d\d\d\d).\d\d.\d\d/", "$1", $_POST['c_endDate']);
   $c_endMonth = preg_replace("/\d\d\d\d.(\d\d).\d\d/", "$1", $_POST['c_endDate']);
   $c_endDay   = preg_replace("/\d\d\d\d.\d\d.(\d\d)/", "$1", $_POST['c_endDate']);
}
// *****************  Initialize date components to now (if not submitted via POST) ********
if(!$_POST['c_startDate']) {
   $c_startYear  = date("Y");
   $c_startMonth = date("m");
   $c_startDay   = date("d");
}
if(!$_POST['c_endDate']) {
   $c_endYear  = date("Y");
   $c_endMonth = date("m");
   $c_endDay   = date("d");
}
/******************************************************************************************/
?>

<table border="0" cellspacing="0" cellpadding="0"><tr>
   <td valign="middle">

   <table id="startDateClass" border="0" cellspacing="0" cellpadding="0"><tr>
     <td align="right" valign="middle">
      <span class="style2blue"><big>Date:</big>&nbsp;</span></td>
     <td valign="middle">
      <font style="font-size:2px"><br></font><span class="style2blue">begin<font style="font-size:9px">&nbsp;</font></span></td>
     <td>
      <?php /************** Start date form var  **********/ ?>
      <input type="text" name="c_startDate" id="c_startDate" size="9" value="<?php echo $_POST['c_startDate'];?>" onkeydown="return false;" onchange="checkDateFormat(this.form.c_startDate.value)">
     </td>
     <td>
      <?php /**************  When "onclick" on this calendar image, g_Calendar.show is called.  And if the fourth parameter contains 'c_endDate' then when user updates c_startDate, the same value will appear in the end date.  **********/ ?>
      <input type="image" src="<?php echo $c_calRootDir;?>/images/calendar.gif" name="imgCalendar" id="startImgCal" width="34" height="21" border="0" alt="" onmouseover="doTimeOut()" onmouseout="doTimeOutDelay()" onclick="g_Calendar.show(event,this.form.name,'c_startDate','c_endDate',false,'yyyy-mm-dd');  return false;">
     </td>
   </tr></table>

   </td>
   <td valign="middle">

   <table id="endDateClass" border="0" cellspacing="0" cellpadding="0"><tr>
     <td valign="middle">
      <font style="font-size:2px"><br></font><span class="style2blue">&nbsp;&nbsp;end<font style="font-size:9px">&nbsp;</font></span></td>
     <td>
      <input type="text" name="c_endDate" id="c_endDate" size="9" value="<?php echo $_POST['c_endDate'];?>" onkeydown="return false;" onchange="checkDateFormat(this.form.c_endDate.value)">
     </td>
     <td>
      <input type="image" src="<?php echo $c_calRootDir;?>/images/calendar.gif" name="imgCalendar" id="endImgCal" width="34" height="21" border="0" alt="" onmouseover="doTimeOut()"  onmouseout="doTimeOutDelay()" onclick="g_Calendar.show(event,this.form.name,'c_endDate','',false,'yyyy-mm-dd');  return false;">
     </td>
   </tr></table>

   </td>
 </tr>
</table>


<?php if($c_showTime) { ?>
  <font style="font-size:9px"><br></font>
  <table border="0" cellspacing="0" cellpadding="0"><tr>
   <td align="right" valign="middle">
   <span class="style2blue"><big>Time:</big>&nbsp;</span>
   </td>

   <td><span class="style2blue">start<font style="font-size:9px">&nbsp;</font></span></td>
   <td><select name="c_hourStart" class="smTextArea">
   <?php
   $c_hourStart = $_POST['c_hourStart'];
   $c_hourselStart['$c_hourStart'] = " selected";
   for ($i=1; $i<13; $i++) {
      $c_tmpHour = preg_replace("/^\d$/", "0$i", $i);  // prepend a zero
      echo "      <option value=\"$c_tmpHour\"".$c_hourselStart['$c_tmpHour'].">$i</option>\n";
   }
   ?>
   </select>
   </td>
   <td valign="middle"><font style="font-size: 16px;">:</font></td>
   <td><select name="c_minuteStart" class="smTextArea">
     <?php
   $c_minuteStart = $_POST['c_minuteStart'];
   $c_minselStart['$c_minuteStart'] = " selected";
   for ($i=0; $i<60; $i+=$c_minuteInc) {
      $c_tmpminute = preg_replace("/^\d$/", "0$i", $i);  // prepend a zero
      echo "      <option value=\"$c_tmpminute\"".$c_minselStart['$c_tmpminute'].">$c_tmpminute</option>\n";
   }
   ?>
   </select>
   </td>
   <td><select name="c_ampmStart" class="smTextArea">
     <option value="am">am</option>
     <option value="pm"<?php if($_POST['c_ampmStart']=="pm") echo " selected";?>>pm</option>
   </select>
   </td>

   <td valign="middle"><font style="font-size:2px"><br></font><span class="style2blue">&nbsp;&nbsp;finish<font style="font-size:9px">&nbsp;</font></span></td>
   <td><select name="c_hourEnd" class="smTextArea">
   <?php
   $c_hourEnd = $_POST['c_hourEnd'];
   $c_hourselEnd['$c_hourEnd'] = " selected";
   for ($i=1; $i<13; $i++) {
      $c_tmpHour = preg_replace("/^\d$/", "0$i", $i);  // prepend a zero
      echo "      <option value=\"$c_tmpHour\"".$c_hourselEnd['$c_tmpHour'].">$i</option>\n";
   }
   ?>
   </select>
   </td>
   <td valign="middle"><font style="font-size: 16px;">:</font></td>
   <td><select name="c_minuteEnd" class="smTextArea">
   <?php
   $c_minuteEnd = $_POST['c_minuteEnd'];
   $c_minselEnd['$c_minuteEnd'] = " selected";
   for ($i=0; $i<60; $i+=$c_minuteInc) {
      $c_tmpminute = preg_replace("/^\d$/", "0$i", $i);  // prepend a zero
      echo "      <option value=\"$c_tmpminute\"".$c_minselEnd['$c_tmpminute'].">$c_tmpminute</option>\n";
   }
   ?>
   </select>
   </td>
   <td>
   <select name="c_ampmEnd" class="smTextArea">
     <option value="am">am</option>
     <option value="pm"<?php if($_POST['c_ampmEnd']=="pm") echo " selected";?>>pm</option>
   </select>
   </td>
  </tr>
</table>
<?php } ?>



<script language="JavaScript">
<!--
function checkDateFormat(dateStr) {
  // Returns false if date is not in format YYYY-MM-DD
  var dateformat = /^\d\d\d\d-(0[0-9]|1[0-2])-(0[0-9]|1[0-9]|2[0-9]|3[0-1])$/;
  if(dateformat.exec(dateStr)==null) {
    alert("Date format not correct, must be in format YYYY-MM-DD.  Use the calendar icon.");
    return false;
  }else{
     return true;
  }
}
function checkTimeFormat(timeStr) {
  // Returns false if time is not in format HH:MM:SS
  var timeformat = /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/;
  if(timeformat.exec(timeStr)==null) {
    alert("Time format not correct, must be in 24-hour format HH:MM:SS");
    return false;
  }else{
     return true;
  }
}
//-->
</script>
