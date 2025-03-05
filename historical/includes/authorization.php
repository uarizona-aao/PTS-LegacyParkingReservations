<?php
/*
 * authorization.php
 * Base class for all user-authorization routines
 */

abstract class authorization {
	private $username;
	private $authorized = false;
	private $failed = false;

	// Customizable variables for the appearance of the sign-in box, should be set in set_variables()
	protected $form_name = 'Authorization Required';
	protected $username_label = 'Username';
	protected $password_label = 'Password';
	protected $button_name = 'Sign In';

	// As a convenience, the optional argument to the constructor will be passed along to try_authorize()
	function __construct($arg = null) {
		$this->set_names();
		$this->try_authorize($arg);
	}

	function get_username() {
		return $this->username;
	}

	// Returns whether the current page is authorized to see
	// Also returns true if the user is not authorized, but is allowed to see a sign-up page
	function is_authorized() {
		if($this->at_signup()) return true;
		return $this->authorized;
	}

	// Called on every restricted page load
	// Attempts to authorize the current user, assuming that they have just submitted the sign-in form
	// Otherwise, if the user is already authorized, checks for sign-out and exits
	function try_authorize() {
		if($this->is_authorized()) {
			if(isset($_GET['signout'])) {
				$this->set_unauthorized();
				$this->failed = false;
				$this->save();

				// Stop any session-enabled form in progress
				$GLOBALS['session']->unset_object('steps');
				//jjj If problems, the might try $GLOBALS['session']->erase();

			}
			return;
		}

		// Get username and password from HTTP POST
		$user = strtr($this->username_label, ' ', '_');
		$username = isset($_POST[$user]) ? $_POST[$user] : '';
		$pass = strtr($this->password_label, ' ', '_');
		$password = isset($_POST[$pass]) ? $_POST[$pass] : '';

		if($username and $password) {
			$this->set_username($username);
			if( $this->test_login($username, $password) ) $this->set_authorized();
			else $this->failed = true;
		}
		else $this->failed = false;

		// This should be the last change the object will go through, so save it back into the session
		$this->save();
	}

	protected function set_username($username) {
		$this->username = $username;
	}

	protected function set_authorized() {
		$this->authorized = true;
	}

	protected function set_unauthorized() {
	  $this->authorized = false;
	}

	function save() {
	  global $session;
	  $session->set_object($this, 'auth');
	}

	// Returns the sign-in box requesting a username and password
	// Also redirects to a new user signup page if necessary
	function get_xml() {
		$signup = $this->get_signup();
		if($signup) url::redirect($signup);
		else if($this->at_signup()) url::redirect('index.php');

		$form = new form($this->form_name);

		if($this->failed) $form->add(field_factory::get_note($this->get_error()));

		$form->add($this->get_username_field());
		$form->add(field_factory::get_password_field($this->password_label));

		$form->add(field_factory::get_note('<authorization/>'));

		$submit = new data($this->button_name);
		$submit->set_renderer(new button_renderer());
		$form->add($submit);

		return $form->get_xml();
	}

	// Returns a default username field for the sign-in box
	protected function get_username_field() {
		$username_field = new data($this->username_label);
		$username_field->set_renderer(new field_renderer());
		return $username_field;
	}

	// Returns the most display-friendly version of the current user's name, not necessarily the "username"
	function get_display_name() {
		return $this->username;
	}

	// Returns true only if there is a new user signup form
	function needs_signup() {
	  return false;
	}

	// Returns the location of the new user signup form
	function get_signup() {
	  return null;
	}

	// Returns true only if the current page is the new user signup form
	function at_signup() {
	  return false;
	}

	// Should be used to set up the custom sign-in box variables (listed at the top)
	protected abstract function set_names();

	// Should verify the username/password pair and return true or false
	protected abstract function test_login($username, $password);

	// Should return an error message when the username/password test fails
	protected abstract function get_error();

	// Returns the proper (saved or new) authorization object given a class name ($type)
	static function get_auth($type, $level = null) {
		global $session;
		if($type) {
			if(class_exists($type)) {
				$auth = $session->get_object('auth');
				if($auth and $auth instanceof $type) $auth->try_authorize($level);
				else $auth = new $type($level);

				return $auth;
			}
		} else {
			return $session->get_object('auth');
		}
		return null;
	}
}
?>
