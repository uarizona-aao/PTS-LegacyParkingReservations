<?php
/*
 * form_steps.php
 * Track the steps in a multiple-part form
 * A form must define a steps_NAME class which defines $form_name, $steps and $descriptions arrays, and the $exit_page
 * Functions called for each form are: (where NAME is the process name and n is the step number)
 * * get_NAME_n    - return the form contents
 * * verify_NAME_n - throw a step_exception (to stay on the same step) or fatal_step_exception on failure
 * * submit_NAME_n - process the form if necessary
 */
class form_steps {
    protected $form_name;
    protected $steps;
    protected $descriptions;
    protected $exit_page = 'index.php';
    private $current_step = 1;
    private $step_error = null;
    private $fatal_error = null;
    private $forms = array();
    private $storage;

    function __construct() {
    }

    // Remove errors when restored from session
    function __wakeup() {
        $this->step_error = null;
    }

    // Return the form at the current step
    function get_current_form() {
        if(isset($_GET['cancel'])) {
            $this->erase();
            url::redirect($this->exit_page);
        }
        else if(isset($_GET['goback'])) {
            $target = $_GET['goback'];
            if($this->current_step == $target+1) $this->current_step = $target;
            $this->save();
        }

        // Stop everything if there's a fatal error.
        if($this->fatal_error) return document::get_error($this->fatal_error);

        return $this->get_form($this->current_step);
    }

    // Return the form before the current step
    function get_previous_form() {
        if($this->current_step == 1)
            throw new Exception('Cannot call get_previous_step from first step');
        return $this->get_form_cache($this->current_step - 1);
    }

    // Returns true if the form has been cached
    function form_cached($step) {
        return (isset($this->forms[$step]));
    }

    // Returns the form at the given step
    function get_form($step) {
        if(!isset($_GET['goback'])) $this->cache_form($step);
        return $this->get_form_cache($step);
    }

    // Saves the form at the given step into the cache
    function cache_form($step) {
        $func =  'get_'.$this->form_name.'_'.$step;
        $this->forms[$step] = $func($this);
        $this->save();
    }

    // Returns the cached version of the form at the given step
    function get_form_cache($step) {
        return $this->form_cached($step) ? $this->forms[$step] : null;
    }

    // Returns the cached version of the current form
    function get_current_form_cache() {
        return $this->get_form_cache($this->current_step);
    }

    // Called when the form is submitted
    function submit_current_form() {
        // Accept "OK" button from fatal error and cancel the form
        if($this->fatal_error) {
            if($_POST['submit_button'] == 'OK') {
                $this->erase();
                url::redirect($this->exit_page);
            }
        }
        try {
            $form = $this->get_current_form();
            if($form instanceof form) {
                // Check that the form was submitted
                if($form->not_submitted()) return;

                // Verify the standard form parts
                foreach($form->get_items() as $item)
                    $item->update();
                if(isset($GLOBALS['form_error'])) return;
            }

            // Verify that the form passed any special conditions
            $func = 'verify_'.$this->form_name.'_'.$this->current_step;
            if(function_exists($func)) $func($this);

            // Process the form as necessary
            $func = 'submit_'.$this->form_name.'_'.$this->current_step;
            if(function_exists($func)) $func($this);

            // Exit the form if it's at the last step already
            if($this->current_step == sizeof($this->steps)) {
                $this->erase();
                url::redirect($this->exit_page);
            }

            // Advance to the next step
            $this->current_step++;
        }
        catch (fatal_step_exception $e) {
            $this->fatal_error = $e;
        }
        catch (step_exception $e) {
            $this->step_error = $e->getMessage();
        }

        $this->save();
    }

    // Stores a value for later use by the form
    function store($name, $value) {
        $this->storage[$name] = $value;
    }

    // Retrieves a value
    function retrieve($name) {
        return isset($this->storage[$name]) ? $this->storage[$name] : null;
    }

    // Returns the header, which describes the stage of the form and includes control buttons
    function get_header() {
        // Remove the header when the form has stopped in an error
        if($this->fatal_error) return '';
        $name = $this->steps[$this->current_step];
        $desc = $this->descriptions[$this->current_step];

        $xml = "<form_header current=\"$this->current_step\"><description>$desc</description>";
        if($this->step_error) $xml .= "<error>$this->step_error</error>";
        foreach($this->steps as $step => $step_name)
            $xml .= "<step number=\"$step\">$step_name</step>";
        $xml .= "</form_header>";
        return $xml;
    }

    function get_xml() {
        // since get_current_form may alter the current form, call before get_header()
        $form = $this->get_current_form();
        $xml = $this->get_header();
        if($form instanceof form) {
            // Add a hidden form step indicator
            /*
            $counter = new data('form_step', $this->current_step);
            $counter->set_renderer(new hidden_renderer());
            $form->add($counter);
            */
            $xml .= $form->get_xml();
        }
        else $xml .= method_exists($form,'get_xml') ? $form->get_xml() : $form;
        return $xml;
    }

    // Restores a process's state
    static function restore($class) {
        global $session;
        $step_obj = $session->get_object('steps');
        if(!$step_obj or !($step_obj instanceof $class)) {
            if(!class_exists($class)) throw new Exception("$class is not defined for the form.");
            $step_obj = new $class();
        }
        return $step_obj;
    }

    function save() {
        global $session;
        $session->set_object($this, 'steps');
    }

    function erase() {
        global $session;
        $session->unset_object('steps');
    }
}

class step_exception extends Exception { }

class fatal_step_exception extends error {
    private $extra;

    function __construct($title, $err, $extra = '') {
        $this->extra = $extra;
        parent::__construct($title, $err);
    }

    function get_extra() {
        return $this->extra;
    }
}
?>
