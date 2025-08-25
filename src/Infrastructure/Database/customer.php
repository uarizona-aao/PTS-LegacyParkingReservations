<?php
namespace App\Infrastructure\Database;
use App\Application\Responders\CustomerResponder;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;

class customer
{
	var $error;
	var $errorMsg;

	var $login;
	var $db;

	var $userid;
	var $newInfo;
	var $deptNums;
	var $deptNames;
	var $createMsg = '<p align="center" class="warning">Your account was created successfully</p>';

	function __construct ($login) {
		// Since processing a new customer will instatiate the login class but will instead be called from the database,
		//		it's necessary to determine which class is being passed.
		$className = get_class($login);
		if ($className=="App\Infrastructure\Database\GrLogin") {
			$this->login = $login;
			$this->db = $login->db;
		}
		elseif ($className=="database") $this->db = $login;
	}

	function custset()
	{
		// No longer using dbkey. Using netid to look up user info.
		// CheckEnabled is pointless; we don't need to control it here.
			// $this->dbkey = $_SESSION['eds_data']['dbkey'];
		$results['count'] = 111;
		$results[0]['mail'][0]					= $_SESSION['eds_data']['mail'];
		$results[0]['telephonenumber'][0]	= $_SESSION['eds_data']['employeephone'];
		$results[0]['cn'][0]						= $_SESSION['eds_data']['cn'];
		$results[0]['department'][0]			= $_SESSION['eds_data']['deptname'];


		if (!count($results) || $results['count']<1)
		{
			$this->login->error = "custfail";
			$this->login->errormsg = "Phonebook look-up failed";
			return false;
		}

		$this->login->cuinfo = array(
			"auth"=>2,
			"authdesc"=>"Customer",
		);

		$this->setNewInfo($results,$this->login->cuinfo);
		$GLOBALS['newCust'] = true;
		$_SESSION['custcreate'] = true;

		// Post to the new route and handle the user there.
		$twig = Twig::create(__DIR__.'/../../../templates', ['cache' => false]);
		$responder = new CustomerResponder($twig);

		// create a Response to pass in
		$responseFactory = AppFactory::determineResponseFactory();
		$response = $responseFactory->createResponse();

		$response = $responder->confirm_user_information($response, [
			'newInfo' => $this->newInfo,
			'path' => $_ENV['APP_URL'] . '/confirm_user_information'
		]);
		echo $response->getBody();
		exit;


		// $cForm = $this->writeCustForm();
		return 1;
	}


	function setNewInfo ($results,$cuinfo=NULL) {
		if (!isset($results[0]['mail'][0])) $results[0]['mail'] = array(0=>$this->login->netid."@arizona.edu");
		$deptno = $_SESSION['eds_data']['deptno'] ? $_SESSION['eds_data']['deptno'] : $this->login->db->getDeptNo(strtoupper($results[0]['department'][0]));
		$this->newInfo = array(
			'email'=>$results[0]['mail'][0],
			'phone'=>$results[0]['telephonenumber'][0],
			'netid'=>$this->login->netid,
			'cuname'=>ucwords($results[0]['cn'][0]),
			'deptno'=>$deptno,
			'deptname'=>strtoupper($results[0]['department'][0]),
			'auth'=>2,
			'authdesc'=>'Customer'
		);
		if (is_array($cuinfo)) $this->newInfo = array_merge($this->newInfo,$cuinfo);
	}


	function writeCustForm()
	{
		$formStr = '';
		//	if ($_SESSION['cuinfo']['auth'] > 1)
		//		return;
		$formStr .= '<p>&nbsp;</p>';
		if (!isset($this->newInfo['cuname'])) {
			$formStr .= '<p class="warning">There has been an error.</p>';
			echo $formStr;
			return false;
		}
		$formStr .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" onSubmit="return checkCustForm();">';
		$formStr .= '<table class="formbox" align="center">'."\n";
		$formStr .= ' <tr><th colspan="2" class="title"><div align="center">Please Verify Your Information</div></th></tr>'."\n";
		$formStr .= ' <tr valign="middle"><td class="req" align="right">Name</td><td><input type="text" name="cuname" size="30" value="'.$this->newInfo['cuname']."\"/></td></tr>\n";
		$formStr .= ' <tr valign="middle"><td class="req" align="right">Email</td><td><input type="text" name="email" size="30" value="'.$this->newInfo['email']."\"/></td></tr>\n";
		$formStr .= ' <tr valign="middle"><td class="req" align="right">Phone</td><td><input type="text" name="phone" size="12" maxlength="12" value="'.$this->newInfo['phone']."\"/></td></tr>\n";
		$formStr .= ' <tr valign="middle"><td class="req" align="right">Department Number<br/>(Five digits: include leading zeros)</td><td><input type="text" name="deptno" size="5" maxlength="5" value="'.$this->newInfo['deptno']."\"/></td></tr>\n";
		//$formStr .= "<tr valign='middle'><td class='req'>(Five digits - include leading zeros)</td><td>&nbsp;</td></tr><br>";
		$formStr .= ' <tr align="center"><td colspan="2" class="submitter"><input type="submit" name="custcreate" value="Continue &gt;&gt;"/><input type="hidden" name="netid" value="'.$this->newInfo['netid'].'"/><input type="hidden" name="auth" value="'.$this->newInfo['auth'].'"/></td></tr>';
		$formStr .= "</table></form>\n";
		return $formStr;
	}

	function createAccount ()
	{
		$deptno = $_POST['deptno'];
		$response = $this->validDept($deptno);
		if (!$response)
		{
			$this->error = 'nodept';
			$this->errorMsg = "The department number you entered ($deptno) was not found. Please try again.";
			//echo $this->error_out();
			return false;
		}

		$this->userid = $this->db->seqInsert("INSERT INTO PARKING.GR_USER (USER_ID,USER_NAME,NETID,AUTH_ID_FK,PHONE,EMAIL,LAST_LOGIN,CREATION_DATE) VALUES(PARKING.GR_USER_ID.NEXTVAL,".$this->db->format($_POST['cuname'],true,false,35).",'".$_POST['netid']."',".$_POST['auth'].",".$this->db->format($_POST['phone'],true,false,12).",".$this->db->format($_POST['email'],true,false,40).",SYSDATE,SYSDATE)","PARKING.GR_USER_ID");

		if (!$this->userid)
		{
			$this->error = "noUserid";
			return false;
		}

		$this->newInfo = $_POST;
		$this->newInfo['userid'] = $this->userid;
		$this->newInfo['authdesc'] = 'Customer';

		$this->createDept($_POST['deptno']);
		if (!$response) return false;
	}

	function deptset($userid)
	{
		// New webauth way.
		$results['count'] = 11;
		$results[0]['department'][0] = $_SESSION['eds_data']['deptname'];

		$this->userid = $userid;
		$this->newInfo['deptno'] = $_SESSION['eds_data']['deptno'] ? $_SESSION['eds_data']['deptno'] : $this->login->db->getDeptNo(strtoupper($results[0]['department'][0]));

		$cForm = $this->writeCustForm();

		$GLOBALS['newDept'] = true;
		$_SESSION['deptcreate'] = true;

		return $cForm;
	}

	//function writeDeptForm () {
	//	echo '<p></p><form method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" onSubmit="return checkDeptForm();"><table border="0" cellpadding="0" class="formbox" align="center">';
	//	echo '	<tr><th colspan="2" class="title">Please Verify Your Information</th></tr>'."\n";
	//	echo '	<tr valign="middle"><td class="req">Department</td><td><input type="text" name="deptno" size="5" maxlength="5" value="'.$this->newInfo['deptno']."\"/></td></tr>\n";
	//	echo '	<tr align="center"><td colspan="2" class="submitter"><input type="submit" name="deptcreate" value="Continue &gt;&gt;"/><input type="hidden" name="netid" value="'.$this->userid.'"/></td></tr>';
	//	echo "</table></form>\n";
	//}

	function createDept ($dept) {
		$this->db->query("INSERT INTO PARKING.GR_USER_DEPARTMENT (USER_ID_FK,DEPT_NO_FK) VALUES('$this->userid','$dept')");
	}

	function validDept ($dept)
	{
		$this->db->query("SELECT DEPT_NO FROM PARKING.GR_DEPARTMENT WHERE DEPT_NO='$dept'");
		if ($this->db->rows) return true;
		else return false;
	}

	function error_out () {
		$return = "";
		$errors = array(
			"custfail"=>"Your entry into the database failed. Please contact PTS Visitor Programs at (520) 621-3710.",
			'nodept'=>'An error occurred in creating your account: ',
			"netidconn"=>"Connection to the authentication server failed. Please try again later.",
			'notArray'=>'There has been a system error. Please try again later.'
		);
		$return .= '<p align="center">';
		if (isset($errors[$this->error])) $return .= '<span class="warning">Error: '.$errors[$this->error]."</span><br/>";
		if ($this->errorMsg) $return .= $this->errorMsg;
		$return .= "</p>\n\n";
		return $return;
	}

}