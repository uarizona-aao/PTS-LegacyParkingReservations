<?php
/*
 * data_interface.php
 * Standard interface for data items, used by data and data_decorator classes
 */

interface data_interface {
    function get_name();
    function set_name($name);
    function get_value();
    function contains_collection();
    function get_formatted_value();
    function set_database_string();
    function get_database_value();
    function set_value($value, $validate = true);
    function validate($value = NULL);
    function set_error($message);
    function reset_error();
    function get_error();
    function note_validator();
    function set_note($note);
    function get_note();
    function get_max_chars();
    function set_validator(data_validator $v);
    function get_validator();
    function set_formatter(data_formatter $f);
    function set_renderer(data_renderer $r);
    function get_renderer();
    function renderer_is_display();
    function get_xml();
    function update();
}
?>
