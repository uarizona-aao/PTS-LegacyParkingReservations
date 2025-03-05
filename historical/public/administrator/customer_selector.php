<?php
function get_cust() {
    $form = new form('Select a Department');
    if(isset($GLOBALS['custformaction']))
			$form->set_action($GLOBALS['custformaction']);

    if(isset($GLOBALS['owing'])) {
        $before = $GLOBALS['owing'];
        $tables = array('GR_DEPARTMENT', 'GR_RESERVATION');
        $conditions = "DEPT_NO_FK = DEPT_NO and PAYMENT_ID_FK is null and RES_DATE < sysdate - $before and ACTIVE = 1";
    }
    else {
        $opt = isset($_GET['view']) ? $_GET['view'] : '';
        if(isset($GLOBALS['withusers']) or $opt == 'user') {
            $tables = array('GR_DEPARTMENT', 'GR_USER', 'GR_USER_DEPARTMENT');
            $conditions = 'USER_ID_FK = USER_ID and DEPT_NO_FK = DEPT_NO';
        }
        else if(isset($GLOBALS['withacct']) or $opt == 'acct') {
            $tables = array('GR_DEPARTMENT', 'GR_FRS');
            $conditions = 'DEPT_NO_FK = DEPT_NO';
        }
        else if($opt == 'addr') {
            $tables = array('GR_DEPARTMENT', 'GR_ADDRESS');
            $conditions = 'DEPT_NO_FK = DEPT_NO';
        }
        else if($opt == 'cust') {
            $tables = 'GR_DEPARTMENT';
            $conditions = 'CUSTOM = 1';
        }
        else {
            $tables = 'GR_DEPARTMENT';
            $conditions = null;
        }
    }

    if(isset($GLOBALS['custeditor'])) {
        $opt_items = array('all'=>'All Departments', 'acct'=>'With Accounts', 'user'=>'With Users', 'addr'=>'With Custom Addresses', 'cust'=>'Custom Only');
        $opt_xml = '<div style="background-color: #DDD; border: 1px solid #CCC; padding: 0.3em; width: 80%;">';
        foreach($opt_items as $key => $val) {
            if($key != 'all') $opt_xml .= "<br/>";
            if($opt == $key or (!$opt and $key == 'all')) $opt_xml .= "<div style=\"font-weight: bold; display: inline; color: #944;\">$val</div>";
            else $opt_xml .= "[<a href=\"?view=$key\">$val</a>]";
        }
        $opt_xml .= '</div>';
        $form->add(field_factory::get_note($opt_xml));
    }

    $cust_list = new single_select_group();
    $pop = new recordset_populator($cust_list, $tables, array('DEPT_NAME', 'DEPT_NO'), $conditions, 'upper(DEPT_NAME)');
    $pop->set_components('name', 'id');
    $pop->set_pairs();
    $pop->set_select_expression('DEPT_NAME', 'distinct DEPT_NAME');

    if($pop->populate()) {
        $view = new data('Customer', $cust_list);
        $r = new list_renderer('select_box');
        $r->set_select_box(20);
        $view->set_renderer($r);
        $form->add($view);

        if(isset($GLOBALS['custeditor'])) {
            $new = field_factory::get_button('New Department', false, 'new_dept.php'); // DON'T THINK THIS IS EVER CALLED.
            $edit = field_factory::get_button('View', false);
            $back = field_factory::get_button('Cancel', false, 'index.php');
            $form->add(field_factory::get_item_row(new collection($edit, $new, $back)));
        }
        else {
            $sub = new data('Continue');
            $sub->set_renderer(new button_renderer());
            $form->add($sub);
        }
    }
    else {
        $form->add(field_factory::get_note('<p><i>No departments listed.</i></p>'));
        $form->add(field_factory::get_button('OK', true, 'index.php'));
    }
    return $form;
}

function set_action($action) { $GLOBALS['custformaction'] = $action; }
function custeditor() { $GLOBALS['custeditor'] = true; }
function owing($days) { $GLOBALS['owing'] = $days; }
function with_users() { $GLOBALS['withusers'] = true; }
function with_accounts() { $GLOBALS['withacct'] = true; }

?>