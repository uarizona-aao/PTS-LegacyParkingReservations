<?php
/*
 * data_decorator.php
 * Allows the decorated classes below to be "made" from a basic data class.
 */

class data_decorator implements data_interface, data_item_interface {
    private $data;

    /*
    // This is the "smart" way to create a decorator using PHP5's magic __call() method
    // However, it doesn't work properly with many layers of overridden functions
    function __call($method, $params) {
        if(!method_exists($this->data, $method))
            throw new Exception("Method $method does not exist in class data");
        return call_user_func_array(array($this->data, $method), $params);
    }
    */

    function get_name() { return $this->data->get_name(); }
    function set_name($name) { return $this->data->set_name($name); }
    function get_value() { return $this->data->get_value(); }
    function contains_collection() { return $this->data->contains_collection(); }
    function get_formatted_value() { return $this->data->get_formatted_value(); }
    function get_editable_value() { return $this->data->get_editable_value(); }
    function set_database_string() { return $this->data->set_database_string(); }
    function get_database_value() { return $this->data->get_database_value(); }
    function set_value($value, $validate = true) { return $this->data->set_value($value, $validate); }
    function validate($value = NULL) { return $this->data->validate($value); }
    function set_error($message) { return $this->data->set_error($message); }
    function reset_error() { return $this->data->reset_error(); }
    function get_error() { return $this->data->get_error(); }
    function note_validator() { return $this->data->note_validator(); }
    function set_note($note) { return $this->data->set_note($note); }
    function get_note() { return $this->data->get_note(); }
    function get_max_chars() { return $this->data->get_max_chars(); }
    function set_validator(data_validator $v) { return $this->data->set_validator($v); }
    function get_validator() { return $this->data->get_validator(); }
    function set_formatter(data_formatter $f) { return $this->data->set_formatter($f); }
    function set_renderer(data_renderer $r) { return $this->data->set_renderer($r); }
    function get_renderer() { return $this->data->get_renderer(); }
    function renderer_is_display() { return $this->data->renderer_is_display(); }
    function update() { return $this->data->update(); }

    function select() { if($this->data instanceof data_item) return $this->data->select(); }
    function deselect() { if($this->data instanceof data_item) return $this->data->deselect(); }
    function is_selected() { if($this->data instanceof data_item) return $this->data->is_selected(); }
    function get_xml() { return $this->data->get_xml(); }

    function set_data(data $data) {
        $this->data = $data;
    }
}

/*
 * Adds information about the database source
 */
class database_data extends data_decorator {
    protected $field_name;
    protected $query_field;
    protected $table_name;

    // $name: the name of a new data instance, OR an existing data object
    // $field: the name of the field in the database
    // $table: the name of the table in the database
    function __construct($name = null, $field = null, $table = null) {
        // Never constructed with a value: needs to be populated from DB
        if($name instanceof data) $this->set_data($name);
        else $this->set_data(new data($name, null));

        if($field) $this->set_field($field);
        if($table) $this->set_table($table);
    }

    function set_field($f) {
        $this->field_name = $f;
        $this->set_query_field($f);
    }

    function get_field() {
        return $this->field_name;
    }

    // Sets the field name for a query, if different from the field name itself
    // e.g. set trunc(DATE) to round to the nearest date
    function set_query_field($f) {
        $this->query_field = $f;
    }

    function get_query_field() {
        $fname = $this->get_field();
        $field = $this->query_field;
        if($field != $fname) $field .= " as $fname";

        // Add table name if known
        //$table = $this->get_table();
        //if($table) $field = "$table.$field";

        return $field;
    }

    function set_table($table) {
        $this->table_name = $table;
    }

    // PTS Database specific
    function get_table() {
        return 'PARKING.'.$this->table_name;
    }
}

/*
 * A menu representing a selection between two database tables
 */
class database_menu extends database_data implements foreign_key {
    protected $collection;
    protected $key_field;
    private $type = 'menu';
    private $multi = false;

    // Sets up the menu's contents using a collection
    function set_menu(collection $collection) {
        if($collection instanceof multi_select_group)
            $this->set_type('select_box', true);
        else if(!($collection instanceof single_select_group))
            throw new Exception('set_menu in database_menu must be called with a *_select_group');
        $this->collection = $collection;
    }

    // Returns the currently selected item; Result could be null
    function get_selection() {
        $current = $this->collection->get_selected_item();
        if($this->collection instanceof multi_select_group) return $current;
        else if($current != null) return $current->get_value();
        return null;
    }

    function select_value($value) {
        $this->collection->select_value($value);
    }

    // Returns the selected key VALUE (not name) in the menu
    function get_key_value() {
        return $this->get_database_value();
    }

    function set_key_field($field) {
	$this->key_field = $field;
    }

    function get_key_field() {
	if(!isset($this->key_field))
	    throw new Exception('Foreign key must be set for database_menu');
	return $this->key_field;
    }

    function set_type($type, $multi = false) {
        $this->type = $type;
        $this->multi = $multi;
    }

    function get_xml() {
        // Display menus set to "read-only"
        if($this->renderer_is_display()) return parent::get_xml();

        $menu = new data($this->get_name(), $this->collection);
        $r = $this->get_renderer() ? $this->get_renderer() : new list_renderer($this->type);
        if($this->multi) $r->set_multiple();
        $menu->set_renderer($r);
        $this->collection->select_name($this->get_value());
        return $menu->get_xml();
    }

    // Convenience to create and set up a new database_menu instance
    // Should be called with single_select_group or multi_select_group
    static function get_menu($name, $value_field, $table, collection $collection) {
        $menu = new database_menu($name, $value_field, $table);
        $menu->set_menu($collection);
        $menu->set_key_field($value_field);
        return $menu;
    }
}

/*
 * database_menu with a populator to fill collection contents
 */
class database_populated_menu extends database_menu {
    private $populator;
    private $constraint = null;
    private $constraint_field;

	public $init;

    // Extends set_menu to include a populator for convenience
    function set_menu(collection $collection, recordset_populator $populator) {
        parent::set_menu($collection);
        $this->populator = $populator;
    }

    // Used for constraining the contents of this menu to another menu's selection (i.e. foreign key)
    function set_constraint(database_menu $constraint, $field) {
        $this->constraint = $constraint;
        $this->constraint_field = $field;
    }

    function populate() {
        $this->populator->set_pairs();
        $this->populator->populate();
    }

    function get_xml() {
        // Filter collection contents, if applicable
        if($this->constraint) {
            $cond = "$this->constraint_field = ".$this->constraint->get_selection();
            $this->populator->set_condition($cond);
        }
        $this->populate();

        return parent::get_xml();
    }

    // Convenience function to return a new database_populated_menu that's set up properly
    static function get_menu($name, $key_field, $foreign_key_field, $value_field, $table, $populator = null, $value_order = false, $init=false) {
        // at first, populator may be a multi_select_group
        if($populator and !($populator instanceof record_populator)) {
            $list = new multi_select_group();
            $populator = null;
        }
        else $list = new single_select_group();
        $order_field = $value_order ? $value_field : $key_field;
        $data = new recordset_populator($list, $table, array($value_field, $key_field), null, $order_field);
        $data->set_components('name', 'id');

        $menu = new database_populated_menu($name, $value_field, $table);
        $menu->set_menu($list, $data);
        $menu->set_key_field($foreign_key_field);
		  $menu->init = $init;
        if($populator) {
            if(!($populator instanceof record_populator))
                throw new Exception('database_populated_menu must be called with a record_populator');
            $populator->add_condition("$key_field = $foreign_key_field");
        }
        return $menu;
    }
}

/*
 * Used with data that represents a secondary table with a foreign key in the "main" record
 * e.g. a database-driven menu
 */
interface foreign_key {
    function get_key_value();
    function get_key_field();
}
?>
