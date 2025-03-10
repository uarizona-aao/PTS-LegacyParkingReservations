<?php
/*
 * data_validator.php
 * Called by a data object. Checks data for proper format and content.
 */

class data_validator {
    protected $regular_expression;
    protected $max_chars;
    protected $required;
    protected $error_message;

    function __construct($regex = '', $max_chars = 0, $required = false, $err = 'Invalid:') {
        if($regex) $this->set_regular_expression($regex);
        if($max_chars) $this->set_max_chars($max_chars);
        if($required) $this->set_required();
        $this->set_error_message($err);
    }

    function set_regular_expression($regex) {
        $this->regular_expression = $regex;
    }

    function set_max_chars($max) {
        $this->max_chars = $max;
    }
    function get_max_chars() {
        return $this->max_chars;
    }

    function set_required($value = true) {
        $this->required = $value;
    }

    function is_required() {
        return $this->required;
    }

    function set_error_message($msg) {
        $this->error_message = $msg;
    }
    function get_error_message() {
        return $this->error_message;
    }

    function validate($value) {
        if($this->is_required() and (!$value and $value !== 0 and $value !== '0'))
            throw new required_data_exception("Required:");

        if(isset($this->regular_expression) and ($this->is_required() or $value)) {
            $matches = preg_match($this->regular_expression, $value);
            if(!$matches) throw new invalid_data_exception($this->get_error_message());
        }

        if(isset($this->max_chars)) {
            if(strlen($value) > $this->max_chars) throw new invalid_data_exception("Maximum of $this->max_chars characters:");
        }
    }
}

class short_date_validator extends data_validator {
    private $earliest_timestamp = null;

    // Usually "today" or "tomorrow"
    function __construct($earliest = null) {
        //parent::__construct('/^\d{1,2}\/\d{1,2}\/\d{2}$/', 8, true, 'Format: MM/DD/YY');
		parent::__construct('/^\d{1,2}\/\d{1,2}\/\d{2}$/', 8, true, 'Format: MM/DD/YY');
        if($earliest) $this->earliest_timestamp = strtotime($earliest);
    }

    function validate($value) {
        parent::validate($value);
        if(!$value) return;

        list($month,$day,$year) = explode('/', $value);
        if(!checkdate($month, $day, $year))
            throw new invalid_data_exception('Invalid date');

        $value_timestamp = mktime(0,0,0,$month,$day,$year);
        if($this->earliest_timestamp and $value_timestamp < $this->earliest_timestamp) {
            $early = date('m/d/y', $this->earliest_timestamp);
            throw new invalid_data_exception("Not before $early");
        }
    }
}

class time_validator extends data_validator {
    function __construct() {
        parent::__construct('/^\d{1,2}:?\d{2} ?(am|pm)$/i', 8, true, 'Format: HH:MM am');
    }

    function validate($value) {
        parent::validate($value);
        if(!$value) return;

        // Extract hour and minute components
        if(strstr($value, ':')) {
            list($hours,$minutes) = explode(':', $value);
            $minutes = substr($minutes, 0, 2);
        }
        else {
            if(preg_match('/^(\d{1,2})(\d{2})/', $value, $result))
                list($dummy,$hours,$minutes) = $result;
        }

        if($hours > 12 or $minutes > 59) throw new invalid_data_exception('Invalid Time:');
    }
}

class number_validator extends data_validator {
    private $min, $max;

    function __construct($length = 0, $required = false) {
        parent::__construct('/^\d+$/', $length, $required);
    }

    function set_min($min) {
        $this->min = $min;
    }

    function set_max($max) {
        $this->max = $max;
    }

    function validate($value) {
        parent::validate($value);
        if(!$value and $value !== 0 and $value !== '0') return;

        if(isset($this->min) and $value < $min) throw new invalid_data_exception("No less than $min:");
        if(isset($this->maz) and $value > $max) throw new invalid_data_exception("No more than $max:");
    }
}

class invalid_data_exception extends Exception { }
class required_data_exception extends Exception { }
?>
