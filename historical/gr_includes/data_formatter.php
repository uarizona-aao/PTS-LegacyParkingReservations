<?php
/*
 * data_formatter.php
 * Used by a data object. Formats input and output data in a specific way.
 */

// Basic pattern for all formatters
abstract class data_formatter {
    function format($value) {
	return $value;
    }

    function database_format($value) {
	return $value;
    }
}

class money_formatter extends data_formatter {
    function format($value) {
        if(!$value) return '-';
        return sprintf('$%1.2f', $value);
    }
}

class date_formatter extends data_formatter {
    private $format_string;

    function __construct($format = 'short') {
        if($format == 'short') $this->format_string = 'm/d/y';
        else if($format == 'long') $this->format_string = 'F j, Y';
    }

    function format($value) {
        if(!$value) return '';
        $timestamp = strtotime($value);
        return date($this->format_string, $timestamp);
    }

    function database_format($value) {
        if(!$value) return '';
	$timestamp = strtotime($value);
	return strtoupper(date('d-M-Y', $timestamp));
    }
}

class time_formatter extends data_formatter {
    function format($value) {
        if(!$value) return '';

        // Extract hour and minute components
        // (Shared code with time_validator)
        if(strstr($value, ':')) {
            list($hour,$minute) = explode(':', $value);
            $minute = substr($minute, 0, 2);
        }
        else {
            if(preg_match('/^(\d{1,2})(\d{2})/', $value, $result))
                list($dummy,$hour,$minute) = $result;
        }
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        
        $ampm = substr($value, -2);

        return "$hour:$minute $ampm";
    }

    function database_format($value) {
        // Extract hour and minute components
        // (Shared code with time_validator)
        if(strstr($value, ':')) {
            list($hour,$minute) = explode(':', $value);
            $minute = substr($minute, 0, 2);
        }
        else {
            if(preg_match('/^(\d{1,2})(\d{2})/', $value, $result))
                list($dummy,$hour,$minute) = $result;
        }
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);

        // Database-centric part
        $ampm = substr($value, -2, 2);
        $date = strtoupper(date('d-M-Y') . " $hour:$minute:00 $ampm");
        return "to_date('$date', 'DD-MON-YYYY HH:MI:SS AM')";
    }
}

class netid_formatter extends data_formatter {
    function format($netid) {
        return "$netid@email.arizona.edu";
    }
}

class wrap_formatter extends data_formatter {
    function format($value) {
        return wordwrap($value, 60, '<br/>');
    }
}
?>
