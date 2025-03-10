<?php
/*
 * fieldset_factory.php
 * Static factory for creating groups of fields and form elements
 */
require_once '/var/www2/include/gr/field_factory.php';

class fieldset_factory {
    private function __construct() { }

    static function get_name() {
        $items = new collection();
        $items->add(field_factory::get_name_field('First Name'));
        $items->add(field_factory::get_name_field('Last Name'));
        return $items;
    }

    static function get_address($required = true) {
        $items = new collection();
        $items->add(field_factory::get_address_field($required));
        $items->add(field_factory::get_city_field($required));
        $items->add(field_factory::get_state_menu());
        $items->add(field_factory::get_zip_field($required));
        return $items;
    }

    static function get_name_address() {
        $items = new collection();
        $items->add(form_factory::get_name());
        $items->add(form_factory::get_address());
        return $items;
    }
}
?>
