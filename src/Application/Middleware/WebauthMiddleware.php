<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use App\Infrastructure\Database\GrLogin;

use function DI\string;

class WebauthMiddleware implements Middleware
{
    public $description			= 'Webauth+Login+for+PTS'; // urlencoded
	public $webauth_return_url	= ''; // URL where webauth comes to after login -
	public $login_url				= ''; // STEP 1 - jump to webauth to login.
	public $customer_url_data	= ''; // STEP 2 - fopen to get custome rdata.
	public $customer_data		= array(); // customer data from customer_url_data.
	/***
	* $propertiesToLoad is array of EDS properties (fields) to fetch -- see
	 * https://siaapps.uits.arizona.edu/home/ --> https://confluence.arizona.edu/pages/viewpage.action?pageId=31655272
	* Others: studentStatus, studentPrimaryCareerProgramPlan, studentAPDesc, employeeFTE, studentCareerProgramPlan, employeePrimaryTitle, dateOfBirth, employeeOfficialOrg, modifytimestamp
	*/
	public $propertiesToLoad	= array('uaId', 'emplId', 'uid', 'isoNumber', 'eduPersonPrimaryAffiliation', 'employeeStatus',
												  'employeePhone', 'mail', 'givenName', 'sn', 'cn', 'employeePrimaryOrgReporting');
	public $getFlexData			= true;
	private $net_id				= '';

    /**
     * {@inheritdoc}
     * TODO: We may need to have the _GET params serialized in the session before forcing a redirect to webauth. Only if we identify a valid case.
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
		// If selenium driver, bypass this as a cookie is being set for normal flows.
		if ($_ENV['APP_ENV'] === 'development' && $request->getUri()->getPath() === '/selenium-cookie') {
			return $handler->handle($request);
		}
		// If any calls are made to dash_pass_pdf, bypass these checks.
		if (preg_match('#^/dash_pass_pdf(/[^/]+)?$#', $request->getUri()->getPath())) {
			return $handler->handle($request);
		}

        $_SESSION['webauth_data'] = [];
        $webAuthBaseURL = "https://webauth.arizona.edu/webauth";
        $webAuthURL = "$webAuthBaseURL/login?service=";
        $serverParams = $request->getServerParams();
        $serviceURL = ($serverParams['REQUEST_SCHEME'] ?? "http") 
            . "://" . $serverParams['HTTP_HOST'] 
            . (strtok($serverParams['REQUEST_URI'], '?') ?: '');

		// DEV-ONLY; checking for dev-auth-as' cookie
		// Only useful for debugging users on production.
		$cookies = $request->getCookieParams();
		if ($_ENV['APP_ENV'] === "development" 
			&& isset($cookies['dev-auth-as']) && !empty($cookies['dev-auth-as'])
			&& !isset($_SESSION['user_token'])) {
			// Simulate authentication using the 'dev-auth-as' cookie
			$_SESSION['webauth_data']['netid'] = $cookies['dev-auth-as'];
			$this->getEdsInfo($_SESSION['webauth_data']['netid']);
			
			// Set token
			$seed = random_int(1000, 9999);
			$inttoken = md5((string) $seed);
			define('TOKEN2', $inttoken);
			$_SESSION['user_token'] = $_SESSION['token2'] = $inttoken;
			$_SESSION['resuser']['netid'] = $_SESSION['webauth_data']['netid'];
			$_SESSION['resuser']['email'] = $_SESSION['eds_data']['mail'];
			$_SESSION['resuser']['fullname'] = $_SESSION['eds_data']['sn'] . ', ' . $_SESSION['eds_data']['givenname'];
			$_SESSION['resuser']['firstname'] = $_SESSION['eds_data']['givenname'];
			$_SESSION['resuser']['lastname'] = $_SESSION['eds_data']['sn'];
			$_SESSION['resuser']['phone'] = $_SESSION['eds_data']['employeephone'] ?? '';
			$_SESSION['resuser']['customertype'] = "UA";

			// Also populate the auth data from GrLogin class implementation
			$login = new GrLogin($_SESSION['webauth_data']['netid'], '', null);
	
			// Continue to the next middleware or route
			return $handler->handle($request);
		}

        // Check if the user is already authenticated
        if (!isset($_SESSION['user_token']) || empty($_SESSION['user_token'])) {
            // Get the 'ticket' from query params
            $queryParams = $request->getQueryParams();
            $ticket = $queryParams['ticket'] ?? null;
    
            // If no ticket, redirect to WebAuth login
            if (!$ticket) {
                $response = new SlimResponse();
                return $response
                    ->withHeader('Location', $webAuthURL . urlencode($serviceURL))
                    ->withStatus(302);
            }
    
            // We have a ticket, process it!
            $validateURL = $webAuthBaseURL . '/serviceValidate?ticket=' . $ticket . '&service=' . $serviceURL;
			$ticketResponse = fopen($validateURL, 'r');

            // XML parse back into $ticketResponse
            $xml = '';
			while ($line = fgets($ticketResponse))
				$xml .= $line;

			$xmlp = xml_parser_create();
			xml_parse_into_struct($xmlp, $xml, $ticketResponse, $index);
			xml_parser_free($xmlp);
            if ($ticketResponse[$index['CAS:USER'][0]]['value'] && !isset($index['CAS:AUTHENTICATIONFAILURE']))
			{
				$_SESSION['webauth_data']['netid'] = $ticketResponse[$index['CAS:USER'][0]]['value'];
                $this->getEdsInfo($_SESSION['webauth_data']['netid']);
                
                // set token
                $seed = random_int(1000,9999);

                $inttoken= md5((string) $seed);
                define('TOKEN2',$inttoken);
                $_SESSION['user_token'] = $_SESSION['token2'] = $inttoken;
                $_SESSION['resuser']['netid'] = $_SESSION['webauth_data']['netid'];
                $_SESSION['resuser']['email'] = $_SESSION['eds_data']['mail'];
                $_SESSION['resuser']['fullname'] = $_SESSION['eds_data']['sn'] . ', ' . $_SESSION['eds_data']['givenname'];
                $_SESSION['resuser']['firstname'] = $_SESSION['eds_data']['givenname'];
                $_SESSION['resuser']['lastname'] = $_SESSION['eds_data']['sn'];
                $_SESSION['resuser']['phone'] =  $_SESSION['eds_data']['employeephone'] ?? '';
                $_SESSION['resuser']['customertype'] = "UA";
				// Also populate the auth data from GrLogin class implementation
				$login = new GrLogin($_SESSION['webauth_data']['netid'], '', null);
			}
			else // $index['CAS:AUTHENTICATIONSUCCESS']
			{
				$_SESSION['webauth_data']['netid'] = '';
			}

            // Redirect to remove ticket from URL
			$uri = $request->getUri();
			$clean_uri = $uri->withQuery('');
			$response = new SlimResponse();
			return $response
				->withHeader('Location', (string) $clean_uri)
				->withStatus(302);
        }
    
        // Continue to the next middleware or route
        return $handler->handle($request);
    }

    public function getEdsInfo($net_id_str, $getFlexData=false, $singleField='')
	{
		/***************
		 * INPUT:
		 *		$net_id_str: The netid should have been obtained through webauth login - $_SESSION['webauth_data']['netid']
		 *		$getFlexData: If true (!$ignore_t2) then call T2 GetEntity class (uses $dbConn)
		 * OUTPUT:
		 *		Sets the $_SESSION['eds_data'] array.
		 * For uits EDS access and documentation, see https://siaapps.uits.arizona.edu/home/accounts/
		 * Using 'Webauth URL' (prefered): Then uses url to get eds data. (No LDAP, so this is the goal)
		 *	OLD way is 'Webauth Login' which uses ldap EDS service to get customers' data via their netid.
		 *
		 * NOTE: Using the username and bindPw, can search individual:  https://eds.arizona.edu/people/16808753 | jbrabec | ...
		 *	NOTE: Eds can't get mailing addresses, but Joann can, but that data is 24 hours old.
		 */

		global $dbConn;

		$this->getFlexData		= $getFlexData;

		$this->net_id				= $net_id_str;
		$_SESSION['eds_data']	= array();

		// set some DEFAULT values:
		$_SESSION['eds_data']['isonumber']	= ''; // cat card id, ENTITY.ENT_TERTIARY_ID
		$_SESSION['eds_data']['class']		= 'No Classification';
		$_SESSION['logout_link'] = true;

		if (!$this->net_id) exitWithBottom('ERROR: No Net ID');

		if ($singleField)
		{
			unset($this->propertiesToLoad);
			$this->propertiesToLoad = array($singleField);
		}

		$this->edsURL($this->net_id, $this->propertiesToLoad);


		$this->refineMoreEDS();

		return '';
	}


	private function edsURL($net_id, $edsProperties)
	{
		/*****
		 * called from getEdsInfo function.
		 * INPUT:
		 *		$net_id (customer has already logged in via webauth).
		 *		$edsProperties - which eds data to fetch (like netid, etc).
		 *		$eds_user and $eds_pass is our our admin account (below) - so can get eds data on anybody.
		 * OUTPUT: calls setEdsSession.
		 */

		$eds_user	= 'parking-permits'; // lowercase
		$bindPw		= 'gngy0WCaWcBxDanoCzIu7LnJR37Qee';  //'6MswcC9LgxK7shz2bVwQ0zXhOGjmVv'; // gv0TxVGrtYJGg8VLo8omZ9Y48tHkpvN6'; // yBShXaENyEdMnL7XCzNb2G6yivbzKqNc
		$eds_pass	= stripslashes(str_replace('\\','\\\\',$bindPw)); // the password allows backslashes but php screws them up

		$url	= 'https://eds.arizona.edu/people/' . $net_id; // could be uaid, etc

		$cred	= sprintf('Authorization: Basic %s', base64_encode($eds_user.':'.$eds_pass));
		$opts	= array('http' => array ('method'=>'GET', 'header'=>$cred));
		$ctx	= stream_context_create($opts);

		// send our request and retrieve the DSML response
		$dsml	= file_get_contents($url, false, $ctx);
		$xml	= new \SimpleXMLElement($dsml);

		// set namespace context for XPath query (may not need this)
		$xml->registerXPathNamespace('dsml', 'http://www.dsml.org/DSML');

		foreach($edsProperties as $k_p => $aProp)
		{
			if ($aProp)
			{
				//	if ($GLOBALS['jody']) {
				//		$xquery = "//dsml:entry/dsml:attr/dsml:value";
				//		$eds_vals_url = $xml->xpath($xquery);
				//		echo '<pre>$xquery: '.$xquery.'<br>';
				//		var_dump($eds_vals_url);
				//		exitWithBottom('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
				//	}
				$xquery = "//dsml:entry/dsml:attr[@name='".$aProp."']/dsml:value";
				$eds_vals_url = $xml->xpath($xquery);
				$this->setEdsSession($aProp, $eds_vals_url);
			}
		}
	}


	private function setEdsSession($edsProperty, $edsvals)
	{
		/***
		 * Called by edsURL (was calld by edsLDAP, old way)
		 * Sets $_SESSION['eds_data'] array.
		 */

		foreach ($edsvals as $k => $v) // eds vals
		{
			/***
			 * This "foreach" block should only loop one time ($k !> 0).
			 * Note: The old way used 'edupersonaffiliation', which has multiple, but now using 'edupersonprimaryaffiliation'
			 */
			$kStr = (string)$k;
			$vStr = (string)$v;
			$vStr = trim($vStr); // just in case eds returns data with spaces.
			if ($kStr == (string)'count') // for ldap data.
				continue;

			// Note how the EDS data has upper-case vars, whereas our session vars don't.
			switch ($edsProperty)
			{
				case 'uaId':
					$_SESSION['eds_data']['dbkey']	= $vStr; // dbkey, ENTITY.TERTIARY_UID
					if (@$_SESSION['eds_data']['dbkey'] == 'T737490097340196352')
					{
						// Test account, reduce to 12 chars.
						$_SESSION['eds_data']['dbkey'] = 'T73749009734'; //0196352
					}
					break;
				case 'eduPersonPrimaryAffiliation':
					// Make $classificationStr string match getEslDefUid() in Flex_Entities .php -- setEslDefs
					// To get a nice live random list, search:	https://eds.arizona.edu/people/co*
					if ($vStr=='admit')					$classificationStr = 'Student';
					else if ($vStr=='studentworker')	$classificationStr = 'Student';
					else if ($vStr=='student')			$classificationStr = 'Student';
					else if ($vStr=='gradasst')		$classificationStr = 'GradAsst';
					else if ($vStr=='employee')		$classificationStr = 'Employee';
					else if ($vStr=='staff')			$classificationStr = 'Employee';
					else if ($vStr=='faculty')			$classificationStr = 'Employee';
					else										$classificationStr = 'No Classification'; // Default
					// same as $_SESSION['entity']['classificationStr']
					$_SESSION['eds_data']['class'] = $classificationStr;
					break;
				case 'uid':
					$_SESSION['eds_data']['netid']	= $vStr; // netid
					break;

				default:
					// Make key lowercase.
					if ($edsProperty)
					{
						$tmpKey = strtolower($edsProperty);
						$_SESSION['eds_data'][$tmpKey] = $vStr;
					}
					break;
			}
		}
	}

	private function refineMoreEDS()
	{
		/***
		 * Not sure why this is not in setEdsSession function.
		 */
		if ($_SESSION['eds_data']['employeeprimaryorgreporting'] && preg_match('/^.*:([^:]+):([\w\d_]+)\-([^:]+):.*$/si', $_SESSION['eds_data']['employeeprimaryorgreporting']))
		{
			/***
			 * A colon (:) separated list containing a PCN (position control number), the Roster Department number for that position,
			 * the college to which the employee reports (formatted as college code and college description, separated by a dash (-)),
			 * and the VP to which the employee reports (formatted as VP code and VP description, separated by a dash (-)).
			 * example:  1867632:9804:PRKG-Parking and Transportation:FCLT-Facilities
			 */
			$_SESSION['eds_data']['deptname']	= preg_replace('/^.*:([^:]+):([\w\d_]+)\-([^:]+):.*$/si', '$3', $_SESSION['eds_data']['employeeprimaryorgreporting']);
			$_SESSION['eds_data']['deptno']		= preg_replace('/^.*:([^:]+):([\w\d_]+)\-([^:]+):.*$/si', '$1', $_SESSION['eds_data']['employeeprimaryorgreporting']);
			if ($_SESSION['eds_data']['deptno']!='' && ctype_digit($_SESSION['eds_data']['deptno']))
			{
				// pad deptno with 0's
				$_SESSION['eds_data']['deptno']		= '0000' . $_SESSION['eds_data']['deptno'];
				$_SESSION['eds_data']['deptno']		= preg_replace('/^\d*(\d{5})$/si', '$1', $_SESSION['eds_data']['deptno']);
			}
		}
		// If givenName has only ONE space, then separate into first name / middle name. Or else put entire givenName into first name.
		$_SESSION['eds_data']['givenname_fn'] = preg_replace('/^([^\s]+)(\s+)([^\s]+)$/si', '$1', $_SESSION['eds_data']['givenname']);
		$_SESSION['eds_data']['givenname_mi'] = preg_replace('/^([^\s]+)(\s+)([^\s]+)$/si', '$3', $_SESSION['eds_data']['givenname']);
		// If givenname doesnt have exactly one space (in which preg_replace failed), then givenname_fn & givenname_mi will = givenname, so erase givenname_mi.
		if ($_SESSION['eds_data']['givenname_fn'] == $_SESSION['eds_data']['givenname'])	$_SESSION['eds_data']['givenname_mi'] = '';
	}
}
