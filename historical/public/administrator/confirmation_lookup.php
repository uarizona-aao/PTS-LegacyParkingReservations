<h1>Confirmation Number Lookup</h1>
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

<dynamic content="search"/>

<?php
function get_search() {
    $form = new form();
    $field = new data('Confirmation Number');
    $field->set_renderer(new field_renderer());
    $form->add($field);
    $form->add(field_factory::get_item_row(new collection(field_factory::get_button('Search', false), field_factory::get_button('Go Back', false, 'index.php'))));
    return $form;
}

function submit_search() {
    $num = $_POST['Confirmation_Number'];
    url::redirect("reservation.php?id=$num");
}
?>
