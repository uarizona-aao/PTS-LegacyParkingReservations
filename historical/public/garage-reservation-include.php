<?php
$admin_domain = 'https://www.pts.arizona.edu';

$pTitle = 'UA PTS | Garage Reservation';
$pDescription = 'University of Arizona Parking and Transportation Department Visitor Garage Reservation.';
$pAuthor = 'UA P&T Development Team';
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$section = "parking";
$activeLink="Garage Reservation";
include_once $docRoot.'/datatest/headrefs.php';
?>
<link rel="stylesheet" href="/css/info-item-pages.css">
</head>

<body  data-offset="15">


<?php

if (!isset($dbConn)) $dbConn = new database();

if (!checkEnabled('Garage Reservaton'))
{
	// Note: Winter Shutdown message will be displayed within checkEnabled func.
		if ($_SERVER['SCRIPT_NAME'] != '/parking/garage-reservation/index.php')
			locationHref('/parking/garage-reservation/index.php');
}

include_once $docRoot.'/Templates/top_menu.php';
include_once '/var/www2/include/messages.inc.php'; // Winter shutdown displays.

if (!isset($_SESSION['cuinfo']['auth']))
{
	$_SESSION['ignore_t2'] = true;
	$ignore_t2 = true;
	include_once '/var/www2/include/login_functions.php';
	$loginReturnURI = getReturnUri(@$loginReturnURI);

	//$_SESSION['ignore_t2'] = $loginReturnURI; No t2 account login needed
	include_once '/var/www2/include/login_external.php';
	unset($_SESSION['ignore_t2']); //This is needed here, probably check all others.
	//include_once 'gr/login_form_gr.php'; // used to be 'required' only if ($GLOBALS['loadLogin'])
}

$dbConn->protectCheck(2); // sets $_SESSION['cuinfo']
?>

<table border="0" cellpadding="0" cellspacing="0" id="printBlocker" align="right" style="padding-top:1px;">
<tr>
<td>
<?php
if (isset($_SESSION['cuinfo']['auth']))
{
	$auth = $_SESSION['cuinfo']['auth'];
	?>
	<div style="white-space:nowrap; font-size: 1.3em; text-align: right; padding-right:22%;">
	<?php if ($auth >= 2) { ?>
		<span style="font-weight:bold;">Sections: &nbsp; <?php echo navLinks("$admin_domain/garage_reservation/administrator/","Administrator",3,$auth) ?> &nbsp;</span>
		<?php echo navLinks("/parking/garage-reservation/cashier/","Cashier",3,$auth) ?> &nbsp;
		<?php echo navLinks("/parking/garage-reservation/","Customer",2,$auth) ?> &nbsp;
		<?php /*** echo navLinks("$admin_domain/billing/","Billing",3,$auth) **/ ?>
		<?php /** echo navLinks("/garage_test/events/","Events",2,$auth) **/ ?>
	<?php } ?>
	</div>
	<?php
}
?>
</td>
</tr></table>

<div id="ttop"></div>

<?php

if ($dbConn->cForm)
{
	// contains new customer <form>, if needed, for garage res system.
	if (!$auth || $auth < 2)
		echo $dbConn->cForm;
	unset($dbConn->cForm);
}

function navLinks($link,$text,$reqAuth=2,$custAuth=2)
{
	// Used in footer_newgr .php
	if ($reqAuth>$custAuth) return false;
	if (strstr($_SERVER['PHP_SELF'],$link)) return "[ <a href=\"$link\">$text</a> ]";
	else return "<a href=\"$link\">$text</a>";
}

function restricted()
{
	if (strstr($_SERVER['REQUEST_URI'],"/garage_test/cashier")) {
		$ips = array("128.196.6","128.196.3","128.196.81","128.196.71","128.196.24","150.135.114","150.135.80","150.135.133");
		if (!in_array(substr($_SERVER['REMOTE_ADDR'],0,strrpos($_SERVER['REMOTE_ADDR'],'.')),$ips)) return true;
	}
}
?>
