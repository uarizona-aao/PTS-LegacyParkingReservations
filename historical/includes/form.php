<?php
/*
 * form.php
 * A container for a standard set of form elements
 */

require_once '/var/www2/include/gr/form_steps.php';
require_once '/var/www2/include/gr/collection.php';
require_once '/var/www2/include/gr/url.php';

class form extends collection {
    private $name;
    private $action;
    private $resubmit_tested = false;

    function __construct($name = null) {
        $this->name = $name;
        $this->set_action();
    }

    function get_name() {
        return $this->name;
    }

    function set_action($page = null) {
        if ($page=='/administrator/index.php') $this->action = $page;
		else {
			$u = new url($page);
			$u->remove('cancel', 'goback');
			$this->action = $u->get_url();
		}
    }

    function not_submitted() {
        return (!isset($_POST['form_submitted']) or $_POST['form_submitted'] != $this->name);
    }

    // Test for form submission or re-submission of STATELESS forms
    // Note that this FAILS if the form is restored (from session) rather than re-created on load
    function test_resubmit() {
        if($this->resubmit_tested) return;
        $this->resubmit_tested = true;

        // Fail if this particular form was not submitted
        if($this->not_submitted()) {
            unset($_SESSION['post']);
            unset($_SESSION['post_times']);
            return;
        }

        // Fail if there are validation errors
        $this->update();
        if(isset($GLOBALS['form_error'])) {
            unset($_SESSION['post']);
            unset($_SESSION['post_times']);
            return;
        }

        // Fail if the form was re-submitted
        if(isset($_SESSION['post']) and $_SESSION['post'] == $_POST['form_submitted']) {
            $_SESSION['post_times']++;
            return;
        }

        $_SESSION['post'] = $_POST['form_submitted'];
        $_SESSION['post_times'] = 1;
    }

    // Returns true if the [stateless] form has been submitted for the first time
    function is_submitted() {
        $this->test_resubmit();
        return (isset($_SESSION['post_times']) and $_SESSION['post_times'] == 1);
    }

    // Returns true if the [stateless] form has been multiply-posted
    function is_resubmitted() {
        $this->test_resubmit();
        return (isset($_SESSION['post_times']) and $_SESSION['post_times'] > 1);
    }

    // Test whether the submit button used last was for the current form
    function button_is($name) {
        $button = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
        return ($name == $button);
    }

    function get_xml() {
        $xml = "\n<form";
        if($this->name) $xml .= " name=\"$this->name\"";
        $xml .= " action=\"$this->action\">";
        $xml .= parent::get_xml();
        $xml .= "\n</form>\n";
        //$xml .= "<script language=\"Javascript\"><![CDATA[function myAlert() {window.status=\"Welcome to my homepage\";}]]></script>";
        return $xml;
    }
}
?>
