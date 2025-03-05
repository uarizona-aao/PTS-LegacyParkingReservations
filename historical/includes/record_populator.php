<?php
/*
 * record_populator.php
 * Populates a group of database_data items from one record
 */

class record_populator implements populator{
    private static $read_renderer;

    private $dataset;
    private $conditions;

    private $table;
    private $id_field;
    private $sequence;

    private $insert_extra = array();

    // Create a populator for the given collection
    // Optional parameters call set_insert_sequence()
    function __construct(collection $dataset, $table = null, $id_field = null, $sequence = null) {
        if(!isset(record_populator::$read_renderer))
            record_populator::$read_renderer = new form_display_renderer();
        $this->dataset = $dataset;
        if($table and $id_field and $sequence) $this->set_insert_sequence($table, $id_field, $sequence);
        else if($table and $id_field) {
            $this->set_id_field($id_field);
            $this->table = "PARKING.$table";
        }
    }

    // Add a SQL query condition beyond the scope of items in the dataset
    function add_condition($cond) {
        $this->conditions[] = $cond;
    }

    // Creates a query from a pool of database_data items
    private function get_query() {
        $fields = array();
        $tables = array();

        $this->get_query_recursive($this->dataset->get_items(), $fields, $tables);
        $select = implode(', ', array_unique($fields));
        $from = implode(', ', array_unique($tables));
        $query = "select $select from $from";
        if(isset($this->conditions)) $query .= ' where '.implode(' and ', $this->conditions);
        return $query;
    }

    // Recursive function to include terms from nested objects
    private function get_query_recursive($items, &$fields, &$tables) {
        foreach($items as $item) {
            // Get terms from all database_data elements
            if($item instanceof database_data) {
                $fields[] = $item->get_query_field();
                $tables[] = $item->get_table();
            }
            else if($item instanceof collection)
                $this->get_query_recursive($item->get_items(), $fields, $tables);
        }
    }

    // Populate data items with database query results
    function populate() {
        $db = get_db();

        // Only 1 result row allowed
        if($db->query($this->get_query()) != 1) return false;

        $results = $db->get_results();
        $results = $results[0];

        // Reference items by field
        $item_fields = array();
        $this->get_item_fields($this->dataset->get_items(), $item_fields);

        foreach($results as $field => $value) {
            $obj = $item_fields[$field];
            $obj->set_value($value, false);
        }

        return true;
    }

    // Builds an array of FIELDNAME => data_object
    private function get_item_fields($items, &$item_fields) {
        foreach($items as $item) {
            if($item instanceof database_data) $item_fields[$item->get_field()] = $item;
            else if($item instanceof collection) $this->get_item_fields($item->get_items(), $item_fields);
        }
    }

    // Sets up the populator to insert new values using a sequence
    function set_insert_sequence($table, $id_field, $sequence) {
        $this->table = "PARKING.$table";
        $this->id_field = $id_field;
        $this->sequence = "PARKING.$sequence";
    }

    function no_sequence() {
        $this->sequence = null;
    }

    // Inserts $value into $field when insert() is called
    function set_insert_extra($field, $value) {
        $this->insert_extra[$field] = $value;
    }

    // Returns the value to be inserted in $field when insert() is called
    function get_insert_extra($field) {
        return $this->insert_extra[$field];
    }

    function set_id_field($id_field) {
        $this->id_field = $id_field;
    }

    // Inserts a new row into the database
    function insert($get_id = false) {
        $db = get_db();

        if($this->sequence) $this->set_insert_extra($this->id_field, "$this->sequence.NEXTVAL");
        $field_value = $this->get_fields_values();

        $fields = implode(', ', array_keys($field_value));
        $values = implode(', ', $field_value);

        $db->execute("insert into $this->table ($fields) values ($values)");

        if($get_id and $this->sequence) {
            $db->query("select $this->sequence.CURRVAL NUM from DUAL");
            return $db->get_from_top('NUM');
        }
    }

    // Updates an existing row in the database
    function update($id) {
        if(!isset($this->id_field)) throw new Exception('Must define the ID field before updating a row.');

        $field_value = $this->get_fields_values();

        $statement = array();
        foreach($field_value as $field => $value)
            $statement[] = "$field = $value";
        $statement = implode(', ', $statement);

        $db = get_db();
        $db->execute("update $this->table set $statement where $this->id_field = $id");
    }

    // Deletes a row in the database where $id_field = $id
    function delete($id) {
        $db = get_db();
        if(!isset($this->id_field)) throw new Exception('Must define the ID field before deleting a row.');
        $db->execute("delete from $this->table where $this->id_field = $id");
    }

    // Returns an array of (database field => value)
    function get_fields_values() {
        $fields = array();
        foreach($this->dataset->get_items() as $item) {
            if(!$item instanceof database_data) continue;
            if($item->get_renderer() instanceof form_display_renderer) continue;

            // Resolve items that have key/value matches on a foreign key
	    if($item instanceof foreign_key) {
                $fields[$item->get_key_field()] = $item->get_key_value();
            }
            else
		$fields[$item->get_field()] = $item->get_database_value();
        }

        // Add extra rows from other sources
        foreach($this->insert_extra as $field => $value)
            $fields[$field] = $value;

        return $fields;
    }

    // Set renderers of nested items to read-only
    function set_readonly() {
        foreach($this->dataset->get_items() as $item) {
            $r = $item->get_renderer();
            if($r instanceof form_raw_renderer or $r instanceof button_renderer) continue;
            else if($r instanceof checkbox_renderer) $item->set_renderer(new boolean_renderer());
            else $item->set_renderer(record_populator::$read_renderer);
        }
    }
}
?>
