<?php
/*
 * field_factory.php
 * Static factory for creating specific re-usable types of fields and form elements
 */

class field_factory {
    private function __construct() { }

    static function get_display_field() {
        return new data();
    }

    static function get_name_field($title = 'Name', $full = false) {
        $field = new data($title);
        $field->set_renderer(new field_renderer());
        $max = $full ? 30 : 0;
        $field->set_validator(new data_validator("/^[a-z\-' ]+$/i", $max, true, 'Format: Name'));
        return $field;
    }

    static function get_address_field($required = true) {
        $field = new data('Address');
        $field->set_renderer(new field_renderer(20));
        $field->set_validator(new data_validator('/^[a-z0-9\. ]{1,40}$/i', 40, $required, 'Format: 123 N. Your Street'));
        return $field;
    }

    static function get_city_field($required = true) {
        $field = new data('City');
        $field->set_renderer(new field_renderer(20));
        $field->set_validator(new data_validator('/^[a-z ]{1,20}$/i', 20, $required, 'Format: City Name'));
        return $field;
    }

    static function get_zip_field($required = true) {
        $field = new data('Zip');
        $field->set_renderer(new field_renderer(10));
        $field->set_validator(new data_validator('/^\d{5}(-?[\d]{4})?$/', 10, $required, 'Format: 12345-6789'));
        return $field;
    }

    static function get_phone_field($title = 'Phone', $required = true) {
        $field = new data($title);
        $field->set_renderer(new field_renderer(12));
        $field->set_validator(new data_validator('/^(\d{3}-)?\d{3}-\d{4}$/', 12, $required, 'Format: XXX-123-4567'));
        return $field;
    }

    static function get_state_menu($title = 'State', $field = null, $table = null) {
        // Get the hard-coded list of states from states.php
        require_once '/var/www2/include/gr/states.php';
        $menu_items = new single_select_group(states::get_states());
        $menu_items->select_value('AZ'); // Default for PTS application

        if($field and $table) $menu = database_menu::get_menu('State', $field, $table, $menu_items);
        else {
            $menu = new data($title, $menu_items);
            $menu->set_renderer(new list_renderer('menu'));
        }
        return $menu;
    }

    static function get_email_field($ua_only = false, $size = null) {
        $field = new data('Email');
        $field->set_renderer(new field_renderer($size));
        if($ua_only) $field->set_validator(new data_validator('/^[a-z0-9]+([_.-][a-z0-9]+)*@[a-z0-9]\\.arizona\\.edu$/i', 0, true, 'Format: you@email.arizona.edu'));
        else $field->set_validator(new data_validator('/^[a-z0-9]+([_.-][a-z0-9]+)*@([a-z0-9]+([.-][a-z0-9]+)*)+\\.[a-z]{2,4}$/i', 0, true, 'Format: you@email.com'));
        return $field;
    }

    static function get_netid_field() {
        $field = new data('NetID');
        $field->set_renderer(new field_renderer(16));
        $field->set_validator(new data_validator('/^[a-z0-9]{3,16}$/i', 16, true));
        return $field;
    }

    static function get_money_field($title, $grjjj=false) {
        $field = new data($title);
        $render = new field_renderer(3);
        $render->set_leading_text('$');
        $field->set_renderer($render);
        $field->set_formatter(new money_formatter());
        if ($grjjj)
	        $field->set_validator(new data_validator('/^[\d\.]{1,8}$/', 6, true, 'Numbers only:'));
	     else
	        $field->set_validator(new data_validator('/^[0-9]{1,3}$/', 3, true, 'Numbers only:'));
        return $field;
    }

    static function get_money_display() {
        $field = new data();
        $field->set_formatter(new money_formatter());
        return $field;
    }

    static function get_password_field($title = 'Password') {
        $field = new data($title);
        $render = new field_renderer();
        $render->set_password();
        $field->set_renderer($render);
        $field->set_validator(new data_validator('', 0, true));
        return $field;
    }

    static function get_short_date_field($title = 'Date', $earliest = null, $default = 'tomorrow', $note_validator = true) {
        $field = new data($title);
        if($default) $field->set_value(date('m/d/y', strtotime($default)));
        $field->set_renderer(new field_renderer(8));
        $field->set_formatter(new date_formatter('short'));
        $field->set_validator(new short_date_validator($earliest));
        if($note_validator) $field->note_validator();
        return $field;
    }

    static function get_time_field($title = 'Time') {
        $field = new data($title);
        $field->set_renderer(new field_renderer(8));
        $field->set_formatter(new time_formatter());
        $field->set_validator(new time_validator());
        $field->note_validator();
        return $field;
    }

	// added by colin for el dana report - 7/11/05
	static function get_months_menu($title = "Month") {
		$db = get_db();
		$db->query("SELECT TO_CHAR(MIN(RES_DATE),'MM/YYYY') AS MINMON,TO_CHAR(MAX(RES_DATE),'MM/YYYY') AS MAXMON FROM PARKING.GR_RESERVATION");
		$months = array();
		$results = $db->get_results();
		$min = $results[0]["MINMON"];
		$max = $results[0]["MAXMON"];
		$startyr = intval(substr($min,3));
		$startmo = intval(substr($min,0,2));
		$endyr = intval(substr($max,3));
		$endmo = intval(substr($max,0,2));
		$y = $startyr;
		while ($y<=$endyr) {
			if ($y==$startyr) $m = $startmo;
			else $m = 1;
			if ($y==$endyr) $lastmo = $endmo;
			else $lastmo = 12;
			while ($m<=$lastmo) {
				if ($m<10) $m = "0".$m;
				$months["$m/$y"] = date("F Y",strtotime("$m/1/$y"));
				$m = intval($m);
				$m++;
			}
			$y++;
		}

		$menu_items = new single_select_group($months);
		$menu_items->select_value(date("m/Y"));

		$menu = new data($title, $menu_items);
		$menu->set_renderer(new list_renderer("menu"));
		return $menu;
    }

    static function get_cn_display() {
        $field = new data('Citation Number');

        return $field;
    }

    static function get_date_display() {
        $field = new data('Date');
        $field->set_formatter(new date_formatter());
        return $field;
    }

    static function get_dept_field($required = true, $title = 'Department Number', $allow_fake = true) {
        $field = new data($title);
        $field->set_renderer(new field_renderer(5));
		$regex_chars = $allow_fake ? '[0-9A-Z]' : '[0-9]';
        $field->set_validator(new data_validator('/^'.$regex_chars.'{1,5}$/', 5, $required, 'Format: 01234'));
        $field->set_database_string();
        return $field;
    }

    static function get_frs_field($allow_fake = false) {
        $field = new data('KFS Number');
        $field->set_renderer(new field_renderer(7));
        $regex_chars = $allow_fake ? '[0-9A-Z]' : '[0-9]';
        $field->set_validator(new data_validator('/^'.$regex_chars.'{7}$/', 7, true));
        $field->set_database_string();
        return $field;
    }

    static function get_sub_act_field() {
        $field = new data('KFS Sub Acct');
        $field->set_renderer(new field_renderer(5));
        $field->set_validator(new data_validator('/^[\w\d ]+$/', 5, false));
        return $field;
    }

    static function get_sub_obj_field() {
        $field = new data('Sub Obj Code');
        $field->set_renderer(new field_renderer(3));
        $field->set_validator(new data_validator('/^[\w\d ]+$/', 3, false));
        return $field;
    }

//    static function get_zip_field($required = true) {
//        $field = new data('Zip');
//        $field->set_renderer(new field_renderer(10));
//        $field->set_validator(new data_validator('/^\d{5}(-?[\d]{4})?$/', 10, $required, 'Format: 12345-6789'));
//        return $field;
//    }

    // Convenience for generating form buttons
    static function get_button($name, $ownrow = true, $redirect = null) {
        $btn = new data($name);
        $btn->set_renderer(new button_renderer($ownrow, $redirect));
        return $btn;
    }

    // Convenience for packing multiple items into one form row, e.g. buttons
    static function get_item_row(collection $item_list) {
        $items = new data('', $item_list);
        $items->set_renderer(new form_raw_renderer());
        return $items;
    }

    static function get_note($xml) {
        $note = new data('xml', $xml);
        $note->set_renderer(new form_raw_renderer());
        return $note;
    }
}
?>
