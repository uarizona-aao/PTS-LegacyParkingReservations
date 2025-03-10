<?php
/*
 * ip_tracker.php
 * Analyzes IP information. Used for determining IP attributes and filtering IP addresses.
 *
 *   See  G:\POS\SetUp\Network.txt
 */

class ip_tracker {
    private $address;
    private $locations = array
        (
         'PTS Building' => array(128,196,6,2,252)
         );
    private $garages = array
        (
         'Main Gate Garage' => array(128,196,81,194,203),
// Changed IP range to old Park Garage settings - jsc - 20080710
//         'Park Avenue Garage' => array(128,196,24,2,11),
         'Park Avenue Garage' => array(150,135,133,32,63),
// Changed IP range to old Second Garage settings - jsc - 20140308
//         'Second Street Garage' => array(128,196,47,2,5),
         'Second Street Garage' => array(150,135,133,64,95),
         'Sixth Street Garage' => array(128,196,71,98,110),
// Changed IP range to old Tyndall Garage settings - jsc - 20140308
//         'Tyndall Avenue Garage' => array(128,196,47,162,165),
         'Tyndall Avenue Garage' => array(128,196,3,130,180),
         'Cherry Avenue Garage' => array(150,135,133,97,121),
         'Highland Avenue Garage' => array(150,135,80,162,187)
         );

    function __construct() {
        $this->address = $_SERVER['REMOTE_ADDR'];
    }

    function get_garage() {
        return $this->check('garages');
    }

    function get_building() {
        return $this->check('locations');
    }

    function get_location() {
        return ($this->get_building() or $this->get_garage());
    }

    function redirect_outside() {
        if(!$this->get_location()) {}  // url::redirect('http://parking.arizona.edu');
    }

    // Finds a name for the current IP, using one of the class variables
    private function check($var_name) {
        if(!isset($this->$var_name)) return null;
        foreach($this->$var_name as $location => $xip)
            if($this->checkip($xip)) return $location;
        return null;
    }

    //Returns true if the user falls within the IP:
    // - xip[0], xip[1], xip[2] must match the user's first 3 IP elements (111.222.333.xxx)
    // - the fourth (test) value from xxx.xxx.xxx.test must be between xip[3] and xip[4].
    private function checkip($xip) {
        $ip = explode('.', $this->address);

        if ( ($ip[0] == $xip[0]) and ($ip[1] == $xip[1]) and ($ip[2] == $xip[2]) and (($ip[3] >= $xip[3]) and ($ip[3] <= $xip[4])) )
             return true;

        return false;
    }
}
?>
