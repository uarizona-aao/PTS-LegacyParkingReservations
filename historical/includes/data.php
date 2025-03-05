<?php
/*
 * Basic class for a discrete unit of data
 * Delegates validation, formatting, and XML rendering to helper classes
 */

require_once '/var/www2/include/gr/data_interface.php';
require_once '/var/www2/include/gr/data_item_interface.php';
require_once '/var/www2/include/gr/form.php';
require_once '/var/www2/include/gr/data_validator.php';
require_once '/var/www2/include/gr/data_formatter.php';
require_once '/var/www2/include/gr/data_renderer.php';
require_once '/var/www2/include/gr/data_decorator.php';

class data implements data_interface {
	private $name;
	private $value;

	private $validator = null;
	private $formatter = null;
	private $renderer = null;

	private $error;
	private $note;
	private $updated = false;
	private $is_string = false;

	function __construct($name = '', $value = '') {
	  if($value || $value === 0 || $value === '0') $this->set_value($value);
	  if($name) $this->set_name($name);
	}

	function __wakeup() {
	  $this->updated = false;
	}

	function get_name() {
	  return $this->name;
	}

	function get_formatted_name() {
	  return $this->format($this->get_name());
	}

	function set_name($name) {
	  $this->name = $name;
	}

	function get_value() {
	  return $this->value;
	}

	function contains_collection() {
	  return $this->get_value() instanceof collection;
	}

	function contains_group() {
	  return $this->get_value() instanceof single_select_group;
	}

	function get_formatted_value() {
	  if($this->contains_collection()) return $this->get_value()->get_xml();

	  // Format unless marked not to
	  $value = $this->get_value();
	  if($this->get_name() != 'xml') $value = $this->format($value);

	  return $value;
	}

	function format($value) {
	  $value = htmlspecialchars($value, ENT_QUOTES);

	  if($this->formatter)
	      $value = $this->formatter->format($value);

	  // Replace newline characters with <br> elements
	  $value = nl2br($value);

	  return $value;
	}

	function get_editable_value() {
	  $value = $this->get_value();
	  if($this->contains_collection()) return $value->get_xml();

	  // Replace newline characters with <br> elements
	  $value = nl2br($value);

	  return $value;
	}

	function set_database_string() {
	  $this->is_string = true;
	}

	function get_database_value() {
	$value = $this->get_value();

	  // Replace Oracle-specific characters
	  $value = strtr($value, array("'" => "''"));

	if($this->formatter) $value = $this->formatter->database_format($value);

	  // Quote everything but numbers and nulls
	  if(!$this->is_string && preg_match('/^([0-9]+|null|.+\(.+)$/', $value)) return $value;
	return "'$value'";
	}

	function set_value($value, $validate = true) {
	  if($validate) $this->validate($value);

	  // Set the value, even if it's wrong.
	  $this->value = $value;
	}

	function validate($value = NULL) {
	  // Trick to avoid validating record deletions
	  $delbtn = isset($GLOBALS['delbtn']) ? $GLOBALS['delbtn'] : 'Delete';
	  if(isset($_POST['submit_button']) && $_POST['submit_button'] == $delbtn) return;

	  if(!$value && $value !== 0 && $value !== '0') $value = $this->get_value();
	  if($this->validator) {
	      try {
	          $this->validator->validate($value);
	      } catch (invalid_data_exception $e) {
	          $this->set_error($e->getMessage());
	      } catch (required_data_exception $e) {
	          $this->set_error($e->getMessage());
	      }
	  }
	}

	function set_error($message) {
	  $this->error = $message;
	  $GLOBALS['form_error'] = true;
	}

	function reset_error() {
	  unset($this->error);
	}

	function get_error() {
	if(!isset($this->error)) return null;
	else return $this->error;
	}

	function note_validator() {
	  if(!isset($this->validator)) throw new Exception('No validator defined for note_validator');
	  $note = $this->validator->get_error_message();
	  if(substr($note, 0, 6) == 'Format') $note = substr($note, 7);
	  $this->set_note($note);
	}

	function set_note($note) {
	  $this->note = $note;
	}

	function get_note() {
	  return $this->note;
	}

	function get_max_chars() {
	if($this->validator)
	 return $this->validator->get_max_chars();
	else return null;
	}

	function set_validator(data_validator $v) {
	$this->validator = $v;
	}

	function get_validator() {
	  return $this->validator;
	}

	function set_formatter(data_formatter $f) {
	$this->formatter = $f;
	}

	function set_renderer(data_renderer $r) {
	$this->renderer = $r;
	}

	function get_renderer() {
	return $this->renderer;
	}

	function renderer_is_display() {
	return ($this->renderer instanceof form_display_renderer);
	}

	function get_xml() {
	$this->update();

	$renderer = $this->get_renderer();
	if($renderer) return $renderer->get_xml($this);
	else return $this->get_formatted_value();
	}

	// Get new data submitted by user
	/* THIS IS THE MAIN INTERFACE WITH FORM INPUT DATA */
	function update() {
		// Don't update anything if the form was not submitted or if the previous form was the sign-in page
		// form.xsl places a hidden input on each form, so it's easy to see when a form is submitted
		  // note: first two terms (a and b) originally, changed to ORs
		if (isset($_POST["form_submitted"]) && $_POST["form_submitted"]=="New user Signup") {
			$this->updated = false;
		}
		elseif (!isset($_POST['form_submitted']) || isset($_GET['goback']) || $this->updated || (isset($_POST['submit_button']) && $_POST['submit_button'] == 'Sign In')) {
			return;
		}
		$this->updated = true;
		$this->reset_error();

		// Update contained items
		if($this->contains_collection()) $this->value->update();

		$post_name = strtr($this->get_name(), ' ', '_');

		// Special case for selectors. Note that checkboxes are unlisted when off.
		if($this->renderer instanceof onoff_renderer && $this instanceof data_item) {
		      if($this->renderer instanceof radiobutton_renderer) {
		          if(isset($_POST[$post_name]) && $_POST[$post_name] == $this->get_value()) $this->select();
		          else $this->deselect();
		      }
		      else if($this->renderer instanceof checkbox_renderer && isset($_POST[$post_name])) {
		          if($_POST[$post_name] == 'on') $this->select();
		          else if($_POST[$post_name] == 'off') $this->deselect();
		      }

		 $this->validate();
		 return;
		}

		  // De-select all multiple select items
		if($this->renderer instanceof list_renderer && $this->renderer->is_multi()) {
			$contents = $this->get_value();
			$contents->deselect_all();
		}

		if(isset($_POST[$post_name])) {
			$post_value = $_POST[$post_name];

			// Arrays passed back for multiple select items
			if(is_array($post_value)) {
			    if($this->contains_group()) {
			        $items = $this->get_value();
			        foreach($post_value as $post_item)
			            $items->select_name($post_item);
			    }
			}
			else {
			    $post_value = trim(stripslashes($post_value));

			    // Remove URL encoding
			    $post_value = html_entity_decode($post_value);

			    // For a collection, select the posted item
			    if($this->contains_group()) $this->get_value()->select_name($post_value);

			    // Otherwise, update the value of this data
			    else if(!$this->contains_collection()) $this->set_value($post_value);
			}
		}
	}
}

// Single item in a list. Selectable.
// Use: new data_item('value', ['display label'])
class data_item extends data implements data_item_interface {
	private $selected = false;
	private $boolean_value = false;

	function __construct($name = '', $value = '') {
	  parent::__construct($name, $value);
	  $this->set_renderer(new list_item_renderer());
	}

	function set_renderer(data_renderer $r) {
	  $this->boolean_value = ($r instanceof checkbox_renderer);
	  parent::set_renderer($r);
	}

	function select() {
	  $this->selected = true;
	  if($this->boolean_value) $this->set_value(1);
	}

	function deselect() {
	  $this->selected = false;
	  if($this->boolean_value) $this->set_value(0);
	}

	function is_selected() {
	  return $this->selected;
	}

	function set_value($value, $validate = true) {
	  parent::set_value($value, $validate);
	  if($this->boolean_value) {
	      if($value) $this->selected = true;
	      else $this->selected = false;
	  }
	}
}
?>
