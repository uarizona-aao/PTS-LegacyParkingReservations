<h1>Find Department by KFS Number</h1>
<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once $docRoot.'/parking/garage-reservation/garage-reservation-include.php';
?>
<div id="main">
 <br />
 <div class="container" >
	<div class="row">
	 <div class="col-sm-4 col-md-4 col-lg-4 hidden-xs">
	 <?php
	 include_once $docRoot.'/parking/parking-menu-include.php';
	 ?>
	 </div>
	 <!-- end side nav menu -->
	 <div id="mainContent" class="col-sm-8 col-md-8 col-lg-8"  >
	 <ol class="breadcrumb">
		<li><a href="/">Home</a></li>
		<li><a href="/parking/">Parking & Permits</a></li>
		<li class="active">Garage Reservation</li>
	 </ol>
	 <h1  class="page-heading">Department Visitor Garage Reservation</h1>
	 <hr />
	 <div id="editableContent">


<?php
spinnerWaiting();

if ($auth < 3)
	exitWithBottom('You are not authorized.');
?>

<dynamic content="find"/>

<?php
function get_find() {
    $form = new form('Find Department');
    $form->add(field_factory::get_frs_field(true));
    $buttons = field_factory::get_item_row(new collection(field_factory::get_button('Find', false), field_factory::get_button('Done', false, 'index.php')));
    $form->add($buttons);
    $xml = $form->get_xml();

    if(isset($_POST['KFS_Number'])) {
        $frs = $_POST['KFS_Number'];
        $db = get_db();
        $db->query("select DEPT_NO, DEPT_NAME from PARKING.GR_FRS, PARKING.GR_DEPARTMENT where DEPT_NO_FK = DEPT_NO and FRS = '$frs'");
        $deptno = $db->get_from_top('DEPT_NO');
        $deptname = $db->get_from_top('DEPT_NAME');
        $xml .= "<p>FRS: <b>$frs</b><br/>Department: <b>$deptname</b><br/>Number: <b>$deptno</b></p>";
    }

    return $xml;
}
?>