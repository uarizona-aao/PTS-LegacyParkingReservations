<?php
/*
 * collection.php
 * Classes to control sets of records
 * Includes classes collection, single_select_group, and multi_select_group (for selecting collection items)
 */

require_once '/var/www2/include/gr/database_pts.php';
require_once '/var/www2/include/gr/populator.php';

class collection {
    private $items = array();
    private $populator;

    function __construct() {
        $argc = func_num_args();

        // The value passed was an array to be made into list items
        // It's organized as value => name, where the key $value is optional
        if($argc == 1 and is_array(func_get_arg(0))) {
            $items = func_get_arg(0);
            foreach($items as $value => $name) {
                // Skip integer keys, which means a "value" key isn't set
                if(is_integer($value)) $value = '';
                $item = new data_item($name, $value);
                $this->add($item);
            }
        }

        // The values passed were objects to be added to the collection
        else if($argc) {
            for($i=0; $i<$argc; $i++)
                $this->add(func_get_arg($i));
        }
    }

    function add($item) {
        array_push($this->items, $item);
    }

    function get_items() {
        return $this->items;
    }

    // Sets the items of this collection equal to the items of the other collection specified
    function set_items(collection $items) {
        $this->items = $items->get_items();
    }

    // Removes the item with the given $name from the collection
    function remove_name($name) {

		//jjj 20120110 - replaced this one line with all the stuff below...
		//$this->items = array_diff($this->items, array($this->get_by_name($name)));
		$obj_k = -1;
		$allItems = $this->items;
		$tmpObj = array($this->get_by_name($name));
		foreach ($allItems as $obj_k => $obj_v) {
			$hate1 = print_r($allItems[$obj_k],true);
			$hate2 = print_r($tmpObj[0],true);
			if ($hate1 == $hate2) {
				unset($allItems[$obj_k]);
				break;
			}
		}
		unset($this->items);
		$this->items = $allItems;
    }

    // Returns the number of items in this collection
    function size() {
        return sizeof($this->items);
    }

    // Returns the first item having a given name
    function get_by_name($name) {
        return $this->match('get_name', $name);
    }

    // Returns the first item having a given value
    function get_by_value($value) {
        return $this->match('get_value', $value);
    }

    // Returns the first item in the collection, or null
    function get_first() {
        return isset($this->items[0]) ? $this->items[0] : null;
    }

    // Returns the first item where the value of item->$func() matches $value.
    private function match($func, $value) {
        foreach($this->items as $item) {
            if(!method_exists($item, $func)) continue;
            if($item->$func() == $value) return $item;
        }
        return null;
    }

    // Special function
    // Returns an array of which checkboxes in the collection were selected
    // This is very useful for listing all checkbox selections
    function get_checks() {
        global $checks;
        if(!isset($checks)) $checks = array();
        foreach($this->items as $item) {
            if($item->contains_collection() and method_exists($item->get_value(), 'get_checks')) $item->get_value()->get_checks();
            else if(substr($item->get_name(), 0, 5) == 'check' and method_exists($item, 'is_selected') and $item->is_selected())
                $checks[] = substr($item->get_name(), 6);
        }
        return $checks;
    }

    // Calls validate() on all items in the collection
    function validate() {
        if(!sizeof($this->items)) return;
        foreach ($this->items as $item)
            $item->validate();
    }

    // Calls update() on all items in the collection
    function update() {
        if(!sizeof($this->items)) return;
        foreach($this->items as $item)
            $item->update();
    }

    /*
    // Unused function
    function swap($old_item, $new_item) {
        foreach($this->items as &$item)
            if($item == $old_item) $item = $new_item;
    }
    */

    // For convenience, store a reference to an associated populator object
    function set_populator($pop) { $this->populator = $pop; }
    function get_populator() { return $this->populator; }

    // Returns an (optionally tagged) XML representation of the items
    function get_xml($tag = null) {
        if(!isset($this->items)) return '';

        $xml = '';
        foreach($this->items as $item) {
            if($tag) $xml .= "<$tag>";
            $xml .= $item->get_xml();
            if($tag) $xml .= "</$tag>";
        }
        return $xml;
    }
}

/*
 * A collection where one item may be selected
 */
//if ($GLOBALS['jody']) {
//	echo 'ccccccccccccccccccccccc<br>';
//}
class single_select_group extends collection {
    // Reference to selected item object
    private $selected_item = null;

    // Select the given item
    function select(data_item $item) {
        $this->deselect_current();
        $this->select_new($item);
    }

    // De-select the currently selected item
    protected function deselect_current() {
        $old_item = $this->get_selected_item();
        if($old_item) $old_item->deselect();
    }

    // Select the given item
    protected function select_new(data_item $item) {
        $item->select();
        $this->selected_item = $item;
    }

    // Select the first item in the list
    function select_first() {
        $item = $this->get_first();
        if($item and method_exists($item, 'select')) $this->select($item);
    }

    // Select the item with the given name
    function select_name($name) {
        $item = $this->get_by_name($name);
        if($item) $this->select($item);

        // Try select-by-value if select-by-name doesn't work
        else $this->select_value($name);
    }

    // Select the item with the given value
    function select_value($value) {
        $item = $this->get_by_value($value);
        if($item) $this->select($item);
    }

    // Returns the currently selected item
    function get_selected_item() {
        return $this->selected_item;
    }

    // Selects the first item if nothing else is selected and the item is NOT a checkbox
    function select_default() {
        $first = $this->get_first();
        if($first and $first instanceof data_item
           and !($first->get_renderer() instanceof checkbox_renderer)
           and !$this->has_selected_item())
            $this->select_first();
    }

    // Returns true if any item in the collection is "selected"
    function has_selected_item() {
        return ($this->selected_item != null);
        //return $this->match('is_selected', true);
    }

    function get_xml() {
        $this->select_default();
        return parent::get_xml();
    }
}

/*
 * A collection where multiple items may be selected
 */
class multi_select_group extends single_select_group {
    // Overrides select_new() to avoid using $selected_item (for semantic purposes)
    protected function select_new(data_item $item) {
        $item->select();
    }

    // Does nothing, because there is no current $selected_item
    protected function deselect_current() { }

    // Returns an array of all selected items in the collection
    function get_selected_item() {
        $results = array();
        foreach($this->get_items() as $item)
            if($item->is_selected()) $results[] = $item;
        return $results;
    }

    // De-selects all items in the collection
    function deselect_all() {
        foreach($this->get_items() as $item)
            $item->deselect();
    }

    // Does nothing, because the default is an empty selection
    function select_default() { }
}
?>
