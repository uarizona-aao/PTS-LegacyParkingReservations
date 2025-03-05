<?php
/*****************
 *
 * This is the web page footer AND header too!!!!!!!!!!!!!!!
 * The page body comes from $content
 */
include_once '/var/www2/include/gr/login_form_gr.php'; // used to be 'required' only if ($GLOBALS['loadLogin'])

//if ($GLOBALS['jody']) echo '~~~~~~~~~~~~ ob_get_contents / ob_end_clean '.__FILE__.'<br>';
$content = ob_get_contents();
ob_end_clean();

preg_match("/\<title\>([a-zA-Z0-9\s\-\.\_\?\/\'\"\:\;\+\=\#\!\@\$\%\^\&\*\(\)]+)\<\/title\>/",$content,$titleMatch);
if (count($titleMatch)) $title = $titleMatch[0];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<?php echo (isset($title)) ? $title : '<title>Garage Reservations..</title>' ?>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="/css/base_gr.css">


<!-- INTERNAL, GR, and EXTERNAL web sites -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<!-- COULD USE local: /js/mootools/jquery-1.8.3.js -->
<script type="text/javascript">
// Better to replace '$()' with 'jQuery()' than using noConflict
// Added noConflict so that mootools will work (jQuery conflicts with $(...) functions)
//jQuery.noConflict();
</script>

</head>


<body<?php echo (isset($onLoad)) ? " onLoad=\"$onLoad\"" : '';?>>

<?php
$noNav = array("/parking/garage-reservation/caltest.php","/garage_test/events/verify.php","/parking/garage-reservation/frscheck.php");

if (!in_array($_SERVER['PHP_SELF'],$noNav) && !isset($GLOBALS['nonav'])) // don't think $GLOBALS['nonav'] is used.
{
	?>
	<!--NEW UA Banner-->
	<div style="height:42px; width:100%; background:#003366; margin:0px; padding:0px;" id="uaheader">
	 <a href="http://www.arizona.edu/" target="_blank" tabindex="-1"><img src="/img/logos/UA_A-line-css_BLU.gif" alt="The University of Arizona" width="370" height="42" border="0" align="left" style="padding-left:15%;" /></a>
	</div>
	<!--END New UA Banner-->

	<div id="content">

	<table border="0" cellpadding="0" cellspacing="0" id="printBlocker" align="right" style="padding-top:1px;">
	<tr>
	<td>
	<?php
	if (isset($_SESSION['cuinfo']['auth']))
	{
		$auth = $_SESSION['cuinfo']['auth'];
		?>
		<div id="grnav">
		<div class="sections" style="white-space:nowrap;">
		<?php if ($auth>1) { ?>
			Sections: &nbsp; <?php echo navLinks("/parking/garage-reservation/administrator/","Administrator",3,$auth) ?> &nbsp;
			<?php echo navLinks("/parking/garage-reservation/cashier/","Cashier",3,$auth) ?> &nbsp;
			<?php echo navLinks("/parking/garage-reservation/","Customer",2,$auth) ?> &nbsp;
			<?php echo navLinks("/garage_test/billing/","Billing",3,$auth) ?> &nbsp;
			<?php /** echo navLinks("/garage_test/events/","Events",2,$auth) **/ ?>
		<?php } else {
			echo '&nbsp;';
		} ?>
		</div>
		</div>
		<div style="position:absolute; right:40px; top:125px; font-weight:bold; padding:5px; background-color:#EEEEEE; color:#336666;">
			Signed in as <?php echo $_SESSION['cuinfo']['netid'] ?>
			(<a href="?logout=1">Logout</a>)</div>
		<?php
	}
	?>
	</td>
	<td align="right">
	<?php
	include_once $_SERVER['DOCUMENT_ROOT'].'/search.php';
	?>
	</td>
	</tr></table>

	<div style="clear:left;">
	<?php
}
echo $content;

?>
</div>

</body>
</html>