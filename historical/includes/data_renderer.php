<?php
/*
 * data_renderer.php
 * Called by a data object. Creates XML from the data object's key attributes.
 * Biased toward HTML output
 */

interface data_renderer {
    function get_xml($data);
}

/*
 * A text-entry field, e.g. HTML <input>
 */
class field_renderer implements data_renderer {
    private $display_size = 0;
    private $leading_text;
    private $trailing_text;
    private $is_password = false;
    private $nolabel = false;
    private $nodefault = false;

    function __construct($size = 0) {
        if($size) $this->set_display_size($size);
    }

    function set_display_size($size) {
        $this->display_size = $size;
    }

    function set_leading_text($text) {
        $this->leading_text = $text;
    }

    function set_trailing_text($text) {
        $this->trailing_text = $text;
    }

    function set_password() {
        $this->is_password = true;
    }

    function set_nolabel() {
        $this->nolabel = true;
    }

    function set_nodefault() {
        $this->nodefault = true;
    }

    function get_xml($data) {
        $name = $data->get_name();
        $max_chars = $data->get_max_chars();
        $contents = $this->nodefault ? '' : $data->get_editable_value();
        $error = $data->get_error();
        $note = $data->get_note();

        $xml = "<field name=\"$name\"";

        $size = 0;
        if($this->display_size) $size = $this->display_size;
        else if($max_chars) $size = $max_chars;
        if($size) $xml .= " size=\"$size\"";

        if($max_chars) $xml .= " maxlength=\"$max_chars\"";

        if($error) $xml .= " error=\"$error\"";
        else if($note) $xml .= " note=\"$note\"";
        if(isset($this->leading_text)) $xml .= " leading_text=\"$this->leading_text\"";
        if(isset($this->trailing_text)) $xml .= " trailing_text=\"$this->trailing_text\"";
        if($this->is_password) $xml .= ' password="true"';
        if($this->nolabel) $xml .= ' nolabel="true"';
        $xml .= '>';

        $xml .= "$contents</field>";
        return $xml;
    }
}

/*
 * A drop-down menu, radio group, or other set of items
 */
class list_renderer implements data_renderer {
    protected $type;
    private $select_box_size = null;
    private $multiple = false;
    private $empty_note = null;

    // Note that passing a second argument (as an array) allows any other type of list as well, and will be marked up as such
    function __construct($type, $types = array('menu', 'select_box', 'radio_group', 'grid')) {
        if(!in_array($type, $types))
            throw new Exception("Cannot use $type as a list_renderer: must be ".implode(', ', $types));

        // type select_box is a convenience for a sized menu
        if($type == 'select_box') {
            $type = 'menu';
            $this->set_select_box();
        }

        $this->type = $type;
    }

    // Sets the select box size, i.e. turn a menu into a select list
    function set_select_box($size = 6) {
        $this->select_box_size = $size;
    }

    // Whether the list is a multi-select list (i.e. not a drop-down menu)
    function set_multiple() {
        $this->multiple = true;
    }

    // Sets a note to display when the list is empty
    function set_empty_note($note) {
        $this->empty_note = $note;
    }

    function is_radio() {
        return ($this->type == 'radio_group');
    }

    function is_multi() {
        return $this->multiple;
    }

    function get_xml($data) {
        $name = $data->get_name();

        $xml = "<$this->type";
        if($name) $xml .= " name=\"$name\"";
        if($this->select_box_size and $this->type == 'menu') $xml .= " size=\"$this->select_box_size\"";
        if($this->multiple and $this->type == 'menu') $xml .= ' multiple="multiple"';
        if($this->empty_note and $this->type == 'menu') $xml .= " empty_note=\"$this->empty_note\"";
        $xml .= ">";
        $xml .= $data->get_value()->get_xml();
        $xml .= "</$this->type>";
        return $xml;
    }
}

/*
 * One row in a grid (table)
 */
class grid_row_renderer extends list_renderer {
    function __construct($type = 'row') {
        parent::__construct($type, array('row', 'heading'));
    }

    function get_xml($data) {
        $xml = "<$this->type>";
        $xml .= $data->get_value()->get_xml('item');
        $xml .= "</$this->type>";
        return $xml;
    }
}

/*
 * One item in a list, e.g. menu
 */
class list_item_renderer implements data_renderer {
    function get_xml($data) {
        $name = $data->get_formatted_name();
        $value = $data->get_formatted_value();
        if(!$value) $value = $name;

        $xml = "<item value=\"$value\"";
        if($data->is_selected()) $xml .= ' selected="selected"';
        $xml .= ">$name</item>";

        return $xml;
    }
}

/*
 * A check box or radio button. Only distinguished in concrete classes by the $type variable
 */
abstract class onoff_renderer implements data_renderer {
    protected $type;
    private $ownrow;
    private $show_name;
    private $note;

    function __construct($show_name = true, $ownrow = true, $note = null) {
        $this->show_name = $show_name;
        $this->ownrow = $ownrow;
        $this->note = $note;
    }

    function get_xml($data) {
        $name = $data->get_name();
        $value = $data->get_value();
        $xml = "<$this->type name=\"$name\" value=\"$value\"";
        if($this->ownrow) $xml .= ' ownrow="true"';
        if(!$this->show_name) $xml .= ' nameless="true"';
        if($data->is_selected()) $xml .= ' selected="selected"';
        if($this->note) $xml .= " note=\"$this->note\"";
        $xml .= '/>';

        return $xml;
    }
}

class checkbox_renderer extends onoff_renderer {
    protected $type = 'checkbox';
}

class radiobutton_renderer extends onoff_renderer {
    protected $type = 'radiobutton';
}

/*
 * A large text entry box, e.g. HTML textarea
 */
class textarea_renderer implements data_renderer {
    protected $columns;
    protected $rows;

    function __construct($col = 40, $row = 4) {
        $this->columns = $col;
        $this->rows = $row;
    }

    function get_xml($data) {
        $name = $data->get_name();
        $value = $data->get_formatted_value();
        $error = $data->get_error();

        $xml = "<textarea name=\"$name\" cols=\"$this->columns\" rows=\"$this->rows\"";
        if($error) $xml .= " error=\"$error\"";
        $xml .= ">$value</textarea>";
        return $xml;
    }
}

/*
 * A button, usually HTML form "submit"
 */
class button_renderer implements data_renderer {
    private $formrow;
    private $type = 'submit';
    private $img;
    private $goto;

    function __construct($formrow = true, $goto = NULL) {
        $this->formrow = $formrow;
        if($goto) $this->set_goto($goto);
    }

    function set_image($img) {
        $this->img = $img;
        $this->type = 'image';
    }

    // Set the button to redirect to a different URL (handled in XSL and document.php)
    function set_goto($goto) {
        $this->goto = $goto;
    }

    function get_xml($data) {
        $name = $data->get_name();
        $xml = "\n<button type=\"$this->type\" name=\"$name\"";
        if($this->formrow) $xml .= " ownrow=\"true\"";
        if(isset($this->img)) $xml .= " src=\"$this->img\"";
        if(isset($this->goto)) {
            $gotoname = 'form_goto_'.strtr(strtolower($name), ' ', '_');
            $xml .= " goto=\"$this->goto\" goto_name=\"$gotoname\"";
        }
        $xml .= "/>";
        return $xml;
    }
}

/*
 * Generic renderer for an arbitrary XML tag
 */
class tag_renderer implements data_renderer {
    private $tag;

    function __construct($tag) {
        $this->tag = $tag;
    }

    function get_xml($data) {
        $name = $data->get_name();
        $xml = "<$this->tag>$name</$this->tag>";
        return $xml;
    }
}

/*
 * Simple output of flat text with a label
 */
class form_display_renderer implements data_renderer {
    function get_xml($data) {
        $name = $data->get_name();
        $value = $data->get_formatted_value();
        $xml = "<form_display name=\"$name\">$value</form_display>";
        return $xml;
    }
}

/*
 * Yes/No display for boolean values
 */
class boolean_renderer implements data_renderer {
    function get_xml($data) {
        $name = $data->get_name();
        $value = $data->get_value() ? 'Yes' : 'No';
        $xml = "<form_display name=\"$name\">$value</form_display>";
        return $xml;
    }
}

/*
 * Raw XML output in a row of a form; no label
 */
class form_raw_renderer implements data_renderer {
    function get_xml($data) {
        $value = $data->get_formatted_value();
        return "<form_raw>$value</form_raw>";
    }
}

/*
 * A HTML link
 */
class link_renderer implements data_renderer {
    private $target;

    function __construct($target = NULL) {
        if($target == NULL) $target = $_SERVER['PHP_SELF'];
        $this->target = $target;
    }

    function get_xml($data) {
        return "<a href=\"$this->target\">".$data->get_formatted_value()."</a>";
    }
}

/*
 * A HTML hidden form input
 */
class hidden_renderer implements data_renderer {
    function get_xml($data) {
        $name = $data->get_name();
        $value = $data->get_value();
        return "<input type=\"hidden\" name=\"$name\" value=\"$value\"/>";
    }
}
?>
