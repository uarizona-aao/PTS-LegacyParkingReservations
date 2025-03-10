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
			include_once '/var/www2/include/gr/authorization_service_ext.php';
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



		$dom_xml = DOMDocument::loadXML($xml);
		
/* 		
		$context = stream_context_create(array(
    "ssl"=>array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
));

$conteudosite = file_get_contents("http://parking.arizona.edu/xsl/main.xsl", false, $context); */

$dom_xsl = new DOMDocument();
//$dom_xsl->load('https://parking.arizona.edu/xsl/main.xsl');
$dom_xsl = simplexml_load_string('<?xml version="1.0" encoding="ISO-8859-1" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="navigation">
	<xsl:variable name="section" select="@section" />
	<ul id="navigation">
		<xsl:variable name="test" select="document(\'/navigation.xml\')"/>
		<xsl:for-each select="$test/navigation/section">
			<li>
				<xsl:choose>
					<xsl:when test="@path = $section">
						<xsl:attribute name="id">current_section</xsl:attribute>
						<xsl:value-of select="title"/>
						<xsl:if test="doc">
							<ul id="documents">
								<xsl:for-each select="doc">
									<li><a>
										<xsl:attribute name="href"><xsl:value-of select="../@path"/>/<xsl:value-of select="@file"/></xsl:attribute>
										<xsl:value-of select="current()"/>
									</a></li>
								</xsl:for-each>
							</ul>
						</xsl:if>
					</xsl:when>
					<xsl:otherwise>
						<a>
							<xsl:attribute name="href">/<xsl:value-of select="@path"/><xsl:value-of select="@file"/></xsl:attribute>
							<xsl:if test="description">
								<xsl:attribute name="title">
                           <xsl:value-of select="description"/>
								</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="title"/>
						</a>
					</xsl:otherwise>
				</xsl:choose>
			</li>
		</xsl:for-each>
	</ul>
</xsl:template>
<xsl:template match="document">
	<html>
		<head>
			<title><xsl:value-of select="contents/title"/></title>
       			<link rel="stylesheet" type="text/css">
				<xsl:attribute name="href">/css/<xsl:value-of select="@css"/>.css</xsl:attribute>
			</link>
			<link href="/xsl/print.css" rel="stylesheet" type="text/css" media="print"/>

                        <xsl:if test="/document/contents/refresh">
                                <meta http-equiv="refresh" content="180"/>
                        </xsl:if>
			<script language="JavaScript" type="text/javascript" src="https://parking.arizona.edu/js/base_orig.js"></script>

		</head>
                <body>
                        <xsl:attribute name="id">
                                <xsl:choose>
                                        <xsl:when test="/descendant::navigation">body_navigation</xsl:when>
                                        <xsl:otherwise>body_full</xsl:otherwise>
                                </xsl:choose>
                        </xsl:attribute>
			<xsl:apply-templates/>
                </body>
	</html>
</xsl:template>



<!-- Major sections of the document layout -->
<xsl:template match="header">
	<div id="header">
		<img src="/images/header-title.gif" id="banner" alt="Parking &amp; Transportation Services"/>
		<div id="stripe"><xsl:value-of select="current()"/></div>
		<img src="/images/header-logo.gif" id="logo" alt="PTS Logo"/>

		<!-- Create a "Tips" box for icon reference -->
		<xsl:variable name="pdf_count"><xsl:value-of select="count(/descendant::pdf)"/></xsl:variable>
		<xsl:variable name="link_count"><xsl:value-of select="count(/descendant::a[@out]) - 1"/></xsl:variable>
		<xsl:variable name="authorization"><xsl:value-of select="count(/descendant::authorization)"/></xsl:variable>
                <xsl:variable name="session_expired"><xsl:value-of select="count(/descendant::session_expired)"/></xsl:variable>
                <xsl:variable name="ip_filter"><xsl:value-of select="count(/descendant::ip_filter)"/></xsl:variable>
		<xsl:if test="$pdf_count + $link_count + $ip_filter + $authorization + $session_expired > 0">
			<ul id="tips">
				<li id="title">Tips</li>

                                <xsl:if test="$session_expired > 0">
                                        <li>
                                                <img src="/images/info.gif" alt="Info"/>
                                                Your session has timed out.&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;
                                        </li>
                                </xsl:if>

				<xsl:if test="$authorization > 0 and $session_expired = 0">
					<li>
						<img src="/images/lock.gif" alt="Secured"/>
						<xsl:choose>
							<xsl:when test="/descendant::authorization/@user">
								<b><xsl:value-of select="/descendant::authorization/@user"/></b> [<a href="?signout=true">Click Here to Sign Out</a>]&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;
							</xsl:when>
							<xsl:otherwise>
								Authorization Required [<a href="/help/netid.php" target="_blank">Help Using NetID</a>]&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;
							</xsl:otherwise>
						</xsl:choose>
					</li>
				</xsl:if>

                                <xsl:if test="$ip_filter > 0">
                                        <li>
                                                <img src="/images/info.gif" alt="Info"/>
                                                Internal Web Site
                                        </li>
                                </xsl:if>

				<xsl:if test="$pdf_count > 0">
					<li>
						<img src="/images/pdf.gif" alt="PDF"/> PDF
						<xsl:choose>
							<xsl:when test="$pdf_count = 1"> file</xsl:when>
							<xsl:otherwise> files</xsl:otherwise>
						</xsl:choose>
						[<a href="/help/pdf.php" target="_blank">Help Reading PDF</a>]
					</li>
				</xsl:if>

				<xsl:if test="$link_count > 0">
					<li>
						<img src="/images/link.gif" alt="Link"/> Non-PTS Web
						<xsl:choose>
							<xsl:when test="$link_count = 1"> page</xsl:when>
							<xsl:otherwise> pages</xsl:otherwise>
						</xsl:choose>
					</li>
				</xsl:if>
			</ul>
		</xsl:if>
		<!-- End of "Tips" box -->

	</div>
</xsl:template>

<xsl:template match="footer">
		<xsl:apply-templates/>
		<br/>
</xsl:template>

<xsl:template match="contents">
	<div>
                <xsl:attribute name="id">
                        <xsl:choose>
                                <xsl:when test="/descendant::navigation">contents_navigation</xsl:when>
                                <xsl:otherwise>contents_full</xsl:otherwise>
                                </xsl:choose>
                        </xsl:attribute>
        	<xsl:apply-templates/>
	</div>
</xsl:template>

<xsl:template match="title">
	<h1><xsl:value-of select="current()"/></h1>
</xsl:template>



<!-- Pass through selected HTML -->
<xsl:template match="p">
	<p>
		<xsl:if test="@class">
			<xsl:attribute name="class"><xsl:value-of select="@class"/></xsl:attribute>
		</xsl:if>
		<xsl:apply-templates/>
	</p>
</xsl:template>

<xsl:template match="a">
	<a>
		<xsl:if test="@href"><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute></xsl:if>
		<xsl:if test="@alt"><xsl:attribute name="alt"><xsl:value-of select="@alt"/></xsl:attribute></xsl:if>
		<xsl:if test="@class"><xsl:attribute name="class"><xsl:value-of select="@class"/></xsl:attribute></xsl:if>
		<xsl:if test="@out">
			<img src="/images/link.gif" alt="Link"/>
		</xsl:if>
		<xsl:apply-templates/>

	</a>
</xsl:template>

<xsl:template match="br">
	<br/>
</xsl:template>

<xsl:template match="b">
	<b><xsl:apply-templates/></b>
</xsl:template>

<xsl:template match="i">
	<i><xsl:apply-templates/></i>
</xsl:template>

<xsl:template match="img">
	<img>
		<xsl:attribute name="src"><xsl:value-of select="@src"/></xsl:attribute>
		<xsl:if test="@alt"><xsl:attribute name="alt"><xsl:value-of select="@alt"/></xsl:attribute></xsl:if>
		<xsl:if test="@height"><xsl:attribute name="height"><xsl:value-of select="@height"/></xsl:attribute></xsl:if>
		<xsl:if test="@width"><xsl:attribute name="width"><xsl:value-of select="@width"/></xsl:attribute></xsl:if>
	</img>
</xsl:template>

<xsl:template match="ul">
        <ul>
        <xsl:for-each select="li">
                <li><xsl:apply-templates/></li>
        </xsl:for-each>
        </ul>
</xsl:template>

<xsl:template match="h1">
        <h1><xsl:apply-templates/></h1>
</xsl:template>

<xsl:template match="h2">
        <h2><xsl:apply-templates/></h2>
</xsl:template>

<xsl:template match="h3">
	<h3><xsl:apply-templates/></h3>
</xsl:template>

<xsl:template match="div">
        <div>
                <xsl:if test="@style"><xsl:attribute name="style"><xsl:value-of select="@style"/></xsl:attribute></xsl:if>
                <xsl:if test="@class"><xsl:attribute name="class"><xsl:value-of select="@class"/></xsl:attribute></xsl:if>
                <xsl:apply-templates/>
        </div>
</xsl:template>
<xsl:template match="form_header">
        <div id="form_header">
                <xsl:variable name="current_step" select="@current"/>
                <xsl:for-each select="step">
                        <xsl:choose>
                                <xsl:when test="@number = $current_step">
                                        <span class="c_step_number"><xsl:value-of select="@number"/></span>
                                        <span class="c_step_name"><xsl:value-of select="current()"/></span>
                                </xsl:when>
                                <xsl:otherwise>
                                        <span class="step_number"><xsl:value-of select="@number"/></span>
                                        <span class="step_name"><xsl:value-of select="current()"/></span>
                                </xsl:otherwise>
                        </xsl:choose>
                </xsl:for-each>

                <xsl:if test="$current_step != count(step)">
                        <a href="?cancel=1" class="header_button">Cancel</a>

                        <xsl:if test="$current_step > 1">
                                <a class="header_button"><xsl:attribute name="href">?goback=<xsl:value-of select="$current_step - 1"/></xsl:attribute>Go Back</a>
                        </xsl:if>
                </xsl:if>


                <div id="description"><xsl:value-of select="description"/></div>

                <xsl:if test="error">
                        <div id="error"><img src="/images/alert.gif" style="margin-right: 0.5em;"/> <xsl:value-of select="error"/></div>
                </xsl:if>
        </div>
</xsl:template>

<xsl:template match="form">
	<form>
		<xsl:attribute name="method">
                        <xsl:choose><xsl:when test="@method = \'get\'">get</xsl:when><xsl:otherwise>post</xsl:otherwise></xsl:choose>
                </xsl:attribute>
		<xsl:attribute name="action"><xsl:value-of select="@action"/></xsl:attribute>

                <xsl:choose>
                <xsl:when test="@hide">
                        <xsl:attribute name="class">form_hide</xsl:attribute>
                        <xsl:apply-templates/>
                </xsl:when>
                <xsl:otherwise>
        		<table class="form_table">
	        		<xsl:if test="@name">
		        		<thead><tr><td class="heading" colspan="2">
			        		<xsl:value-of select="@name"/>
				        </td></tr></thead>
        			</xsl:if>

	        		<tbody>
		        		<xsl:apply-templates/>
			        </tbody>
        		</table>
	        </xsl:otherwise>
                </xsl:choose>

		<input type="hidden" name="form_submitted">
			<xsl:attribute name="value"><xsl:value-of select="@name"/></xsl:attribute>
		</input>
	</form>
</xsl:template>


<xsl:template match="field">
	<xsl:choose>
                <xsl:when test="@nolabel"><xsl:call-template name="field_inline"/></xsl:when>
		<xsl:otherwise><xsl:call-template name="field_label"/></xsl:otherwise>
	</xsl:choose>
</xsl:template>
<xsl:template name="field_label">
        <tr class="form_item">
		<xsl:choose>
			<xsl:when test="@error">
				<td class="label_error"><xsl:value-of select="@name"/></td>
			</xsl:when>
			<xsl:otherwise>
				<td class="label"><xsl:value-of select="@name"/></td>
			</xsl:otherwise>
		</xsl:choose>

		<td><xsl:call-template name="field_inline"/></td>
	</tr>
</xsl:template>
<xsl:template name="field_inline">
      	<xsl:if test="@error"><div class="error"><xsl:value-of select="@error"/></div></xsl:if>
	<xsl:if test="@leading_text">
		<xsl:value-of select="@leading_text"/>
	</xsl:if>
	<input>
		<xsl:attribute name="type">
			<xsl:choose>
			<xsl:when test="@password">password</xsl:when>
			<xsl:otherwise>text</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
		<xsl:attribute name="value"><xsl:value-of select="current()"/></xsl:attribute>
		<xsl:choose>
			<xsl:when test="@error">
				<xsl:attribute name="class">error</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name="class">field</xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:if test="@maxlength">
			<xsl:attribute name="maxlength"><xsl:value-of select="@maxlength"/></xsl:attribute>
		</xsl:if>
		<xsl:if test="@size">
			<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
		</xsl:if>
	</input>
        <xsl:if test="@note"><span class="note"><xsl:value-of select="@note"/></span></xsl:if>
	<xsl:if test="@trailing_text">
		<xsl:value-of select="@trailing_text"/>
	</xsl:if>
</xsl:template>

<xsl:template match="menu">
<tr class="form_item">
	<td class="label"><xsl:value-of select="@name"/></td>
	<td>
		<xsl:choose>
		<xsl:when test="not(item) and not(group)"><i><xsl:value-of select="@empty_note"/></i></xsl:when>
		<xsl:otherwise>
			<select>
				<xsl:choose>
				<xsl:when test="@multiple">
				<xsl:attribute name="multiple">multiple</xsl:attribute>
				<xsl:attribute name="name"><xsl:value-of select="@name"/>[]</xsl:attribute>
				</xsl:when>
				<xsl:otherwise><xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute></xsl:otherwise>
				</xsl:choose>
				<xsl:if test="@size"><xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute></xsl:if>
				<xsl:apply-templates/>
			</select>
		</xsl:otherwise>
		</xsl:choose>
	</td>
</tr>
</xsl:template>
<xsl:template match="group">
	<optgroup>
		<xsl:attribute name="label"><xsl:value-of select="@name"/></xsl:attribute>
		<xsl:apply-templates/>
	</optgroup>
</xsl:template>
<xsl:template match="item">
	<option>
		<xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
		<xsl:if test="@selected"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
		<xsl:value-of select="current()"/>
	</option>
</xsl:template>

<xsl:template match="radio_group">
	<tr class="form_item">
		<td class="label"><xsl:value-of select="@name"/></td>
		<td>
			<xsl:variable name="radio-groupname" select="@name"/>
			<xsl:for-each select="item">
				<xsl:if test="position()!=1"><br/></xsl:if>
				<input type="radio">
					<xsl:attribute name="name"><xsl:value-of select="$radio-groupname"/></xsl:attribute>
					<xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
					<xsl:if test="@selected"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<xsl:value-of select="current()"/>
			</xsl:for-each>
		</td>
	</tr>
</xsl:template>


<xsl:template match="checkbox">
	<xsl:choose>
		<xsl:when test="@ownrow"><xsl:call-template name="checkbox_row"/></xsl:when>
		<xsl:otherwise><xsl:call-template name="checkbox_inline"/></xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="checkbox_row">
	<tr class="form_item"><td colspan="2">
		<xsl:call-template name="checkbox_inline"/>
	</td></tr>
</xsl:template>

<xsl:template name="checkbox_inline">
        <input type="hidden" value="off">
                <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
        </input>

	<input type="checkbox">
		<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
		<xsl:if test="@selected">
			<xsl:attribute name="checked">checked</xsl:attribute>
		</xsl:if>
	</input>
	<xsl:if test="not(@nameless)"><xsl:value-of select="@name"/></xsl:if>
        <xsl:if test="@note"><span class="note"><xsl:value-of select="@note"/></span></xsl:if>
</xsl:template>


<xsl:template match="radiobutton">
	<xsl:choose>
		<xsl:when test="@ownrow"><xsl:call-template name="radiobutton_row"/></xsl:when>
		<xsl:otherwise><xsl:call-template name="radiobutton_inline"/></xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="radiobutton_row">
	<tr class="form_item"><td colspan="2">
		<xsl:call-template name="radiobutton_inline"/>
	</td></tr>
</xsl:template>

<xsl:template name="radiobutton_inline">
	<input type="radio">
		<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
                <xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
		<xsl:if test="@selected">
			<xsl:attribute name="checked">checked</xsl:attribute>
		</xsl:if>
	</input>
	<xsl:if test="not(@nameless)">
		<xsl:value-of select="@name"/>
	</xsl:if>
</xsl:template>


<xsl:template match="textarea">
	<tr class="form_item"><td colspan="2"><div class="centered">
		<div class="textarea_label">
			<xsl:value-of select="@name"/>
			<xsl:if test="@error">
				<span class="error"> (<xsl:value-of select="@error"/>)</span>
			</xsl:if>
		</div>
		<textarea>
			<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
			<xsl:attribute name="cols"><xsl:value-of select="@cols"/></xsl:attribute>
			<xsl:attribute name="rows"><xsl:value-of select="@rows"/></xsl:attribute>
			<xsl:if test="@error"><xsl:attribute name="class">error</xsl:attribute></xsl:if>
			<xsl:value-of select="current()"/>
		</textarea>
	</div></td></tr>
</xsl:template>

<xsl:template match="button">
	<xsl:choose>
		<xsl:when test="@ownrow"><xsl:call-template name="button_row"/></xsl:when>
		<xsl:otherwise><xsl:call-template name="button_inline"/></xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="button_row">
	<tr class="form_item"><td colspan="2" class="centered">
		<xsl:call-template name="button_inline"/>
	</td></tr>
</xsl:template>

<xsl:template name="button_inline">
	<input name="submit_button">
		<xsl:attribute name="type"><xsl:value-of select="@type"/></xsl:attribute>
		<xsl:attribute name="value"><xsl:value-of select="@name"/></xsl:attribute>
		<xsl:if test="@src"><xsl:attribute name="src"><xsl:value-of select="@src"/></xsl:attribute></xsl:if>
		<xsl:attribute name="class">default_button</xsl:attribute>
	</input>
        <xsl:if test="@goto">
                <input type="hidden">
                        <xsl:attribute name="name"><xsl:value-of select="@goto_name"/></xsl:attribute>
                        <xsl:attribute name="value"><xsl:value-of select="@goto"/></xsl:attribute>
                </input>
        </xsl:if>
</xsl:template>

<xsl:template match="grid">
        <xsl:choose>
                <xsl:when test="@inline"><xsl:call-template name="grid_inline"/></xsl:when>
                <xsl:otherwise>
                        <tr class="form_item"><td colspan="2">
                                <xsl:call-template name="grid_inline"/>
                        </td></tr>
                </xsl:otherwise>
        </xsl:choose>
</xsl:template>

<xsl:template name="grid_inline" match="grid_inline">
	<table class="data_grid">
                <xsl:if test="heading"><thead>
			<xsl:for-each select="heading">
				<tr class="heading">
					<xsl:for-each select="item">
        					<td class="grid_item"><xsl:apply-templates/></td>
					</xsl:for-each>
				</tr>
			</xsl:for-each>
		</thead></xsl:if>
		<tbody>
			<xsl:for-each select="row">
				<tr>
					<xsl:attribute name="class">
						<xsl:choose>
						<xsl:when test="position() mod 2 = 0">grid_row_alt</xsl:when>
						<xsl:otherwise>grid_row</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>

					<xsl:for-each select="item">
						<td class="grid_item"><xsl:apply-templates/></td>
					</xsl:for-each>
				</tr>
			</xsl:for-each>
		</tbody>
	</table>

</xsl:template>

<xsl:template match="form_display">
	<tr class="form_item">
		<td class="label"><xsl:value-of select="@name"/></td>
		<td><xsl:apply-templates/></td>
	</tr>
</xsl:template>

<xsl:template match="form_raw">
	<tr class="form_item"><td colspan="2" class="form_raw">
		<xsl:apply-templates/>
	</td></tr>
</xsl:template>

<xsl:template match="input[@type=\'hidden\']">
        <input type="hidden">
                <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
                <xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
        </input>
</xsl:template>
<xsl:template match="pdf">
	<a>
		<xsl:attribute name="href"><xsl:value-of select="@file"/></xsl:attribute>
		<img src="/images/pdf.gif" alt="PDF File"/> <xsl:value-of select="current()"/>
	</a>
</xsl:template>

<xsl:template match="calendar">

	<table border="0" width="252" class="calendar" style="font-weight:bold;">
		<tr>
			<xsl:if test="@prev">
				<td width="5" align="left" valign="top">
					<a><xsl:attribute name="href"><xsl:value-of select="@prev"/></xsl:attribute>&lt;&lt;</a>
				</td>
			</xsl:if>
			<td align="center">
				<xsl:value-of select="@title"/>
			</td>
			<xsl:if test="@next">
				<td width="5" align="right" valign="top">
					<a><xsl:attribute name="href"><xsl:value-of select="@next"/></xsl:attribute>&gt;&gt;</a>
				</td>
			</xsl:if>
		</tr>
	</table>

	<table class="calendar">

		<th>Sun</th>
		<th>Mon</th>
		<th>Tue</th>
		<th>Wed</th>
		<th>Thu</th>
		<th>Fri</th>
		<th>Sat</th>

		<xsl:for-each select="week">
			<tr>
				<xsl:for-each select="day">
					<td>
						<xsl:if test="current() = \' \'">
							<xsl:attribute name="class">empty</xsl:attribute>
						</xsl:if>
						<xsl:if test="@class">
							<xsl:attribute name="class">
								<xsl:value-of select="@class"/>
							</xsl:attribute>
						</xsl:if>

						<!-- Parse links here (redundant?) -->
						<xsl:choose>
							<xsl:when test="@href">
								<a>
									<xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute>
									<xsl:if test="@title">
										<xsl:attribute name="title"><xsl:value-of select="@title"/></xsl:attribute>
									</xsl:if>
									<xsl:value-of select="current()"/>
								</a>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="current()"/>
							</xsl:otherwise>
						</xsl:choose>

					</td>
				</xsl:for-each>
			</tr>
		</xsl:for-each>
	</table>
</xsl:template>
</xsl:stylesheet>');
	//	$dom_xsl = DOMDocument::load('http://parking.arizona.edu/xsl/main.xsl');

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
			var yyyy = todayd.getYear(); //  /var/www2/include/gr/documnet
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

/* class error extends Exception {
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
