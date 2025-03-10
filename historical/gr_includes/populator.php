<?php
/*
 * populator.php
 * A "populator" fills a collection object with data and nested objects if necessary
 */

interface populator {
    function populate();
}

require_once '/var/www2/include/gr/record_populator.php';
require_once '/var/www2/include/gr/recordset_populator.php';
?>