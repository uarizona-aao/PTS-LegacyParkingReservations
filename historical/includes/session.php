<?php
/*
 * session.php
 * Manages data persistence across pages - front end to PHP's session capabilities
 */

class session {
	static private $save_globals = array();

	// Session timeout in seconds
	static private $timeout = 1800;

	function __construct() {

		if (!session_id())
			session_start();

		// Track the session state
		if(isset($_SESSION['page_loads'])) $_SESSION['page_loads']++;
		else $_SESSION['page_loads'] = 0;

		// Only allow one IP to access the session
		$ip = $_SERVER['REMOTE_ADDR'];
		if(isset($_SESSION['IP'])) {
			if($_SESSION['IP'] != $ip) {
				 // The user's IP has changed from its initial value. (bad!)
				 $this->erase();
			}
		} else $_SESSION['IP'] = $ip;

			// Time session access and stop on timeout // JODY 20160620
			//if(isset($_SESSION['timer']) and mktime() - $_SESSION['timer'] > session::$timeout) {	//	 // The user timed out
			//	 $this->erase();	 $GLOBALS['session_expired'] = true;	}
			// $_SESSION['timer'] = mktime();
    }

	// Clear all data for a session
	function erase() {
		unset($_SESSION);
		session_destroy();
	}

	function set_object($object, $name) {
		$_SESSION[$name] = serialize($object);
	}

	function unset_object($name) {
		 if(isset($_SESSION[$name])) unset($_SESSION[$name]);
	}

	function get_object($name) {
		 if(isset($_SESSION[$name])) return unserialize($_SESSION[$name]);
		 return null;
	}

	function cookies_enabled() {
		 return ($_SESSION['page_loads'] > 0);
	}
}

?>
