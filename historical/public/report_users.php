<h1>Report - Online Reservation Users</h1>
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

$showSQLerrors = true;

//echo '<pre>';
//echo '============'.$_SESSION['auth'].'<br>';
//print_r($_SESSION);
//echo '</pre>';

$protectPass = $dbConn->protectCheck(3);

//echo '<pre>';
//print_r($_SESSION);

if ($auth < 3)
{
	if ($_POST['start_date'] && $_POST['end_date']) {
		$form_submit = true;
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
	} else {
		$form_submit = false;
		$start_date = date('m/d/y');
		$end_date = date('m/d/y');
	}

   ?>

	<form method="post" action="report_users.php">
	<table>
	 <tr>
	  <td>
	   Organize results by:
	  </td>
	  <td>
	   <select name="order_by">
	    <option value="USER_NAME" <?php if ($_POST['order_by']=='USER_NAME') echo 'selected';?>>User Name</option>
	    <option value="PHONE" <?php if ($_POST['order_by']=='PHONE') echo 'selected';?>>Phone Number</option>
	    <option value="EMAIL" <?php if ($_POST['order_by']=='EMAIL') echo 'selected';?>>Email Address</option>
	    <option value="DEPT_NAME" <?php if ($_POST['order_by']=='DEPT_NAME') echo 'selected';?>>Department Name</option>
	   </select>
	  </td>
	 </tr>
	 <tr>
	  <td>
	   Display reservations from:
	  </td>
	  <td>
	   <input type="text" name="start_date" value="<?php echo $start_date;?>" maxlength="8" size="8"> to
	   <input type="text" name="end_date" value="<?php echo $end_date;?>" maxlength="8" size="8">
	   <input name="submit_button" type="submit" value="View">
	  </td>
	 </tr>
	</table>
	</form>


   <?php

	if ($form_submit) {

		// Process form submission

		$query = "SELECT COUNT(USER_ID_FK) AS CT, USER_NAME, PHONE, EMAIL, DEPT_NAME
					 FROM PARKING.GR_RESERVATION INNER JOIN PARKING.GR_USER ON USER_ID_FK = USER_ID
					   LEFT JOIN PARKING.GR_DEPARTMENT ON DEPT_NO_FK = DEPT_NO
					 WHERE ENTER_TIME between to_date(:start_date, 'mm/dd/yy') and to_date(:end_date, 'mm/dd/yy')
					 GROUP BY USER_NAME, PHONE, EMAIL, DEPT_NAME
					 ORDER BY :order_by";
		$qVars = array('start_date'=>$start_date, 'end_date'=>$end_date, 'order_by'=>$_POST['order_by']);
		//		echo "$query<br>";
		//		print_r($qVars);
		$dbConn->sQuery($query, $qVars);

		?>
		<table border="0" cellpadding="1" cellspacing="0" style="border-top:1px solid black; border-right:1px solid black;">
		 <tr>
		  <td style="border-left:1px solid black; border-bottom:1px solid black; font-weight:bold;<?php if ($_POST['order_by']=='USER_NAME') echo 'background-color:#AAFFAA;'?>">User Name</td>
		  <td style="border-left:1px solid black; border-bottom:1px solid black; font-weight:bold;<?php if ($_POST['order_by']=='PHONE') echo 'background-color:#AAFFAA;'?>">Phone</td>
		  <td style="border-left:1px solid black; border-bottom:1px solid black; font-weight:bold;<?php if ($_POST['order_by']=='EMAIL') echo 'background-color:#AAFFAA;'?>">Email</td>
		  <td style="border-left:1px solid black; border-bottom:1px solid black; font-weight:bold;<?php if ($_POST['order_by']=='DEPT_NAME') echo 'background-color:#AAFFAA;'?>">Department</td>
		  <td style="border-left:1px solid black; border-bottom:1px solid black; font-weight:bold;"># Reservations</td>
		 </tr>
		<?php
		for ($i=0; $i<$dbConn->rows; $i++) {
			?>
			<tr>
			 <td style="border-left:1px solid black; border-bottom:1px solid black;"><?php echo $dbConn->results['USER_NAME'][$i];?></td>
			 <td style="border-left:1px solid black; border-bottom:1px solid black;"><?php echo $dbConn->results['PHONE'][$i];?></td>
			 <td style="border-left:1px solid black; border-bottom:1px solid black;"><?php echo $dbConn->results['EMAIL'][$i];?></td>
			 <td style="border-left:1px solid black; border-bottom:1px solid black;"><?php echo $dbConn->results['DEPT_NAME'][$i];?></td>
			 <td style="border-left:1px solid black; border-bottom:1px solid black;"><?php echo $dbConn->results['CT'][$i];?></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php

	}

	?>


<?php } ?>
