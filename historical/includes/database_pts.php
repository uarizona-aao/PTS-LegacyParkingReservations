<?php
/*
* database_pts.php
* PTS-specific database implementation
*/

abstract class database_ora
{
	static private $instance = array();

	// Returns the instance of database, creates if necessary
	static final function get_instance($database_class) {
		// Return null if the parameter is not a real class
	if(!class_exists($database_class))
		return null;

	// Instantiate the class if necessary
	if(!isset(database_ora::$instance[$database_class])) {
		$new_instance = new $database_class();
		$new_instance->init();
		database_ora::$instance[$database_class] = $new_instance;
	}

	return database_ora::$instance[$database_class];
	}

	// Do not allow this class to be instantiated directly
	final function __construct() { }

	abstract function connect();
	abstract function is_connected();
	abstract function query($sql);
	abstract function get_results();
	function init() { }


	function showQuery($query, $qVars=array(), $sVars=array()) {
		//TODO: USE setOciError function somehow instead of this function
		if ($GLOBALS['DEBUG_DEBUG']) {
			echo '<small>';
			if ($this->production)
				echo "--------PRODUCTION DB ------<br>";
			else
				echo "--------test DB -------<br>";
			echo $query.'<br>';
			if (sizeof($qVars))
				print_r($qVars);
			if (sizeof($sVars))
				print_r($sVars);
			echo '<hr></small>';
		}
	}
}



/*
 * Oracle DB class adds functionality:
 * - Setters: set_identifier(), set_username(), and set_password()
 * - Getters: num_rows() of the results
 */
class oracle_database extends database_ora
{
	private $identifier;
	private $username;
	private $password;

	private $connection;
	private $last_query = '';
	private $results;
	private $rows = 0;

	private $first_row = 0;
	private $max_rows = -1;

	// Sets the Oracle identifier string (i.e. tnsnames.ora entry)
	function set_identifier($i) {
	  $this->identifier = $i;
	}

	// Sets the Oracle username
	function set_username($u) {
	  $this->username = $u;
	}

	// Sets the Oracle password
	function set_password($p) {
	  $this->password = $p;
	}

	function connect() {
//		if ($GLOBALS['WEBSITE_DOWN_MSG'])
//		{
//			if ($GLOBALS['WEBSITE_DOWN'] == 3) {
//
//			}else if ($GLOBALS['database_test_db'] && $GLOBALS['WEBSITE_DOWN']==2) {
//				// Test db and var set to 2, so don't shut down service nor DB connections.
//			} else {
//				return false;
//			}
//		}

		if($this->is_connected()) return;
		$this->connection = @oci_connect($this->username, $this->password, $this->identifier);
		if ($this->is_connected()===false) {
			//jjj Nov 17, 2009 , added this extra oci_pconnect, to try to avoid all the errors Carmen is getting.
			$this->connection = @oci_pconnect($this->username, $this->password, $this->identifier);
			if ($this->is_connected()===false)
				throw new database_exception('Cannot connect to database');
		}
	}

	function is_connected() {
		return $this->connection;
	}

	// Queries the database, connecting if necessary
	// Returns the number of rows in the result
	function query($sql) {

	$this->showQuery($sql);

	$this->connect();

	// Don't re-execute the same query
	if($sql == $this->last_query) return $this->num_rows();
	$this->last_query = $sql;

	$st = @oci_parse($this->connection, $sql);
	@oci_execute($st);
	$err = @oci_error($st);
	$this->rows = @oci_fetch_all($st, $this->results, $this->first_row, $this->max_rows, OCI_FETCHSTATEMENT_BY_ROW);
	@oci_free_statement($st);

	if($err) throw new database_exception('<pre>'.print_r($err,true).'</pre>');
	if($err) throw new database_exception('Cannot access the database. ('.$err['code'].')');

	return $this->num_rows();
	}

	// Executes a database query or stored procedure
	// Binds PHP variables to Oracle variables if listed in the second argument
	function execute($sql, $vars = null) {

		$this->showQuery($sql, $vars);

		$this->connect();

		$st = @oci_parse($this->connection, $sql);
		if($vars) {
			unset($this->results);
			foreach($vars as $name => $size)
				 @oci_bind_by_name($st, ":$name", $this->results[$name], $size);
		}
		@oci_execute($st);
		$err = @oci_error($st);
		@oci_free_statement($st);

		if($err) throw new database_exception('<pre>'.print_r($err,true).'</pre>');
		if($err) throw new database_exception('Cannot access the database. ('.$err['code'].')');
	 }

	function get_results() {
	  return $this->results;
	}

	// Returns the given field from the first result
	function get_from_top($field) {
	  return isset($this->results[0][$field]) ? $this->results[0][$field] : null;
	}

	function num_rows() {
	  return $this->rows;
	}

	function disConnect () {
	if ($this->is_connected()) oci_close($this->connection);
	}
}


class database_exception extends Exception { }


// Shortcut to database_ora::get_instance(<type>);
// Just call get_db([optional_type]);
function get_db($database_class = 'pts_database') {
	// Hey, kinda like new pts_database()
	return database_ora::get_instance($database_class);
}


class pts_database extends oracle_database
{
	/*******************************************
	 * Returns DB connection data for PARKING schema
	 * ######### TO DO: Merge gr/database_pts.php and database.php
	 ******************************************* */

	function init() {
	  $this->init_identifier();
	}

	function init_identifier()
	{
		if (@$GLOBALS['database_test_db'])
			$production = false;
		else
			$production = true;

		if ($production)
		{
			// n8_live
			$dbHost = '128.196.6.59'; // OLD: 128.196.6.97
			$serviceName = 'ptsor19.ptsaz.arizona.edu'; // OLD: oracle9.pts7.arizona.edu
			$uuu = 'parking'; // parking    flexadmin
			$ppp = 'longitudinal-syringe-9@'; // mis4pts    flexadmin4pts23
		} else {
			// n7_test
			$dbHost = '128.196.6.216'; // OLD: 128.196.6.90
			$serviceName = 'node16o.node16.pts.arizona.edu'; // OLD: oracle9.pts0.arizona.edu
			$uuu = 'parking'; // parking    flexadmin
			$ppp = 'longitudinal-syringe-9@'; // mis4pts    flexadmin4pts23
		}
		$this->set_identifier('(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = '.$dbHost.')(PORT = 1521))) (CONNECT_DATA = (SERVICE_NAME = '.$serviceName.')))');
		$this->set_username($uuu);
		$this->set_password($ppp);
	}
}

//**************************************************************** This class is not used.
//class pts_test_database extends pts_database {
//function init_identifier() {
//	$this->set_identifier('(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 128.196.6.252)(PORT = 1521)) ) (CONNECT_DATA = (SERVICE_NAME = oracle9.pts6.arizona.edu) ) )');
//}
//}
?>
