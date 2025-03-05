<script language='JavaScript'>

<?php

/************* see dateAndTime.php **************************/

/***********************************************************************************************

Calendar.prototype.show = function(event, formName, theDateA, theDateB, bHasDropDown, dateFormat, dateFrom, dateTo)
	*NOTE: When "onclick" on this calendar image g_Calendar.show is called, and if the fourth parameter contains an input text field, then when user updates startDate, the same value will appear in the end date.  See simplecalendar.js.php

Example of a calendar that only allows days to be selected tomorrow (note dateFrom field):
	<input type="text" readonly name="startDate" id="startDate" onFocus="resetDate(this);" onBlur="checkDate(this);" size="12" maxlength="10" value="<?= (getVal($resInfo,"RESDATE",0)) ? getVal($resInfo,"RESDATE",0) : '' ?>" />
	<input type="image" src="<?php echo $c_calRootDir;?>/images/calendar.gif" name="imgCalendar" id="endImgCal" width="34" height="21" border="0" alt="" onmouseover="doTimeOut()" onmouseout="doTimeOutDelay()" onclick="c_autoSubmit=false; g_Calendar.show(event,this.form.name,'startDate','',false,'mm/dd/yyyy','<?php echo date('m/d/Y', (time()+(24*60*60)));?>'); return false;">

Example of select box, for multiple dates:
	<select name="multiDateBox" multiple>
	  <option value="">Enter a Date</option>
	</select>
	<input type="image" src="<?php echo $c_calRootDir;?>/images/calendar.gif" name="imgCalendar" id="endImgCal" width="34" height="21" border="0" alt="" onmouseover="doTimeOut()" onmouseout="doTimeOutDelay()" onclick="c_autoSubmit=false; g_Calendar.show(event,this.form.name,'multiDateBox','',false,'mm/dd/yyyy'); return false;">
	<a href="#" onClick="g_Calendar.removeDate(document.resForm.multiDateBox);" style="padding-left:3px; padding-right:3px; margin:0; border:1px solid black; text-decoration:none; background-color:#DDDDDD;" /><span style="font-weight:bold;">X</span> Remove Selected Date</a>

********************************************************************************************/
?>

<?php
$showCalErrors = false;
if(!isset($calHeight))
	$calHeight = 120;
?>

var timeoutDelay = 300; // milliseconds, change this if you like, set to 0 for the calendar to never auto disappear
var timeoutId    = false; // used by timeout auto hide functions
var c_autoSubmit = false; // when you click a day in the calendar, should the form submit itself.

var monthDist  = 0; // distance the selected month is away from the origional month.  Used to decide wether or not to stick 'pending' and 'approved' at the bottom of the calendar.
var g_startDay = 0// 0=sunday, 1=monday
// preload images
var imgUp = new Image(8,12);
imgUp.src = '<?php echo $c_calRootDir;?>/images/up.gif';
var imgDown = new Image(8,12);
imgDown.src = '<?php echo $c_calRootDir;?>/images/down.gif';


function doTimeOut() {
   if(window.timeoutId)
      if(timeoutId)
         clearTimeout(timeoutId);
   window.status='Show Calendar';
   return true;
}

function doTimeOutDelay() {
   if(window.timeoutDelay)
      if(timeoutDelay)
         calendarTimeout();
   window.status='';
}


// dom browsers require this written to the HEAD section

// the now standard browser sniffer class
function Browser() {
	this.dom = document.getElementById?1:0;
	this.ie4 = (document.all && !this.dom)?1:0;
	this.ns4 = (document.layers && !this.dom)?1:0;
	this.ns6 = (this.dom && !document.all)?1:0;
	this.ie5plus = (this.dom && document.all)?1:0;
	this.ie9 = (document.addEventListener)?1:0; // ie5plus will also be true
	this.ok = this.dom || this.ie4 || this.ns4;
	this.platform = navigator.platform;
}
var browser = new Browser();

<?php
if ($_SERVER['REMOTE_ADDR']=='128.196.6.35') {
	?>
	//if (browser.ie5plus)	alert('ie5plus');
	//if (browser.ie9)	alert('ie9');
	<?php
}
?>


if (browser.dom || browser.ie4 || browser.ie5plus) {
	document.writeln('<style>');
	document.writeln('#container {');
	document.writeln('position : absolute;');
	if (browser.ie9){
		document.writeln('left : 200px;');
		document.writeln('top : 200px;');
	}else{
		document.writeln('left : 100px;');
		document.writeln('top : 100px;');
	}
	document.writeln('width : 124px;');
	browser.platform=='Win32'?height=<?php echo $calHeight;?>:height=<?php echo ($calHeight+5);?>;
	document.writeln('height : ' + height +'px;');
	document.writeln('clip:rect(0px 124px ' + height + 'px 0px);');
	//document.writeln('overflow : hidden;');
	document.writeln('visibility : hidden;');
	document.writeln('background-color : #ffffff');
	document.writeln('}');
	document.writeln('</style>')
	document.write('<div id="container"');
	if (timeoutDelay)
		document.write(' onmouseout="calendarTimeout();" onmouseover="if(timeoutId) clearTimeout(timeoutId);"');
	document.write('></div>');
}

var g_Calendar;  // global to hold the calendar reference, set by constructor

function calendarTimeout() {
	// This is for when the mouse moves out of the calendar.
   if (browser.ie4 || browser.ie5plus)
      if (window.event.srcElement && window.event.srcElement.name!='month')
         timeoutId = setTimeout('g_Calendar.hide();',timeoutDelay);
   if (browser.ns6 || browser.ns4)
      timeoutId = setTimeout('g_Calendar.hide();',timeoutDelay);
}

// constructor for calendar class
function Calendar(){
  g_Calendar = this;
  // some constants needed throughout the program
  this.daysOfWeek = new Array("Su","Mo","Tu","We","Th","Fr","Sa");
  this.months = new Array("January","February","March","April","May","June","July","August","September","October","November","December");
  this.daysInMonth = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

  if (browser.ns4) {
     var tmpLayer = new Layer(127);
	  if (timeoutDelay){
		  tmpLayer.captureEvents(Event.MOUSEOVER | Event.MOUSEOUT);
		  tmpLayer.onmouseover = function(event) {
		     if(timeoutId)
		       clearTimeout(timeoutId);
		  };
		  tmpLayer.onmouseout = function(event) {
		     timeoutId = setTimeout('g_Calendar.hide()',timeoutDelay);
		  };
	  }
     tmpLayer.x = 100;
     tmpLayer.y = 100;
     tmpLayer.bgColor = "#ffffff";
  }
  if (browser.dom || browser.ie4){
	  var tmpLayer = browser.dom ? document.getElementById('container') : document.all.container;
  }
  this.containerLayer = tmpLayer;
  if (browser.ns4 && browser.platform=='Win32') {
     this.containerLayer.clip.height=134;
     this.containerLayer.clip.width=127;
  }
}


Calendar.prototype.getFirstDOM = function() {
	var thedate = new Date();
	thedate.setDate(1);
	thedate.setMonth(this.month);
	thedate.setFullYear(this.year);
	return thedate.getDay();
}


Calendar.prototype.getDaysInMonth = function (){
   if (this.month!=1) {
     return this.daysInMonth[this.month]
   }
   else {
     // is it a leap year
	    if (Date.isLeapYear(this.year)) {
		  return 29;
		}
	    else {
		  return 28;
		}
   }
}



Calendar.prototype.changeHighlight = function(currCell) {
	// Fill in the multi-select box with user-selected date.  And change height of select box.

	var dayStr = currCell.substr(4);
	var dateStr = this.formatDateAsString(dayStr,this.month,this.year);

	var wholeName = eval('document.' + this.formName + '.' + this.theDateA1);

	if (wholeName.options[0].value == "") {
		wholeName.options[0].value = dateStr;
		wholeName.options[0].text = dateStr;
	} else {
		wholeName.options[wholeName.options.length] = new Option(dateStr,dateStr);
	}
}


Calendar.prototype.removeDate = function (box) {
	// Remove a date from the multi-select box - which dates were created via changeHighlight.
	sele = box.selectedIndex;
	if (box.options[sele].value=="") return false;
	else if (sele==-1) alert("Please select a date to remove");
	else box.options[sele] = null;
	if (!box.options.length) box.options[0] = new Option("Enter a Date","");
}



Calendar.prototype.buildString = function() {
  var tmpStr = '<form onSubmit="this.year.blur();return false;"><table style="border:0; padding:2px; margin:0;" width="100%" border="0" cellspacing="0" class="calBorderColor"><tr><td valign="top" style="border:0; padding:0; margin:0;"><table style="border:0; padding:1px; margin:0;" width="100%" border="0" cellspacing="0" class="calBgColor">';
  tmpStr += '<tr>';
  tmpStr += '<td width="70%" class="cal" align="left" style="border:0; padding:0; margin:0;">';
  if (this.hasDropDown) {
    tmpStr += '<select class="month" name="month" onchange="g_Calendar.selectChange();">';
	 for (var i=0;i<this.months.length;i++){
      tmpStr += '<option value="' + i + '"'
	   if (i == this.month) tmpStr += ' selected';
	   tmpStr += '>' + this.months[i] + '</option>';
    }
    tmpStr += '</select>';
  } else {
    // make the month selector
    tmpStr += '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#D4D4D4; border:0; padding:0; margin:0;"><tr><td width="1" style="border:0; padding:0; margin:0;"><a href="javascript: g_Calendar.changeMonth(-1);"><img name="calendar" src="<?php echo $c_calRootDir;?>/images/down.gif" width="8" height="12" border="0" alt=""></a></td><td class="cal" align="center" style="border:0; padding:0; margin:0;">' + this.months[this.month] + '</td><td class="cal" width="1" style="border:0; padding:0; margin:0;"><a href="javascript: g_Calendar.changeMonth(+1);"><img name="calendar" src="<?php echo $c_calRootDir;?>/images/up.gif" width="8" height="12" border="0" alt=""></a></td><td width="1" style="border:0; padding:0; margin:0;">&nbsp;&nbsp;</td></tr></table>';
  }
  tmpStr += '</td>';
  /* observation : for some reason if the below event is changed to 'onChange' rather than 'onBlur' it totally crashes IE (4 and 5)! */
  tmpStr += '<td width="30%" align="right" class="cal" style="border:0; padding:0; margin:0;">';
  if (this.hasDropDown) {
    tmpStr += '<input class="year" type="text" size="';
    // get round NS4 win32 lenght of year input problem
    (browser.ns4 && browser.platform=='Win32')?tmpStr += 1:tmpStr += 4;
    tmpStr += '" name="year" maxlength="4" onBlur="g_Calendar.inputChange();" value="' + this.year + '">';
  } else {
     // make the year
     tmpStr += '<table width="1" border="0" cellspacing="0" cellpadding="0" style="border:0; padding:0; margin:0;"><tr><td style="border:0; padding:0; margin:0;" class="cal" width="1%"><a href="javascript: g_Calendar.changeYear(-1);"><img name="calendar" src="<?php echo $c_calRootDir;?>/images/down.gif" width="8" height="12" border="0" alt=""></a></td><td style="border:0; padding:0; margin:0;" class="cal" width="1%" align="center">' + this.year + '</td><td style="border:0; padding:0; margin:0;" class="cal" width="1%"><a href="javascript: g_Calendar.changeYear(+1);"><img name="calendar" src="<?php echo $c_calRootDir;?>/images/up.gif" width="8" height="12" border="0" alt=""></a></td></tr></table>'
  }
  tmpStr += '</td>';
  tmpStr += '</tr>';
  tmpStr += '</table>';
  var iCount = 1;

  var iFirstDOM = (7+this.getFirstDOM()-g_startDay)%7; // to prevent calling it in a loop

  var iDaysInMonth = this.getDaysInMonth(); // to prevent calling it in a loop

  tmpStr += '<table style="border:0; padding:1px; margin:0;" width="100%" border="0" cellspacing="0" class="calBgColor">';
  tmpStr += '<tr>';
    for (var i=0;i<7;i++){
	  tmpStr += '<td style="border:0; padding:0; margin:0;" align="center" class="calDaysColor">' + this.daysOfWeek[(g_startDay+i)%7] + '</td>';
	}
  tmpStr += '</tr>';
  var tmpFrom = parseInt('' + this.dateFromYear + this.dateFromMonth + this.dateFromDay,10);
  var tmpTo   = parseInt('' + this.dateToYear + this.dateToMonth + this.dateToDay,10);
  var tmpCompare;

  // create the 6 by 7 calendar table, with all the day numbers
  var tmpStr0 = '';
  var isToday = 0;

  for (var j=1;j<=6;j++) {
     tmpStr += '<tr>';
     for (var i=1;i<=7;i++){
	   tmpStr += '<td style="border:0; padding:0; margin:0;" width="16" align="center" '
	   if ( (7*(j-1) + i)>=iFirstDOM+1  && iCount <= iDaysInMonth) {
	     if (iCount==this.day && this.year==this.oYear && this.month==this.oMonth) {
	        // calHighlightColor is the color of current day selected.
	        isToday = 1;
	        tmpStr0 = 'class="calHighlightColor"';
	     } else {
	        isToday = 0;
		     if (i==7-g_startDay || i==((7-g_startDay)%7)+1)
		        tmpStr0 = 'class="calWeekend"';
		     else
			     tmpStr0 = 'class="cal"';
           if (c_autoSubmit) { // if c_autoSubmit=true, then this means we are working with the Select Date calendar.
      		  <?php
      		  // When a customer has a time reserved for a particular day, he (and the admin) will be able to see the java calendar with that day's bg color highlighted.
				  if (!isset($c_resReserv))
					  $c_resReserv = '';
              $javaColorStr = '';
              $javaColorStr .= "\n       	     if(0) {\n";
              if ($c_resReserv) {
         		  foreach($c_resReserv as $year_number=>$xx) {
              	     $javaColorStr .=  "	 	         } else if(this.year=='$year_number') {\n";
                    $javaColorStr .=  "   	            if(0){\n";
            		  foreach($c_resReserv['$year_number'] as $month_number=>$xxx) {
              	        $javaColorStr .=  "	  	           } else if(this.month=='$month_number') {\n";
               		  foreach($c_resReserv['$year_number']['$month_number'] as $day_number=>$modeOfDay) {

                          if($modeOfDay < -99)
                             $calReservedCSS = 'calReserved3'; // customer not approved day
                          elseif($modeOfDay < 0)
                             $calReservedCSS = 'calReserved2'; // customer is approved for day
                          elseif($modeOfDay > 99)
                             $calReservedCSS = 'calReserved1'; // admin color - not approved
                          else
                             $calReservedCSS = 'calReserved0'; // admin color - approved for this day

              	           $javaColorStr .=  "	      	          if(iCount=='$day_number')  tmpStr0 = 'class=\"$calReservedCSS\"';\n";
               		  }
            		  }
                    $javaColorStr .=  "	     	       }\n";
         		  }
              }
              $javaColorStr .=  "	     	     }\n";
              echo $javaColorStr;
              ?>
           }
	     }

		  tmpStr += tmpStr0;
	     tmpStr += '>';
		  // could create a date object here and compare that but probably more efficient to convert to a number and compare number as numbers are primitives
		  tmpCompare = parseInt('' + this.year + padZero(this.month) + padZero(iCount),10);
		  if (tmpCompare >= tmpFrom && tmpCompare <= tmpTo) {
		  	 if (this.isSelect) {
   		     tmpStr += '<a class="cal" href="javascript: g_Calendar.clickDay(' + iCount + ');" id="cell' + iCount + '" onClick="g_Calendar.changeHighlight(this.id);">' + iCount + '</a>';
		  	 }else{
 		      if(isToday)
   		     tmpStr += '<a class="cal" href="javascript: g_Calendar.clickDay(' + iCount + ');" style="color:#000000" title="Today!!!">' + iCount + '</a>';
		      else
   		     tmpStr += '<a class="cal" href="javascript: g_Calendar.clickDay(' + iCount + ');">' + iCount + '</a>';
		  	 }
		  } else {
		     tmpStr += '<span class="disabled">' + iCount + '</span>';
		  }
		  iCount++;
	   } else {
	     if  (i==7-g_startDay || i==((7-g_startDay)%7)+1) tmpStr += 'class="calWeekend"'; else tmpStr +='class="cal"';
		  tmpStr += '>&nbsp;';
	   }
	   tmpStr += '</td>';
	 }
	 tmpStr += '</tr>';
  }
  tmpStr += '</table></td></tr><tr><td style="border:0; padding:0; margin:0;">';
  if (c_autoSubmit) {
     if((monthDist > <?php echo $numMonthsPast_;?>) && (monthDist < <?php echo $numMonthsFuture_;?>)) {
        tmpStr += '<table style="border:0; padding:0; margin:0;" border="0" cellpadding="0"><tr>';
        tmpStr += '<td style="border:0; padding:0; margin:0;" class="calReserved3">&nbsp;&nbsp;&nbsp;&nbsp;</td><td>pending</td><td>&nbsp;</td>';
        tmpStr += '<td style="border:0; padding:0; margin:0;" class="calReserved2">&nbsp;&nbsp;&nbsp;&nbsp;</td><td>approved</td>';
        tmpStr += '</tr></table>';
     }else{
        tmpStr += '<table style="border:0; padding:0; margin:0;" border="0" cellpadding="0"><tr>';
        tmpStr += '<td style="border:0; padding:0; margin:0;" class="calReserved4">&nbsp;&nbsp;&nbsp;&nbsp;</td><td></td><td>&nbsp;</td>';
        tmpStr += '<td style="border:0; padding:0; margin:0;" class="calReserved4">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td style="border:0; padding:0; margin:0;">&nbsp;&nbsp;&nbsp;reload&nbsp;month&nbsp;&nbsp;&nbsp;</td>';
        tmpStr += '</tr></table>';

     }
  }
  tmpStr += '</td></tr></table></form>';
  return tmpStr;
}



Calendar.prototype.selectChange = function(){
  this.month = browser.ns6?this.containerLayer.ownerDocument.forms[0].month.selectedIndex:this.containerLayer.document.forms[0].month.selectedIndex;
  this.writeString(this.buildString());
}

Calendar.prototype.inputChange = function(){
  var tmp = browser.ns6?this.containerLayer.ownerDocument.forms[0].year:this.containerLayer.document.forms[0].year;
  if (tmp.value >=1900 || tmp.value <=2100){
    this.year = tmp.value;
    this.writeString(this.buildString());
  } else {
    tmp.value = this.year;
  }
}
Calendar.prototype.changeYear = function(incr){
    if (incr==1)
      monthDist = monthDist+12;
    else
      monthDist = monthDist-12;
   (incr==1)?this.year++:this.year--;
   this.writeString(this.buildString());
}
Calendar.prototype.changeMonth = function(incr){
    if (incr==1)
      monthDist++;
    else
      monthDist--;

    if (this.month==11 && incr==1){
      this.month = 0;
  	   this.year++;
    } else {
      if (this.month==0 && incr==-1){
        this.month = 11;
	     this.year--;
      } else {
	     (incr==1)?this.month++:this.month--;
	  }
	}
	this.writeString(this.buildString());
}

function pausecomp(millis) {
   date = new Date();
   var curDate = null;
   do { var curDate = new Date(); }
   while(curDate-date < millis);
}


Calendar.prototype.clickDay = function(day) {

	if (!this.isSelect) {
	   var tmp  = eval('document.' + this.theDateA2);
	   tmp.value = this.formatDateAsString(day,this.month,this.year);
	   //jjj Custom function call when someone select a day (toDate or fromDate)
		//	   alert(tmp.value);
		//	   checkSelectedDate(tmp.value);
	   if(this.theDateB1) {
	      // if theDateB is set, then this means user is selecting a date from theDateA, in which case theDateB should be set to theDateA (ONLY IF theDateB form field is empty), and the background color of theDateB should be chanded to a non-white color
		   var tmp2 = eval('document.' + this.theDateB2); // document.formName.theDateB
	      if(tmp2)
	         if(!tmp2.value) {
	      	   tmp2.value = tmp.value; // set theDateB to theDateA
	         	if(document.getElementById(this.theDateB1))
	               document.getElementById(this.theDateB1).style.background='#DDFFDD';
	         }
	   }else{
	      // assume that theDateB is now theDateA
	   	if(document.getElementById(this.theDateA1))
	         document.getElementById(this.theDateA1).style.background='#FFFFFF';
	   }
	}

	// After a date is selected (clicked) by user, hide the calendar
   if (browser.ns4) {
   	if (!this.isSelect)
	      this.containerLayer.hidden=true;
   }
   if (browser.dom || browser.ie4) {
   	if (!this.isSelect)
	      this.containerLayer.style.visibility='hidden';
   }
   if (c_autoSubmit) {
      var tmpFormName = eval('document.' + this.formName);
      tmpFormName.modifyTimeslotSubmit.value = -1;
      tmpFormName.c_startYear.value  = this.year;
      tmpFormName.c_startMonth.value = this.month;
      tmpFormName.c_startDay.value   = this.day;
      if(document.getElementById('startImgCal'))
         document.getElementById('startImgCal').src = '<?php echo $c_calRootDir;?>/images/hourglass.gif';
      tmpFormName.submit();
   }
}


Calendar.prototype.formatDateAsString = function(day, month, year){
  var delim = eval('/\\' + this.dateDelim + '/g');
   switch (this.dateFormat.replace(delim,"")){
     case 'ddmmmyyyy': return padZero(day) + this.dateDelim + this.months[month].substr(0,3) + this.dateDelim + year;
	  case 'ddmmyyyy': return padZero(day) + this.dateDelim + padZero(month+1) + this.dateDelim + year;
	  case 'mmddyyyy': return padZero((month+1)) + this.dateDelim + padZero(day) + this.dateDelim + year;
     case 'yyyymmdd': return year + this.dateDelim + padZero(month+1) + this.dateDelim + padZero(day);
	  default: alert('unsupported date format');
   }
}

Calendar.prototype.writeString = function(str){
  if (browser.ns4){
    this.containerLayer.document.open();
    this.containerLayer.document.write(str);
    this.containerLayer.document.close();
  }
  if (browser.dom || browser.ie4){
    this.containerLayer.innerHTML = str;
  }
}


var showImgClicked = 0;

Calendar.prototype.show = function(event, formName, theDateA, theDateB, bHasDropDown, dateFormat, dateFrom, dateTo) {
   // calendar can restrict choices between 2 dates, if however no restrictions
   // are made, let them choose any date between 1900 and 3000
	showImgClicked = 1;
   // See if we are dealing with a multi-select box.
	var tmpS = eval('document.' + formName + '.' + theDateA + '.options');
	if (tmpS)
		this.isSelect = true;
	else
		this.isSelect = false;

	if (dateFrom) {
		var t_yr = dateFrom.substr(6,4);
		var t_mo = dateFrom.substr(0,2) - 1;
		var t_dy = dateFrom.substr(3,2) - 0;
	   this.dateFrom = new Date(t_yr, t_mo, t_dy);
	} else {
	   this.dateFrom = new Date(1900,0,1);
	}
   this.dateFromDay = padZero(this.dateFrom.getDate());
   this.dateFromMonth = padZero(this.dateFrom.getMonth());
   this.dateFromYear = this.dateFrom.getFullYear();
   this.dateTo = dateTo || new Date(3000,0,1);
   this.dateToDay = padZero(this.dateTo.getDate());
   this.dateToMonth = padZero(this.dateTo.getMonth());
   this.dateToYear = this.dateTo.getFullYear();
   this.hasDropDown = bHasDropDown;
   this.dateFormat = dateFormat || 'dd-mmm-yyyy';
   switch (this.dateFormat){
     case 'dd-mmm-yyyy':
     case 'dd-mm-yyyy':
     case 'yyyy-mm-dd':
       this.dateDelim = '-';
   	 break;
     case 'dd/mm/yyyy':
     case 'mm/dd/yyyy':
     case 'dd/mmm/yyyy':
       this.dateDelim = '/';
   	 break;
   }

  if (browser.ns4) {
     if (!this.containerLayer.hidden) {
	     this.containerLayer.hidden=true;
	     return;
	  }
  }
  if (browser.dom || browser.ie4){
    if (this.containerLayer.style.visibility=='visible') {
	  this.containerLayer.style.visibility='hidden';
	  return;
	}
  }

  if (browser.ie5plus || browser.ie4){
     var event = window.event;
  }
  if (browser.ns4){
     this.containerLayer.x = event.x+10;
     this.containerLayer.y = event.y-5;
  }

  if (browser.ie5plus || browser.ie4){
     var obj = event.srcElement;
	     x = 0;
		  while (obj.offsetParent != null) {
 		  x += obj.offsetLeft;
 		  obj = obj.offsetParent;
		  }
		  x += obj.offsetLeft;
     y = 0;
	  var obj = event.srcElement;
     while (obj.offsetParent != null) {
 		  y += obj.offsetTop;
 		  obj = obj.offsetParent;
		  }
 	  y += obj.offsetTop;

     this.containerLayer.style.left = x+35;
	  if (event.y>0) this.containerLayer.style.top = y;
  }

  if (browser.ns6){
	  this.containerLayer.style.left = event.pageX+10+"px";
	  this.containerLayer.style.top  = event.pageY-5+"px";
  }



  this.theDateA1  =                  theDateA;
  this.theDateA2  = formName + '.' + theDateA;
  this.theDateB1  =                  theDateB;
  this.theDateB2  = formName + '.' + theDateB;
  this.formName   = formName;

  var tmp = eval('document.' + this.theDateA2);
  if (tmp && tmp.value && tmp.value.split(this.dateDelim).length==3 && tmp.value.indexOf('d')==-1) {
      var atmp = tmp.value.split(this.dateDelim)
   	switch (this.dateFormat) {
   	 case 'dd-mmm-yyyy':
   	 case 'dd/mmm/yyyy':
   	   for (var i=0;i<this.months.length;i++){
   	     if (atmp[1].toLowerCase()==this.months[i].substr(0,3).toLowerCase()){
   	       this.month = this.oMonth = i;
   		   break;
   	     }
   	   }
   	   this.day = parseInt(atmp[0],10);
   	   this.year = this.oYear = parseInt(atmp[2],10); // origional year
   	   break;
   	 case 'dd/mm/yyyy':
   	 case 'dd-mm-yyyy':
   	   this.month = this.oMonth = parseInt(atmp[1]-1,10);
   	   this.day = parseInt(atmp[0],10);
   	   this.year = this.oYear = parseInt(atmp[2],10);
   	   break;
   	 case 'mm/dd/yyyy':
   	 case 'mm-dd-yyyy':
   	   this.month = this.oMonth = parseInt(atmp[0]-1,10);
   	   this.day = parseInt(atmp[1],10);
   	   this.year = this.oYear = parseInt(atmp[2],10);
   	   break;
   	 case 'yyyy-mm-dd':
   	   this.month = this.oMonth = parseInt(atmp[1]-1,10);
   	   this.day = parseInt(atmp[2],10);
   	   this.year = this.oYear = parseInt(atmp[0],10);
   	   break;
   	}
   } else { // no date set, default to today
    var theDate = new Date();
  	 this.year = this.oYear = theDate.getFullYear();
     this.month = this.oMonth = theDate.getMonth();
     this.day = this.oDay = theDate.getDate();
   }
   this.writeString(this.buildString());

   // and then show it!
   if (browser.ns4) {
      this.containerLayer.hidden=false;
   }
   if (browser.dom || browser.ie4){
      this.containerLayer.style.visibility='visible';
   }
}



Calendar.prototype.hide = function() {
   if (browser.ns4) this.containerLayer.hidden = true;
   if (browser.dom || browser.ie4){
      this.containerLayer.style.visibility='hidden';
   }
}

function handleDocumentClick(e) {

	if (browser.ie9 && showImgClicked){
		xPix = e.pageX+'px';
		yPix = e.pageY+'px';
		document.getElementById('container').style.left = xPix;
		document.getElementById('container').style.top = yPix;
		showImgClicked = 0;
}
	var bTest = (e.pageX > parseInt(g_Calendar.containerLayer.style.left,10) && e.pageX < (parseInt(g_Calendar.containerLayer.style.left,10)+125) && e.pageY < (parseInt(g_Calendar.containerLayer.style.top,10)+125) && e.pageY > parseInt(g_Calendar.containerLayer.style.top,10));
	if (e.target.name!='imgCalendar' && e.target.name!='month' && e.target.name!='year' && e.target.name!='calendar' && !bTest) {
		g_Calendar.hide();
	}

	/****** xxxxxxxxxxxxxxxxxxx
	if (browser.ie4 || browser.ie5plus)	e = window.event;
	if (browser.ns6){
		var bTest = (e.pageX > parseInt(g_Calendar.containerLayer.style.left,10) && e.pageX < (parseInt(g_Calendar.containerLayer.style.left,10)+125) && e.pageY < (parseInt(g_Calendar.containerLayer.style.top,10)+125) && e.pageY > parseInt(g_Calendar.containerLayer.style.top,10));
		if (e.target.name && e.target.name!='imgCalendar' && e.target.name!='month'  && e.target.name!='year' && e.target.name!='calendar' && !bTest) {
			g_Calendar.hide();	}	}
	if (browser.ie4 || browser.ie5plus){
	  // extra test to see if user clicked inside the calendar but not on a valid date, we don't want it to disappear in this case
	  var bTest = (e.x > parseInt(g_Calendar.containerLayer.style.left,10) && e.x <  (parseInt(g_Calendar.containerLayer.style.left,10)+125) && e.y < (parseInt(g_Calendar.containerLayer.style.top,10)+125) && e.y > parseInt(g_Calendar.containerLayer.style.top,10));
		if (e.srcElement.name!='imgCalendar' && e.srcElement.name!='month' && e.srcElement.name!='year' && !bTest & typeof(e.srcElement)!='object'){
			g_Calendar.hide();
		}	}
	if (browser.ns4) g_Calendar.hide();
	xxxxxxxxxxxxxxxxxx */
}

// utility function
function padZero(num) {
   return ((num <= 9) ? ("0" + num) : num);
}
// Finally licked extending  native date object;
Date.isLeapYear = function(year){
   if (year%4==0 && ((year%100!=0) || (year%400==0)))
      return true;
   else return false;
}
Date.daysInYear = function(year){ if (Date.isLeapYear(year)) return 366; else return 365;}
var DAY = 1000*60*60*24;
Date.prototype.addDays = function(num){
	return new Date((num*DAY)+this.valueOf());
}

// events capturing, careful you don't override this by setting something in the onload event of
// the <body> tag
window.onload=function(){
   new Calendar(new Date());
   if (browser.ns4){
      if (typeof document.NSfix == 'undefined'){
	      document.NSfix = new Object();
         document.NSfix.initWidth=window.innerWidth;
	      document.NSfix.initHeight=window.innerHeight;
 	   }
   }
}

if (browser.ns4) window.onresize = function(){
   if (document.NSfix.initWidth!=window.innerWidth || document.NSfix.initHeight!=window.innerHeight)
      window.location.reload(false);
} // ns4 resize bug workaround

window.document.onclick=handleDocumentClick;

<?php if($showCalErrors) { ?>
   window.onerror = function(msg,url,line){
      alert('******* an error has occurred ********' +
      '\n\nPlease check that' +
      '\n\n1)You have not added any code to the body onload event,'
      +  '\nif you want to run something as well as the calendar initialisation'
      + '\ncode, add it to the onload event in the calendar library.'
      + '\n\n2)You have set the parameters correctly in the g_Calendar.show() method '
      + '\n\nSee www.totallysmartit.com\\examples\\calendar\\simple.asp for examples'
      + '\n\n------------------------------------------------------'
      + '\nError details'
      + '\nText:' + msg + '\nurl:' + url + '\nline:' + line);
   }
<?php } ?>

</script>