<?php
/*
 * data_item_interface.php
 * Standard interface for all selectable data_item instances
 */

interface data_item_interface {
    function select();
    function deselect();
    function is_selected();
}
?>
