<h1>User Editor</h1>
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

<dynamic content="detail"/>

<?php
function get_detail() {
    $userid = isset($_GET['id']) ? $_GET['id'] : null;

    // Capture dept. removal clicks
    if(isset($_GET['remove'])) {
        $remdept = $_GET['remove'];
        $db = get_db();
        $db->execute("delete from PARKING.GR_USER_DEPARTMENT where USER_ID_FK = $userid and DEPT_NO_FK = '$remdept'");
    }

    $form = new form('User Details');
    $pop = new record_populator($form, 'GR_USER', 'USER_ID', 'GR_USER_ID');

    $name = new database_data(field_factory::get_name_field(), 'USER_NAME', 'GR_USER');
    $form->add($name);

    $netid = new database_data(field_factory::get_netid_field(), 'NETID', 'GR_USER');
    $netid->get_validator()->set_required(false);
    $form->add($netid);

    $email = new database_data(field_factory::get_email_field(false, 25), 'EMAIL', 'GR_USER');
    $email->get_validator()->set_required(false);
    $form->add($email);

    $phone = new database_data(field_factory::get_phone_field('Phone', false), 'PHONE', 'GR_USER');
    $form->add($phone);

    $auth = database_populated_menu::get_menu('Authorization', 'AUTH_ID', 'AUTH_ID_FK', 'DESCRIPTION', 'GR_AUTHORIZATION', $pop);
    $form->add($auth);

    if($userid) {
        $deptlist = '<b>Department</b> (Click to remove)<br/>';
        $db = get_db();
        $db->query("select DEPT_NO, DEPT_NAME from PARKING.GR_DEPARTMENT, PARKING.GR_USER_DEPARTMENT where DEPT_NO_FK = DEPT_NO and USER_ID_FK = $userid");
        if($db->num_rows() > 1) {
            foreach($db->get_results() as $result) {
                $dept_no = $result['DEPT_NO'];
                $dept_name = preg_replace('/&([^a])/si', '&amp;$1', $result['DEPT_NAME']);
                $deptlist .= "<a href=\"?id=$userid&amp;remove=$dept_no\">$dept_name</a><br/>";
            }
            $list = new data('xml', $deptlist);
            $list->set_renderer(new form_raw_renderer());
        }
        else {
            $list = new data('Department', $db->get_from_top('DEPT_NAME'));
            $list->set_renderer(new form_display_renderer());
        }
        $form->add($list);

        $cust = field_factory::get_dept_field(false, 'Add Department', true);
        //$cust = new database_data(new data('Department'), 'DEPT_NAME', 'GR_DEPARTMENT');
        //$cust->set_renderer(new form_display_renderer());
        //$pop->add_condition('DEPT_NO_FK = DEPT_NO');
    }
    else {
        //$cust = new database_data(field_factory::get_dept_field(), 'DEPT_NO_FK', 'GR_USER');
        $cust = field_factory::get_dept_field();
    }

    $form->add($cust);

    $buttons = new collection();

    if($userid) {
        $sub = new data('Update');
        $sub->set_renderer(new button_renderer(false));
        $buttons->add($sub);
    }
    else {
        $ins = new data('Insert');
        $ins->set_renderer(new button_renderer(false));
        $buttons->add($ins);
    }

    $back = new data('Cancel');
    $back->set_renderer(new button_renderer(false, 'users.php'));
    $buttons->add($back);

    $form->add(field_factory::get_item_row($buttons));

    if($userid) {
        $pop->add_condition("USER_ID = $userid");
        $pop->populate();
    }

    $form->set_populator($pop);
    return $form;
}

function submit_detail($form) {
    $pop = $form->get_populator();
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $dept = $form->get_by_name('Add Department');
    if(!$dept) $dept = $form->get_by_name('Department Number');
    $dept = $dept->get_value();
    $submit = $_POST['submit_button'];
    $db = get_db();
    if($submit == 'Insert') {
        $pop->set_insert_extra('CREATION_DATE', 'sysdate');
        //try {
            $pop->insert();
            if ($id==null) {
				$rows = $db->query("SELECT USER_ID FROM PARKING.GR_USER WHERE NETID='".$_POST['NetID']."' AND USER_NAME='".$_POST['Name']."'");
				if ($rows) {
					$rset = $db->get_results();
					$id = $rset[0]['USER_ID'];
				}
			}
			$db->execute("insert into PARKING.GR_USER_DEPARTMENT values ($id, '$dept')");
        /*} catch(Exception $e) {
            return 'User not created. The name may already exist, or the department number may be incorrect.';
        }*/
        return 'New user created.';
    }
    else if($submit == 'Update') {
        $pop->update($id);
        if(trim($dept)) {
            try {
                $db->execute("insert into PARKING.GR_USER_DEPARTMENT values ($id, '$dept')");
            } catch(Exception $e) {
                return 'Could not add department.';
            }
        }
        return 'User information updated.';
    }
}

function get_action() {
    return 'users.php';
}
?>