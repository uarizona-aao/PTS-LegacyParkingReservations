<?php
require_once '/var/www2/include/gr/document_int.php';
require_once '/var/www2/include/gr/data_int.php';
require_once '/var/www2/include/gr/session.php';

global $session;
$session = new session();

// Begin buffering to capture the entire page contents
ob_start();

?>