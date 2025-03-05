<?php
/*
 * authorization_garage_reservation . php
 * User authorization routines for the garage reservation application
 * (See classes authorization and authorization_netid for details)
*/

//require_once 'gr/authorization_netid . php';

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
//// Set up the level of authorization required and reset if it has increased
//if(isset($this->required_level)) {
//if($level > $this->required_level and $level > $this->authorization)
//$this->set_unauthorized();
//}
//$this->required_level = $level;
//if(!$this->is_authorized()) $this->auth_level = 0;
//parent::try_authorize();
//}
//
//protected function test_login($username, $password) {
//$this->auth_level = 0;
//if( !parent::test_login($username, $password) ) return false;
//$this->set_unauthorized();
//$this->auth_level = 1;
//
//// Check user database for the given NetID
//$db = get_db();
//$username = strtolower($username);
//$db->query("select USER_ID, USER_NAME, AUTH_ID_FK, EMAIL from PARKING.GR_USER, PARKING.GR_DEPARTMENT where NETID = '$username'");
//if(!$db->num_rows()) return false;
//
//$this->user_id = $db->get_from_top('USER_ID');
//$this->user_name = $db->get_from_top('USER_NAME');
//$this->authorization = $db->get_from_top('AUTH_ID_FK');
////$this->customer_id = $db->get_from_top('DEPT_NO_FK');
////$this->customer = $db->get_from_top('DEPT_NAME');
//$this->email = $db->get_from_top('EMAIL');
//
//$db->query("select DEPT_NO_FK from PARKING.GR_USER_DEPARTMENT where USER_ID_FK = $this->user_id");
//$depts = array();
//foreach($db->get_results() as $result)
//$depts[] = "'" . $result['DEPT_NO_FK'] . "'";
//$this->customer_id = implode(',',$depts);
//
//$this->auth_level = 2;
//
//if($this->authorization < $this->required_level)
//throw new error('Access Denied', 'You are not authorized to use this part of the site.');
//$this->auth_level = 3;
//
//$db->execute("update PARKING.GR_USER set LAST_LOGIN = sysdate where USER_ID = $this->user_id");
//
//return true;
//}
//
//function get_user_id() {
//return $this->user_id;
//}
//
//function get_user_name() {
//return $this->user_name;
//}
//
//function get_authorization() {
//return $this->authorization;
//}
//
//function get_customer_id() {
//return $this->customer_id;
//}
//
//function get_customer() {
//return $this->customer;
//}
//
//function get_display_name() {
//return $this->get_user_name();
//}
//
//function get_email() {
//return $this->email;
//}
//
//function get_signup() {
//if($this->auth_level == 1 and $this->required_level == 2) return '/parking/garage-reservation/request_access.php';
//return null;
//}
//
//function at_signup() {
//if($_SERVER['PHP_SELF'] == $this->get_signup()) return true;
//return false;
//}
//}
?>