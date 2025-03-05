<?php
/*
 * recordset_populator.php
 * Creates and formats a set of records, i.e. "table" format
 */

require_once '/var/www2/include/gr/field_factory.php';
require_once '/var/www2/include/gr/fieldset_factory.php';

class recordset_populator implements populator {
    // Dataset to be populated with database values
    private $dataset;

    // Ordered data values:
    private $tables;                     // Array of tables to be used
    private $fields;                     // Array of fields to be used
    private $field_select_expressions;   // Array of SQL expressions for each field
    private $condition;                  // SQL "where" clause (if any)
    private $orderby;                    // SQL "order by" clause (if any)
    private $groupby;                    // SQL "group by" clause (if any)

    // Matching the set of fields:
    private $components = array();       // Array of constructors or placeholders
    private $component_headings;         // Array of column headings

    // For creating a dynamic link using each "id" component
    private $id_link_component;          // The component to make into a link
    private $id_link_target;             // The link target
    private $id_link_param;              // The link parameters, other than id
    private $id_link_separator;          // Separator: either ? or &

    private $pairs = false;              // Are the data simple key/value pairs?
    private $heading_links = false;      // Click headings to sort?

    private $selector = null;            // Include a checkbox or radio button for each row?
    private $done = false;               // Has the populator already run?

    // Defines the collection, included tables, included fields, and optional "where" and "order by" clauses
    function __construct(collection $dataset, $tables, $fields, $condition = null, $orderby = null) {
        $this->dataset = $dataset;

        if(is_array($tables)) {
            foreach($tables as $table)
                $this->tables[] = 'PARKING.'.strtoupper($table);
        }
        else $this->tables[] = 'PARKING.'.strtoupper($tables);

        if(is_array($fields)) {
            foreach($fields as $field)
                $this->fields[] = strtoupper($field);
        }
        else $this->fields[] = strtoupper($fields);

        foreach($this->fields as $field) {
            $this->field_select_expressions[$field] = $field;
        }

        if($condition) $this->set_condition($condition);

        // Load sort commands from GET for dynamic column re-sorting
        if(isset($_GET['sort'])) {
            $field_num = $_GET['sort'];
            $this->set_order($this->fields[$field_num]);
        }
        else if($orderby) $this->set_order($orderby);
    }

    // Set SQL "where" clause
    function set_condition($where) {
        $this->condition = $where;
    }

    // Set SQL "order by" clause
    function set_order($orderby) {
        $this->orderby = $orderby;
    }

    // Set SQL "group by" clause
    function set_group($groupby) {
        $this->groupby = $groupby;
    }

    /*
     * Defines how to construct component objects.
     * The items passed to set_components are either static methods in the class field_factory
     *    or placeholder names
     * Must be in the same order as field names.
     * Syntax: set_components('get_city_field', 'get_state_field', 'dummy', etc.);
     */
    function set_components() {
        $num_components = func_num_args();
        if($num_components != sizeof($this->fields))
            throw new Exception("Call to set_components with incorrect number of components");

        for($i = 0; $i < $num_components; $i++) {
            $this->components[] = func_get_arg($i);
        }
    }

    // Set column headings, if needed. Note that this must correspond to arguments to set_components()
    function set_headings() {
        $num_headings = func_num_args();
        if($num_headings != sizeof($this->fields))
            throw new Exception("Call to set_headings with incorrect number of heading names");

        for($i = 0; $i < $num_headings; $i++)
            $this->component_headings[] = func_get_arg($i);
    }

    // Set headings to be used as sort links
    function set_heading_links() {
        $this->heading_links = true;
    }

    private function num_components() {
        $num = sizeof($this->components);
        if($this->selector) $num++;
        return $num;
    }

    /*
     * Use to create fancy SQL select expressions for a given field.
     * For example: set_select_expression("TIME", "to_char(TIME, 'HH:MI AM')")
     */
    function set_select_expression($field, $expression) {
        $this->field_select_expressions[$field] = "$expression as $field";
    }

    // Makes a component into a link
    function set_id_link($component, $target = null, $param = 'id') {
        if(!in_array($component, $this->components))
            throw new Exception('Cannot set_id_link for a component not in the collection');
        if(!in_array('id', $this->components))
            throw new Exception("Cannot set_id_link without an 'id' component");

        $this->id_link_component = $component;
        $this->id_link_target = $target ? $target : $_SERVER['PHP_SELF'];
        $this->id_link_param = $param;
        $this->id_link_separator = strpos($target, '?') ? '&amp;' : '?';
    }

    // Returns a query to populate all components in this collection
    private function get_query() {
        // List all field expressions to use
        $fields = '';
        foreach($this->field_select_expressions as $field => $expr)
            $fields .= ($fields ? ", $expr" : $expr);

        $tables = implode(', ', $this->tables);

        $query = "select $fields from $tables";
        if(isset($this->condition)) $query .= " where $this->condition";
        if(isset($this->groupby)) $query .= " group by $this->groupby";
        if(isset($this->orderby)) $query .= " order by $this->orderby";

        return $query;
    }

    // Tells the populator that records returned will be key/value pairs
    function set_pairs($val = true) {
        if($this->num_components() != 2)
            throw new Exception('num_components must be exactly 2 to set key/value pairing');
        $this->pairs = $val;
    }

    // Tells the populator to add a "selector" to each row, i.e. checkbox or radio button to select the row
    function set_selector($type) {
        if(!in_array('id', $this->components))
            throw new Exception("Cannot set_selector without an 'id' component");
        if($type != 'checkbox' and $type != 'radiobutton')
            throw new Exception("Selector must be either a checkbox or radiobutton");
        $this->selector = $type;
    }

    // Populates the collection with objects filled with database data
    function populate($pdfGarages='BioMedical,USA') {
        if($this->done) return;
        $this->done = true;

        $db = get_db();

        if($this->num_components() == 0)
            throw new Exception('Must set components before populating');

        if(!$db->query($this->get_query())) return;
        $new_data = $db->get_results();

        // Get a list of factory methods that might be used
        $factory_methods = get_class_methods('field_factory');

        // For component lists, add headings if appropriate
        if(isset($this->component_headings)) {
            $this_row = new collection();

            // Add heading space for checkbox/radiobutton
            if($this->selector) $this_row->add(new data('',''));

            foreach($this->component_headings as $num => $heading) {
                // Don't display headings called 'id' (for hidden row id information)
                if($heading == 'id') continue;

                // Create column sorting links for headings
                if($this->heading_links) {
                    $u = new url();
                    $u->set_value('sort', $num);
                    $link = $u->get_url();
                    $item = new data('xml', "<a href=\"$link\" class=\"sort_link\">$heading</a>");
                }
                else $item = new data('', $heading);
                $this_row->add($item);
            }

            $this_row = new data('', $this_row);
            $this_row->set_renderer(new grid_row_renderer('heading'));
            $this->dataset->add($this_row);
        }


        foreach($new_data as $new_row) {

				if ($_SESSION['standardUser']) {

					$pdfGarages = preg_replace('/,/si', '|', $pdfGarages);
					$makePDF = preg_match('/('.$pdfGarages.')/i', $new_row['GARAGE_NAME']);

					if (!$makePDF) {
						continue;
					}
				}

            // Create a collection of components for each row
            if($this->num_components() and !$this->pairs) {
                $this_row = new collection();
                $counter = 0;

                $row_id = 0;
                foreach($this->components as $component) {
                    // Get the ID row and save it for later, if applicable
                    if($component == 'id') {
                        $current_field = $this->fields[$counter++];
                        $row_id = $new_row[$current_field];

                        // Here we ASSUME that the ID field is FIRST when a checkbox/radiobutton appears
                        if($this->selector) {
                            if($this->selector == 'checkbox') $select = new data_item('check_'.$row_id, 0);
                            else $select = new data_item('grid_selector', $row_id);
                            $renderer = $this->selector.'_renderer';
                            $select->set_renderer(new $renderer(false, false));
                            $this_row->add($select);
                        }

                        continue;
                    }

                    if(in_array($component, $factory_methods)) {
                        // Pass the database row ID (if any) to the factory method
                        $item = field_factory::$component($row_id);
                    }
                    else {
                        $item = new data($component);

                        // Create links for specified table contents
                        if(isset($this->id_link_component) and $this->id_link_component == $component) {
                            $link = "$this->id_link_target$this->id_link_separator$this->id_link_param=$row_id";
                            $item->set_renderer(new link_renderer($link));
                        }
                    }

                    $current_field = $this->fields[$counter++];
                    $current_data = $new_row[$current_field];
                    $item->set_value($current_data);
                    $this_row->add($item);
                }

                //Package the row in a data item, so it can have a renderer
                $this_row = new data_item('', $this_row);
                $this_row->set_renderer(new grid_row_renderer());

                $this->dataset->add($this_row);
            }

            // Create a component for each row: first field is name, second field is value
            else if($this->pairs) {
                $name = $new_row[$this->fields[0]];
                $value = isset($this->fields[1]) ? $new_row[$this->fields[1]] : '';
                $new_item = new data_item($name, $value);
                $this->dataset->add($new_item);
            }
        }

        // Return the number of rows created
        return $this->dataset->size();
    }
}
?>
