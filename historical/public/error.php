<?php

if (!isset($_GET['type'])) header("Location: index.php");

$errors = array(
	"resallowed"=>"You are not allowed to view this reservation. Only the user who created the reservation may edit it.",
	"resnotfound"=>"The reservation was not found. Please try again."
);

if (isset($errors[$_GET['type']])) echo '<p class="warning" align="center">'.$errors[$_GET['type']]."</p>\n\n";
?>