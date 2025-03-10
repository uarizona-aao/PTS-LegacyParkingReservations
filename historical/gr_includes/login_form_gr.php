<?php
/***
 * included in footer_newgr .php
 *
 */

if (checkEnabled('Webauth Garage'))
{
	/***
	 * New webauth longin way
	 */
	//------------------------------- Go get the login form, if not logged in
	include_once '/var/www2/include/top_Ext_Head.php';
	include_once '/var/www2/include/login_functions.php';
	$loginReturnURI = getReturnUri(@$loginReturnURI);
	$_SESSION['ignore_t2'] = $loginReturnURI; // No t2 account login needed
	include_once '/var/www2/include/login_external.php'; // Fills the $_SESSION['eds_data'] array.
}
else if (!isset($_SESSION['cuinfo']['auth']))
{
	/*********
	 * OLD SCHOOL - Local, LDAP
	 */
	?>
	<p align="center"><b>Please enter your UA Net ID and password to continue.</b>
		<br/>If you do not have a UA Net ID you will need to obtain one before continuing.</p>

	<form name="loginForm" method="post" action="" onSubmit="return checkFormLogin();">
	<table class="formbox" cellpadding="0" cellspacing="0" align="center">
	<tr><td colspan="2" class="title">UA NetID Login &nbsp;
		<span style="background-color:#fff; font-size:12px;">
		 <a href="/help/netid.php" target="_blank" style="text-decoration:none;"><img align="absmiddle" src="/images/icons/help.gif" alt="Click Here for Help" width="18" height="18" border="0" /></a>
		</span></td></tr>
	<tr><td class="req">NetID</td><td class="form_element">
		<input tabindex="1" type="text" name="login" autocomplete="off" value="<?php if (isset($_POST['login'])) echo htmlentities($_POST['login'], ENT_QUOTES); ?>" /></td></tr>
	<tr><td class="req">Password</td><td class="form_element"><input tabindex="2" type="password" name="password" autocomplete="off" /></td></tr>
	<tr align="center"><td class="submitter" colspan="2"><input tabindex="3" type="submit" name="submit" value="Login" /></td></tr>
	</table>
	</form>
	<script type="text/javascript">
	 document.loginForm.login.focus();
	</script>
	<?php
}

?>

<script type="text/javascript">
var alerts = '';
var netidRe = /^[0-9A-Za-z _]{1,16}$/;
var passRe = /^[0-9A-Za-z]{1,20}$/;
function checkFormLogin () {
	with (document.loginForm) {
		if (!netidRe.test(login.value))
			alerts += "Invalid Net ID\n";
		if (!password.value.length)
			alerts += "Please enter your password\n";
	}
	if (alerts) {
		alert(alerts);
		return false;
	}
}
</script>
