<?php
/*****************************************************
 *
 * Called from top.inc .php
 * SEE <body> stuff at  footer_newgr .php
 *
 */

$login = false;


if (isset($_GET['logout']) && isset($_SESSION['cuinfo'])) unset($_SESSION['cuinfo']);

ob_start();
?>