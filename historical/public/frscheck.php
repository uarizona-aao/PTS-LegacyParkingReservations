<?php
/**************************
 * Called from forms.js, like so:
 *		frames['frsCheckFrame'].location.href = 'frscheck.php?frs='+frs+'&cust='+cust;
 * Does not include top.php nor bottom.php because array noIncFiles contains 'frscheck.php'
 *************************/

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', true);
//error_reporting(E_ALL);

if (isset($_GET['frs']) && isset($_GET['cust']))
{
	//	$docRoot = $_SERVER['DOCUMENT_ROOT'];
	//	include_once $docRoot.'/datatest/headrefs.php';
	include_once 'top.inc.php'; // AJAX calls this file, and this file is "limited" in top.inc.php.
	?>
	<!-- jQuery -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
	<!-- see spinner_x in top.inc.php -->
	<script type="text/javascript" src="/js/spin.js"></script>
	<?php	spinnerWaiting(' ');	?>
	<script>
	if (spinner_x_div) {
		spinner_x_div.style.left  = '22px';
		spinner_x_div.style.top  = '22px';
	}
	</script>
	<table border="0" style="border:0; padding:0; margin:0 0 0 15px; font-weight:bold; font-size:14px;">
	<tr><td valign="top">
	<div style="vertical-align: middle;">
	<?php
	// echoNow();
	if (!preg_match('/^\w{7}$/si', $_GET['frs']))
	{
		?>
			<img src="/images/icons/invalid.gif" id="validity" style="vertical-align: middle;" />
			<span style="color:#c03;">KFS must be 7 chars.</span>
		<?php
	}
	else
	{
		if (!isset($dbFConn)) $dbFConn = new database();

		$qVars['frs']  = $_GET['frs'];
		$qVars['cust'] = $_GET['cust'];

		$goodKFS = $goodUser = false;

		// kfs 2559100 has no cust id
		$query = "SELECT USER_ID
			FROM PARKING.GR_USER INNER JOIN (PARKING.GR_USER_DEPARTMENT UD INNER JOIN PARKING.GR_FRS F ON UD.DEPT_NO_FK=F.DEPT_NO_FK) ON UD.USER_ID_FK=USER_ID
			WHERE FRS=:frs";
		$dbFConn->sQuery($query, $qVars);
		if ($dbFConn->rows)
			$goodKFS = true;

		if ($goodKFS)
		{
			$query = "SELECT USER_ID
				FROM PARKING.GR_USER INNER JOIN (PARKING.GR_USER_DEPARTMENT UD INNER JOIN PARKING.GR_FRS F ON UD.DEPT_NO_FK=F.DEPT_NO_FK) ON UD.USER_ID_FK=USER_ID
				WHERE FRS=:frs AND USER_ID=:cust";
			$dbFConn->sQuery($query, $qVars);
			if ($dbFConn->rows)
				$goodUser = true;
		}

		// if ($GLOBALS['jody']) echo '---KFS QUERY: '.$query.'<br>'.$qVars['frs'].','.$qVars['cust'].'<br>';

		if ($goodUser)
		{
			?>
			<img src="/images/icons/valid.gif" id="validity" style="vertical-align: middle;" />
			<span style="color:#000;">KFS is valid.</span>
			<?php
		}
		else
		{
			?>
			<img src="/images/icons/invalid.gif" id="validity" style="vertical-align: middle;" />
			<span style="color:#c03;">
			   <?php if ($goodKFS) { ?>
					ERROR: INVALID customer ID.
				<?php }else{ ?>
					ERROR: Invalid KFS number (no longer using FRS numbers).
				<?php } ?>
				<?php if ($GLOBALS['DEBUG_DEBUG']) { ?>
					<div style='position:absolute; top:22; right:22; width:100%; height:100%; padding:1px 5px 5px 5px; margin:1px; background-color:orangered; color:white; font-weight:normal;'>Debug Data query: <?php echo $query . ' [[ ' . print_r($qVars,true). ' ]] ';?></div>
				<?php } ?>
			</span>
			<?php
		}
		$dbFConn->disConnect();
	}
	?>
	</div>
	</td></tr>
	</table>
	<?php
}
else
{
	echo '<img src="/images/edit.gif" name="validity" style="vertical-align: middle;"/>';
}
?>