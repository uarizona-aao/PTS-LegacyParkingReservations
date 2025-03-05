<?php

if (isset($_GET['month'])) $month = $_GET['month'];
else $month = date("m");
if (isset($_GET['date'])) $date = $_GET['date'];
elseif (date("d")==date("t")) {
	$date = 1;
	$month++;
}
else $date = date("d")+1;
if (isset($_GET['year'])) $year = $_GET['year'];
else $year = date("y");

$currentMonth = date("m");
// disallow same-day reservations
$currentDate = date("d");
$currentYear = date("y");

// date bug fix, 2006-11-28
if ($month!=$currentMonth && $date>28) $date = 28;

$dateStamp = strtotime("$month/$date/$year");
$lastDay = date("t",$dateStamp);
?>
<script language="JavaScript" type="text/javascript">
var currCell = "cell<?= $date ?>";
var currMonth = "<?= $month ?>";
var currYear = "<?= $year ?>";
function changeHighlight (ele) {
	if (document.getElementById(currCell).className=="highlight")
		document.getElementById(currCell).className = "reg";
	document.getElementById(ele).className = "highlight";
	currCell = ele;

	var dateString = currCell.substr(4);
	if (dateString.length==1)
		dateString = "0"+dateString;
	if (currMonth.length==1)
		currMonth = "0"+currMonth;

	window.parent.setDateField(currMonth+"/"+dateString+"/20"+currYear);
}
</script>
<div class="calendar">
<table class="calendar monthName" width="100%" border="0"><tr>
	<?
	if ($year>$currentYear || ($year==$currentYear && $month!=$currentMonth)) {
		?>
		<td align="left"> <a href="caltest.php?month=<?= ($month==1) ? "12" : ($month-1) ?>&date=<?= $date ?>&year=<?= ($month==1) ? "0".($year-1) : $year ?>">&lt;&lt;</a> &nbsp; </td>
		<?
	} else {
		echo '<td align="left">&nbsp;</td>';
	}
	?>
	<td align="center">
	<?= date("M Y",$dateStamp) ?>
	</td>
	<?
	if ($year<8 || ($year==8 && $month!=12)) {
		?>
		<td align="right"> &nbsp; <a href="caltest.php?month=<?= ($month==12) ? "01" : ($month+1) ?>&date=<?= $date ?>&year=<?= ($month==12) ? "0".($year+1) : $year ?>">&gt;&gt;</a> </td>
		<?
	} else {
		echo '<td align="right">&nbsp;</td>';
	}
	?>
</tr></table>

<div class="month">
	<table border="0" cellpadding="0" cellspacing="0" width="140">
	<tr><td width="20">S</td><td width="20">M</td><td width="20">T</td><td width="20">W</td><td width="20">T</td><td width="20">F</td><td width="20">S</td></tr>
<?php
$weekdayCount = 0;
for ($d=1; $d<=$lastDay; $d++) {
	if ($weekdayCount==0) echo "		<tr>\n";
	$firstDay = date("w",strtotime("$month/1/$year"));
	if ($d==1) {
		for ($i=0; $i<$firstDay; $i++) {
			echo "			<td class=\"filled\">&nbsp;</td>\n";
			$weekdayCount++;
		}
	}

	echo "			<td id=\"cell$d\"";
	//if (($month==$currentMonth && $d<=$currentDate) || $weekdayCount==0 || $weekdayCount==6) echo " class=\"na\">$d</td>\n";
	if (($month==$currentMonth && $d<=$currentDate)) echo " class=\"na\">$d</td>\n";
	else {
		if ($month==$currentMonth && $d==$date) echo ' class="highlight"';
		echo " onClick=\"changeHighlight(this.id);\"><a href=\"#\">$d</a></td>\n";
	}
	$weekdayCount++;

	if ($d==$lastDay) {
		for ($i=$weekdayCount; $i<7; $i++) {
			echo "			<td class=\"filled\">&nbsp;</td>\n";
		}
	}

	if ($weekdayCount==7) {
		echo "		</tr>\n";
		$weekdayCount = 0;
	}
}
?>
	</tr>
	</table>
</div>
<table width="100%" border="0" cellpadding="3" cellspacing="0">
<tr valign="middle">
	<td align="center" width="20%">
	<?
	if (    0    &&   ($year>$currentYear || ($year==$currentYear && $month!=$currentMonth))) {
		?>
		<a href="caltest.php?month=<?= ($month==1) ? "12" : ($month-1) ?>&date=<?= $date ?>&year=<?= ($month==1) ? "0".($year-1) : $year ?>">&lt;&lt;</a> &nbsp;
		<?
	} else {
		echo '&nbsp;';
	}
	?>
	</td>
	<td align="center" width="60%" style="font-size:11px;"><a href="#" onClick="window.parent.closeCal()">x Close x</a></td>
	<td align="center" width="20%">
	<?
	if (    0    &&   ($year<8 || ($year==8 && $month!=12))) {
		?>
		<a href="caltest.php?month=<?= ($month==12) ? "01" : ($month+1) ?>&date=<?= $date ?>&year=<?= ($month==12) ? "0".($year+1) : $year ?>">&gt;&gt;</a>
		<?
	} else {
		echo '&nbsp;';
	}
	?>
	</td>
</tr>
</table>
</div>