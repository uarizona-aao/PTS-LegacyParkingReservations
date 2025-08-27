<?php
namespace App\Infrastructure\Database;

class GrLogin
{
	var $db;

	var $cForm;
	var $netid;
	var $cuinfo;

	var $dbkey;

	var $error;
	var $errormsg;
	var $login;

	// construct function for new login
	function __construct ($Pnetid, $pass=null, $db_conn=null)
	{
		// if db not passed correctly, instantiate it in the object

		if ($db_conn)
			$this->db = $db_conn;
		else
			$this->db = new database();
		// if net id supplied
		if ($Pnetid)
		{
			// ldap net id is case-sensitive
			$Pnetid = strtolower($Pnetid);
			// store net id
			$this->netid = $Pnetid;


			// get user info from local user table + department info

			$queryLocal = "SELECT U.*,DESCRIPTION AS AUTH,DEPT_NO,DEPT_NAME
					FROM PARKING.GR_USER U,PARKING.GR_AUTHORIZATION,PARKING.GR_DEPARTMENT,PARKING.GR_USER_DEPARTMENT
					WHERE AUTH_ID=AUTH_ID_FK AND USER_ID=USER_ID_FK AND DEPT_NO=DEPT_NO_FK AND LOWER(NETID)=:login";

			$qVars = array('login'=>$Pnetid);

			$this->db->sQuery($queryLocal, $qVars);

			// found user in local table
			if ($this->db->rows)
			{
				$this->cuinfo = array(
					'userid'=>$this->db->results['USER_ID'][0],
					'username'=>$this->db->results['USER_NAME'][0],
					'netid'=>$this->db->results['NETID'][0],
					'phone'=>$this->db->results['PHONE'][0],
					'email'=>$this->db->results['EMAIL'][0],
					'auth'=>$this->db->results['AUTH_ID_FK'][0],
					'authdesc'=>$this->db->results['AUTH'][0],
					'deptno'=>array(),
					'deptname'=>array()
				);

				// since user can have multiple departments, store each in an array
				for ($i=0; $i<$this->db->rows; $i++) {
					$this->cuinfo['deptno'][] = $this->db->results['DEPT_NO'][$i];
					$this->cuinfo['deptname'][] = $this->db->results['DEPT_NAME'][$i];
				}

				// update the login date
				$this->db->query('UPDATE PARKING.GR_USER SET LAST_LOGIN=SYSDATE WHERE USER_ID='.$this->cuinfo['userid']);

				// tell the database that they've logged in
				$this->db->login = true;

				// set cuinfo session variable
				$this->setcuinfo();

			}
			// if the customer was not found, add them
			else
			{
				// open customer page and instantiate the new customer object

				//############################### New Customer Class ##################################
				// include_once '/var/www2/include/gr/new_cust_gr.php';
				$newcust = new customer($this);

				// check if it's a dept missing or the whole customer
				$this->db->sQuery("SELECT USER_ID FROM PARKING.GR_USER WHERE LOWER(NETID)=:login", array('login'=>$Pnetid));

				if ($this->db->rows) { // if they're missing from GR_USER_DEPARTMENT then add them
					$this->cForm = $newcust->deptset($this->db->results['USER_ID'][0]);
				} else  { // otherwise add them to the local user table
					$this->cForm = $newcust->custset();
				}
			}

				// if ($GLOBALS['DEBUG_DEBUG'])
				// {
				// 	// echoed once in top.inc .php
				// 	$_SESSION['ldap_data_debug'] = '<br><br><br><br><hr><pre>Ldap login $results: ' . print_r($results,true) . '</pre>';
				// 	$_SESSION['ldap_data_debug'] .= '<pre>class login $this: ' . print_r($this,true) . '</pre><br><br><br>';
				// }
		}
	}


	function setcuinfo () {
		/***
		 * sets the session variable from the object's information.
		 */
		error_log("Setting cuinfo session variable");
		$_SESSION['cuinfo'] = $this->cuinfo;

		/* Similar to database .php which has:
			$_SESSION['cuinfo']['userid'] = $newcust->newInfo['userid'];
			$_SESSION['cuinfo']['username'] = $newcust->newInfo['cuname'];
			$_SESSION['cuinfo']['netid'] = $newcust->newInfo['netid'];
			$_SESSION['cuinfo']['phone'] = $newcust->newInfo['phone'];
			$_SESSION['cuinfo']['email'] = $newcust->newInfo['email'];
			$_SESSION['cuinfo']['auth'] = $newcust->newInfo['auth'];
			$_SESSION['cuinfo']['authdesc'] = $newcust->newInfo['authdesc'];
			$_SESSION['cuinfo']['deptno'] = array($newcust->newInfo['deptno']);
			$_SESSION['cuinfo']['deptname'] = array($this->getDeptName($newcust->newInfo['deptno']));
		 */
	}


	function fail_login () {
		// login failed
		$this->error = 'login';
	}


	function error_out () {
		// returns the error messages
		$return = '';
		$errors = array(
			'ldapBind'	=> 'Bad Connection or Incorrect Net ID and/or Password',
			'login'		=> 'Incorrect Net ID and/or Password',
			'auth'		=> 'You are not authorized to access this page',
			'custfail'	=> 'Your entry into the database failed. Please contact PTS Visitor Programs at (520) 621-3710.',
			'netidconn'	=> 'Connection to the authentication server failed. Please try again later.',
			'nodept'		=> 'An error occurred in creating your account: '
		);
		$return .= '<p align="center">';
		if (isset($errors[$this->error]))
			$return .= '<span class="warning">Error: '.$errors[$this->error]."</span><br/>";
		if ($this->errormsg)
			$return .= $this->errormsg;
		$return .= "</p>\n\n";
		return $return;
	}
}