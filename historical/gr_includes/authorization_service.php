<?php
/*
 *
 *
 *
 *
 * OLD, USE authorization_service_ext.php
 *
 *
 *
 *
 *
 */

//require_once 'authorization.php';
//
//
//class authorization_netid extends authorization {
////private $student_id;
//private $employee_id;
//private $dbkey;
//private $phonebook_entry;
//
//protected function set_names() {
//  $this->form_name = 'UA NetID Required';
//  $this->username_label = 'NetID';
//}
//
// // Check the username and password against the UA NetID LDAP server
// // Also saves employee ID, student ID, and dbkey variables as reported by the NetID server
// // oooo, commandline> ldapsearch -D 'uid=jbrabec,ou=Accounts,ou=NetID,ou=CCIT,o=University of Arizona,c=US' -W -H 'ldaps://netid.arizona.edu/:636' -x
// protected function test_login($username, $password) {
//
//if ($GLOBALS['DEBUG_DEBUG'])
//	echo "<br><br><br><br>----------FAKE LOGIN AND RESULTS FOR user: jbrabec.<br>";
//
//$ds = @ldap_connect('ldaps://netid.arizona.edu/', 636);
//if(@!$GLOBALS['DEBUG_DEBUG'] && $ds === false)
//  throw new error("NetID Error", "Cannot connect to the NetID server. Please try again later.");
//$username = $this->get_username();
//
//// bind to the LDAP server
//$dn = 'uid='.$username.',ou=Accounts,ou=NetID,ou=CCIT,o=University of Arizona,c=US';
//$bind = @ldap_bind($ds, $dn, $password);
//
//if(@$GLOBALS['DEBUG_DEBUG'] || $bind) {
//
//	if (@$GLOBALS['DEBUG_DEBUG']) {
//		$results['count']						= 3;
//		$results[0]['emplid'][0]			= '16808753';
//		$results[0]['activeemployee'][0]	= 1;
//		$results[0]['dbkey'][0]				= ''; // 109106543384;
//
//	} else {
//		$sr = @ldap_search($ds, $dn, '(objectclass=*)');
//		$results = @ldap_get_entries($ds, $sr);
//		@ldap_close($ds);
//	}
//
//	if($results['count']) $this->set_authorized(); // same as $this->authorized
//	if(isset($results[0]['activeemployee'][0]))
//		$this->employee_id = $results[0]['emplid'][0];  // 9/28/2009: $this->employee_id = $results[0]['employee xxxx id'][0];
//
//	//## On July 16 2010, only keep
//	//getting rid of student_id = $results[0]['student xxxx id'][0]
//	//if (@$results[0]['activestudent'][0] > 0) {
//	//if (@$results[0]['studentid'][0])
//	//$this->student_id = $results[0]['studentid'][0];
//	//elseif (@$results[0]['emplid'][0])
//	//$this->student_id = $results[0]['emplid'][0];  }
//
//	if(isset($results[0]['dbkey'][0]))
//		 $this->dbkey = $results[0]['dbkey'][0];
//}
//
//return $this->is_authorized(); // returns $this->authorized (for the most part)
//}
//
//protected function get_username_field() {
//	return field_factory::get_netid_field();
//}
//
//protected function get_error() {
//	return "Sorry, you entered an invalid NetID or password.";
//}
//
//// Loads the currently signed in user's UA phonebook entry. Invoked the first time get_phonebook is called.
//private function lookup_phonebook() {
//	$ds = ldap_connect('ldap.arizona.edu');
//	$bind = ldap_bind($ds);
//	$sr = ldap_search($ds, "o=University of Arizona,c=US", "(&(objectclass=*)(dbkey=$this->dbkey))");
//	$results = ldap_get_entries($ds, $sr);
//
//	// Only save the first entry (because dbkey is an exact match)
//	$this->phonebook_entry = $results[0];
//
//	ldap_close($ds);
//}
//
//// Returns the field in the current user's UA phonebook entry with the title of $key
//// For a listing of fields, consult the UA phonebook LDAP documentation or var_dump($this->phonebook_entry) after loading
//function get_phonebook($key) {
//	if(!$this->is_authorized()) return '';
//	if(!isset($this->phonebook_entry)) $this->lookup_phonebook();
//	return isset($this->phonebook_entry[$key][0]) ? $this->phonebook_entry[$key][0] : '';
//}
//}
//
//
//
//
//class authorization_garage_reservation extends authorization_netid {
//// Internal code to represent the step reached in the authorization process
//// 1 = netid, 2 = user, 3 = authorized at required_level
//private $auth_level = 0;
//private $user_id, $user_name;
//private $authorization, $required_level;
//private $customer_id, $customer;
//private $email;
//
//function try_authorize($level) {
//	// Set up the level of authorization required and reset if it has increased
//	if(isset($this->required_level)) {
//		 if($level > $this->required_level and $level > $this->authorization)
//			  $this->set_unauthorized();
//	}
//	$this->required_level = $level;
//	if(!$this->is_authorized()) $this->auth_level = 0;
//	parent::try_authorize();
//}
//
//protected function test_login($username, $password) {
//	$this->auth_level = 0;
//	if( !parent::test_login($username, $password) ) return false;
//	$this->set_unauthorized();
//	$this->auth_level = 1;
//
//	// Check user database for the given NetID
//	$db = get_db();
//	$username = strtolower($username);
//	$db->query("select USER_ID, USER_NAME, AUTH_ID_FK, EMAIL from PARKING.GR_USER, PARKING.GR_DEPARTMENT where NETID = '$username'");
//	if(!$db->num_rows()) return false;
//
//	$this->user_id = $db->get_from_top('USER_ID');
//	$this->user_name = $db->get_from_top('USER_NAME');
//	$this->authorization = $db->get_from_top('AUTH_ID_FK');
//	//$this->customer_id = $db->get_from_top('DEPT_NO_FK');
//	//$this->customer = $db->get_from_top('DEPT_NAME');
//	$this->email = $db->get_from_top('EMAIL');
//
//	$db->query("select DEPT_NO_FK from PARKING.GR_USER_DEPARTMENT where USER_ID_FK = $this->user_id");
//	$depts = array();
//	foreach($db->get_results() as $result)
//		 $depts[] = "'" . $result['DEPT_NO_FK'] . "'";
//	$this->customer_id = implode(',',$depts);
//
//	$this->auth_level = 2;
//
//	if($this->authorization < $this->required_level)
//		 throw new error('Access Denied', 'You are not authorized to use this part of the site.');
//	$this->auth_level = 3;
//
//	$db->execute("update PARKING.GR_USER set LAST_LOGIN = sysdate where USER_ID = $this->user_id");
//
//return true;
//}
//
//function get_user_id() {
//	return $this->user_id;
//}
//
//function get_user_name() {
//	return $this->user_name;
//}
//
//function get_authorization() {
//	$tmpAuth = $this->authorization ? $this->authorization : $_SESSION['cuinfo']['auth']; // jody
//	return $tmpAuth;
//}
//
//function get_customer_id() {
//	return $this->customer_id;
//}
//
//function get_customer() {
//	return $this->customer;
//}
//
//function get_display_name() {
//	return $this->get_user_name();
//}
//
//function get_email() {
//	return $this->email;
//}
//
//function get_signup() {
//	if($this->auth_level == 1 and $this->required_level == 2) return '/parking/garage-reservation/request_access.php';
//	return null;
//}
//
//function at_signup() {
//	if($_SERVER['PHP_SELF'] == $this->get_signup()) return true;
//	return false;
//}
//}
//
//
//
//class authorization_service extends authorization_netid {
//private $eid, $first_name, $last_name, $division_id;
//
//// Check against the employee list (partially loaded from UIS) for a matching UA NetID
//function test_login($username, $password) {
//	if( !parent::test_login($username, $password) ) return false;
//	$this->set_unauthorized();
//
//	$db = get_db();
//	$db->query("select * from PARKING.EMPLOYEE where NETID = '$username' and END_DATE > sysdate");
//	if($db->num_rows() != 1) return false;
//
//	$this->eid = $db->get_from_top('EID');
//	$this->first_name = $db->get_from_top('FIRST_NAME');
//	$this->last_name = $db->get_from_top('LAST_NAME');
//	$this->division_id = $db->get_from_top('EMP_DIVISION_FK');
//
//	$this->set_authorized();
//	return true;
//}
//
//function get_display_name() {
//	return "$this->first_name $this->last_name";
//}
//
//// Don't think this is used anywhere.
//function get_eid() {
//	return $this->eid;
//}
//
//// Returns the appropriate DIVISION_ID for PARKING.PTS_DIVISION
//function get_division_id() {
//	return $this->division_id;
//}
//}
?>