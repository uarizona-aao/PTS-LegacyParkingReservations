<?php
//jody 20151014 - added entire php block:
if (isset($_SESSION['cuinfo'])) {
if ($_SESSION['cuinfo']['netid']) { ?>
 <div style="position:absolute; right:40px; top:125px; font-weight:bold; padding:5px; background-color:#EEEEEE; color:#336666;">
	Signed in as <?php echo $_SESSION['cuinfo']['netid'] ?>
	(<a href="?logout=1">Logout</a>)</div>
<?php } } ?>


<?php
// Get the page that was just loaded
//if (@$_SERVER['REMOTE_ADDR'] == '128.196.6.66') echo '~~~~~~~~~~~~ ob_get_clean '.__FILE__.'<br>';
$xml = ob_get_clean();
echo $xml;
// exit;
$doc = new document($xml);
echo $doc->get_transformed_xml();

?>