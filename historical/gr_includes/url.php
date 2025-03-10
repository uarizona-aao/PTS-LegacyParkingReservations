<?php
/*
 * url.php
 * Basic URL manipulation and redirection
 */
class url {
    private $file;
    private $query;

    function __construct($file = null) {
        $this->file = $file ? $file : $_SERVER['PHP_SELF'];
        $this->query = $_GET;
        unset($this->query['signout']);
    }

    function set_file($file) {
        $this->file = $file;
    }

    function set_value($key, $val) {
        $this->query[$key] = $val;
    }

    // Remove one or more arguments from the GET string of the URL
    function remove() {
        $args = func_get_args();
        foreach($args as $arg)
            if(isset($this->query[$arg])) unset($this->query[$arg]);
    }

    // Returns the GET component of the URL
    function get_query() {
        return http_build_query($this->query);
    }

    // Generates a full URL or path name
    function get_url() {
        $q = $this->get_query();
        $q = str_replace('&', '&amp;', $q);
        if($q) return "$this->file?$q";
        return $this->file;
    }

    // Redirects the browser with a Location header
    static function redirect($dir) {
        if($_SERVER['PHP_SELF'] == $dir) return;
		// ob_start();
        header("Location: $dir");
	//	 ob_end_flush();
        exit;
    }

    // Redirects between http and https depending on whether the current page is set to be secure
	 // not used I don't believe.
    static function secure($is_secure = true) {
        $current = (isset($_SERVER['HTTPS']));
        if(($current and $is_secure) or (!$current and !$is_secure)) return;
        $protocol = $is_secure ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        url::redirect("$protocol://$host$uri");
    }
}
?>
