<?php
/*
* document.php
* Main class for document control and formatting
*/

require_once '/var/www2/include/gr/collection.php';
require_once '/var/www2/include/gr/url.php';
require_once '/var/www2/include/gr/ip_tracker.php';
require_once '/var/www2/include/gr/email.php';

class document {
	private $title;
	private $xml;
	private $AUTH_ME_BONKERS = false;

	private $directory, $file;

	// Creates a blank document and add XML content if provided
	function __construct($xml = null) {
	  // Parse current URL to determine directory/file data
	  $url = explode('/', $_SERVER['PHP_SELF']);
	  if(substr($url[1],-3) == 'php') {
			// The main site page
			$this->directory = '';
			$this->file = $url[1];
	  }
	  else {
			$this->directory = $url[1];
			$this->file = $url[2];
	  }

	  if($xml) $this->add_xml_content($xml);
	}

	// Adds main-page XML content to the document
	function add_xml_content($xml) {
		// Test for form redirection, i.e. a submit button pointing to an alternate page
		if(isset($_POST['submit_button'])) {
			$goto = 'form_goto_'.strtr(strtolower($_POST['submit_button']), ' ', '_');
			if(isset($_POST[$goto])) url::redirect($_POST[$goto]);
		}

		// Preserve page title
		$matches = array();
		if(preg_match('/<title>.+<\/title>/i', $xml, $matches)) $title = $matches[0];

		try {
			global $auth;
			include_once 'gr/authorization_service_ext.php';
		   if (!is_object($auth)) $auth = new authorization_garage_reservation(); //jody

			// Include all authorization classes for various applications
			//if (checkEnabled('Webauth Garage')) include_once 'authorization_service_ext.php';
			//else include_once 'authorization_service.php';

			// Check for HTTPS, and redirect if necessary
			// xxxx url::secure(true);

			// Authorize users before processing
			if($this->AUTH_ME_BONKERS || preg_match('/<authorization type="(.+?)"(\s+level="(.+?)")?\/>/i', $xml)) {

				preg_match('/<authorization type="(.+?)"(\s+level="(.+?)")?\/>/i', $xml, $matches);
				// Remove the <authorization> tag
				preg_replace('/<authorization type="(.+?)".*?\/>/i', '', $xml, 1);

				$auth_class = 'authorization_' . $matches[1];
				$level = isset($matches[3]) ? $matches[3] : null;
				$auth = authorization::get_auth($auth_class, $level);
				if(!$this->AUTH_ME_BONKERS && !$auth) throw new Exception('Authorization Error');

				if($auth->is_authorized()) {
					if(!$auth->at_signup()) {
						if ($this->AUTH_ME_BONKERS)
							$dName = $this->AUTH_ME_BONKERS;
						else
							$dName = $auth->get_display_name();

						$xml .= '<authorization user="'.$dName.'"/>';
					}
				} else if ($this->AUTH_ME_BONKERS) {
						$xml .= '<authorization user="'.$this->AUTH_ME_BONKERS.'"/>';
				} else {
				  $xml = isset($title) ? $title : '';
				  $xml .= $auth->get_xml();
				}

			// No authorization required, but list authorization in "Tips" box if necessary
			} else {
				$auth = authorization::get_auth(null);
				if($auth and $auth->is_authorized()) $xml .= '<authorization user="'.$auth->get_display_name().'"/>';
			}

			// Replace <dynamic content="function"/> tags with the dynamic content
			$xml = preg_replace_callback('/<dynamic content="(.+?)"( steps=".+?")?\/>/i', array($this, 'callback_get_dynamic'), $xml);

			// Check for <ip_filter/> tags and filter IPs as dictated in ip_tracker class
			if(strpos($xml, '<ip_filter/>') !== false) {
				$filter = new ip_tracker();
				$filter->redirect_outside();
			}

			// Erase stepped form if straying outside the page.
			if(!isset($GLOBALS['seen_big_form'])) unset($_SESSION['steps']);

			// Note expired sessions
			if(isset($GLOBALS['session_expired'])) $xml .= '<session_expired/>';
		}
		// This is the MAIN general error-handling for the site's "dynamic content"-style portions.
		catch(Exception $e) {
			$xml = $title . document::get_error($e);
		}

		$this->xml = $xml;
	}

	// Callback function for handling <dynamic content="function"/> tags
	// For parameter information ($matches) see regular expression above
	static function callback_get_dynamic($matches) {
		$match = $matches[1];
		$has_steps = (isset($matches[2]));
		try {
			// Form with multiple stages
			if($has_steps) {
				// Note that we've seen a big form
				$GLOBALS['seen_big_form'] = true;

				// Restore the form
				$steps_obj = "steps_$match";
				$steps = form_steps::restore($steps_obj);

				// Process form submission
				if(isset($_POST['submit_button'])) $steps->submit_current_form();

				return $steps->get_xml();
			}
			else {
				$func = "get_$match"; // psychotic, this could be get_reservation_protected, or ????
				$new_content = $func();

				if($new_content instanceof form) {
				  $func_validate = "validate_$match";
				  $func_submit = "submit_$match";
				  $func_resubmit = "resubmit_$match";
				  if(isset($_POST['submit_button']) && function_exists($func_validate) and !$func_validate($new_content)) {
				  } else if($new_content->is_submitted() and function_exists($func_submit)) {
						$new_content = $func_submit($new_content);

						if(is_string($new_content)) {
							$submit = new data('OK');
							$submit->set_renderer(new button_renderer());
							$submit = $submit->get_xml();
							$action = function_exists('get_action') ? get_action() : 'index.php';
							$new_content = "<form name=\"Submitted\" action=\"$action\"><form_raw><p>$new_content</p></form_raw>$submit</form>";
						}
				  }
				  else if($new_content->is_resubmitted()) {
						if(function_exists($func_resubmit)) $new_content = $func_resubmit($new_content);
						else url::redirect('index.php');
				  }
				}
				return (method_exists($new_content, 'get_xml')) ? $new_content->get_xml() : $new_content;
			}


		} catch (Exception $e) {
			return document::get_error($e);
		}
	}

	// Returns XML for a pretty error box
	static function get_error(Exception $exception) {
		if($exception instanceof error) {
			$title = $exception->get_title();

			$extra = '';
			if($exception instanceof fatal_step_exception) $extra = $exception->get_extra();
			if(!$extra) {
				$extra = new data('OK');
				$extra->set_renderer(new button_renderer(true, $_SERVER['PHP_SELF']));
				$extra = $extra->get_xml();
			}
		}
		else {
			$title = 'Error';
			$extra = '';
		}
		$err = $exception->getMessage();

		return "<form name=\"$title\"><form_raw>$err</form_raw>$extra</form>";
	}

	// Applies an XSL to the document's XML
	// in general, used to create a normal HTML page from document XML using XSLT
	function transform_xml($xml) {
		// Debugging function to see raw XML data
		//return "<pre>-------------\n".$xml."\n----------------------------------\n";

		$dom_xml = @DOMDocument::loadXML($xml);
		$dom_xsl = @DOMDocument::load('https://'.$_SERVER["HTTP_HOST"].'/xsl/main.xsl');

		$proc = new XsltProcessor();
		$proc->importStylesheet($dom_xsl);
		return $proc->transformToXML($dom_xml);
	}

	function get_xml() {
		return $this->structure_document($this->xml);
	}

	function get_transformed_xml() {
		$xml = $this->get_xml();
		$new = $this->transform_xml($xml);
		// <footer> here:
		$new .= '
			<div id="footer">
			<a href="http://security.arizona.edu/index.php?id=857" out="out" blank="blank" title="Official UofA Privacy Policy">Privacy Info</a>
			<br/>
			All contents copyright &copy; 2004 -
			<script language="javascript">
			var todayd = new Date();
			var yyyy = todayd.getYear();//eeeeeeee
			if (yyyy < 1000)
				yyyy += 1900;
			document.write(yyyy);
			</script>
			Arizona Board of Regents.
			</div>
		';
		return $new;
	}

	function get_html_jjj() {
		$xml = $this->get_xml();
		$xml = preg_replace('/<(\/?)grid([^>]*)>/si',		'<$1table$2>'."\n",	$xml);
		$xml = preg_replace('/<(\/?)heading([^>]*)>/si',	'<$1tr$2>'."\n",		$xml);
		$xml = preg_replace('/<(\/?)row([^>]*)>/si',			'<$1tr$2>'."\n",		$xml);
		$xml = preg_replace('/<(\/?)item([^>]*)>/si',		'<$1td$2>',				$xml);
		return $xml;
	}

	// Sets up the document skeleton for page layout
	private function structure_document($xml) {
		$result = '<document css="main">'
			. '<header>'.$this->get_header().'</header>'
			. "<contents>$xml"
			. '<footer>'.$this->get_footer().'</footer>'
			. "</contents>"
			. $this->get_navigation()
			. "\n</document>";
		return $result;
	}

	private function get_header() {
		return;
	}

	private function get_navigation() {
		return; // "<navigation section=\"$this->directory\" doc=\"$this->file\"/>";
	}

	private function get_footer() {
		return '';
	}
}

/*
 * A fancy error with a title and message body
 */
/*
class error extends Exception {
    private $title;

    function __construct($title, $err) {
        $this->title = $title;
        parent::__construct($err);
    }

    function get_title() {
        return $this->title;
    }
}

 */
?>
