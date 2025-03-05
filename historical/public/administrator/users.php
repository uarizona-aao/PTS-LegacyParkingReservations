<h1>Edit Users</h1>
<?php

//<authorization type="garage_reservation" level="4"/>
$protectPass = $dbConn->protectCheck(4);
if (!$protectPass)
	exitWithBottom('You are not authorized.');
?>

<dynamic content="users"/>

<?
function get_users() {
	$form = new form('Users');
	$users = new collection();
	$user_data = new recordset_populator
	(
		$users,
		array('GR_USER', 'GR_AUTHORIZATION',"GR_USER_DEPARTMENT"),
		array('USER_ID', 'USER_NAME',"DEPT_NO_FK", 'PHONE', 'DESCRIPTION', 'LAST_LOGIN'),
		"AUTH_ID_FK = AUTH_ID AND USER_ID_FK(+)=USER_ID",
		"AUTH_ID_FK DESC, USER_NAME"
	);
	$user_data->set_headings('id', 'Name',"Dept. No.", 'Phone', 'Authorization', 'Last Login');
	$user_data->set_components('id', 'name', 'dept', 'phone', 'auth', 'log');
	$user_data->set_id_link('name', 'user_detail.php');
	$user_data->set_select_expression('LAST_LOGIN', "to_char(LAST_LOGIN, 'DD-Mon-YY HH:MI pm')");

	$user_data->populate();
	$user_data = new data('', $users);
	$user_data->set_renderer(new list_renderer('grid'));
	$form->add($user_data);

	$buttons = new collection();

	$newuser = new data('New User');
	$newuser->set_renderer(new button_renderer(false, 'user_detail.php'));
	$buttons->add($newuser);

	$back = new data('Cancel');
	$back->set_renderer(new button_renderer(false, 'index.php'));
	$buttons->add($back);

	$form->add(field_factory::get_item_row($buttons));

	return $form;
}
?>