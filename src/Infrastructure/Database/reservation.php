<?php
namespace App\Infrastructure\Database;
use App\Application\ResponseEmitter\PDF\Cezpdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class reservation {

	// conf is the reservation confirmation number, or the FIRST reservation confirmation number in the case of multi-date reservations
	var $conf;
	// resids is all of the confirmation numbers
	var $resids;
	// error is the error index
	var $error;
	// error msg is the optional error message
	var $errormsg;
	// errordate is the reservation date which caused the error
	var $errordate;

	// resTranspose takes the object var as a key and the database field equivalent as a value
	var $resTranspose = array(
		"resid"=>"RESERVATION_ID",
		"resdate"=>"RESDATE",
		"resenter"=>"RESSTART",
		"resexit"=>"RESEND",
		"price"=>"PRICE",
		"userid"=>"USER_ID_FK",
		"garageid"=>"GARAGE_ID_FK",
		"allowextra"=>"ALLOW_EXTRA",
		"comego"=>"COME_AND_GO",
		"createdate"=>"CREATION_DATE",
		"addguests"=>"GUESTS_OFFCAMPUS",
		"deptno"=>"DEPT_NO_FK",
		"frs"=>"FRS_FK",
		"KFS_SUB_ACCOUNT_FK"=>"KFS_SUB_ACCOUNT_FK",
		"KFS_SUB_OBJECT_CODE_FK"=>"KFS_SUB_OBJECT_CODE_FK"
	);

	var $humanFields = array(
		"resid"=>"Confirmation Number",
		"resdate"=>"Reservation Date",
		"resenter"=>"Start Time",
		"resexit"=>"Stop Time",
		"price"=>"Price per Space",
		"garageName"=>"Garage",
		"deptName"=>"Department",
		"frs"=>"FRS",
		"KFS_SUB_ACCOUNT_FK"=>"KFS_SUB_ACCOUNT_FK",
		"KFS_SUB_OBJECT_CODE_FK"=>"KFS_SUB_OBJECT_CODE_FK",
		"userName"=>"Reserved By",
		"guestList"=>"Guest List/Group Name",
		"groupCount"=>"Spaces",
		"comego"=>"Guest May Come and Go"
	);
	//	"addguests"=>"Additional Guests",
	//	"allowextra"=>"Allow Extra Guests",

	// variables for single res
	var $resid;
	var $resdate;
	var $resenter;
	var $resexit;
	var $price;
	var $userid;
	var $garageid;
	var $allowextra;
	var $comego;
	var $createdate;
	var $addguests;
	var $deptno;
	var $frs;
	var $KFS_SUB_ACCOUNT_FK;
	var $KFS_SUB_OBJECT_CODE_FK;
	var $guestList;
    var $groupCount;
	var $garageName;
	var $deptName;
	var $userName;
	var $active;
	var $numNotes;

	var $resinfo;

	var $owner;

	var $phone = "(520) 621-3710";

	var $otherDates;


	function __construct () {}



	function errorCheck ($frs,$customer,$garageid,$dates,$stime,$etime,$gg,$option1,$option2,$comeGo,$extra,$addGuests)
	{
		//##########  FUNCTION NOT USED - I THINK #############

		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		// frs validation check
		if (!preg_match("/^[\w\d\-\/ \.]*$/",$frs)) {
			$this->error = 'frsInvalid';
			return false;
		}

		// Safe Query!  // frs access check
		$query = "SELECT U.USER_ID FROM PARKING.GR_FRS F INNER JOIN
			(PARKING.GR_USER_DEPARTMENT D INNER JOIN PARKING.GR_USER U ON USER_ID = USER_ID_FK) ON F.DEPT_NO_FK = D.DEPT_NO_FK
			WHERE FRS = '$frs'";
		var_dump("Query 1;");
		var_dump($query);
		$dbConn->query($query);
		if (!$dbConn->rows) return 'frs-notfound';
		if (!in_array($customer['userid'],$dbConn->results['USER_ID'])) return 'frs-access';
		$this->frs = $frs;

		// date check

		if ($dates && !is_array($dates)) $dates = array($dates);
		elseif (!$dates) return 'noDates';
		$this->resdate = $dates;

		// enter time check
		$timeCheck = "/[0-9]{1,2}\:[0-9]{2} am|AM|pm|PM/";
		if (!preg_match($timeCheck,$stime)) return 'notTime';
		if (strpos($stime,':')==1)
			$this->resenter = "0".$stime;

		// exit time check
		if (!preg_match($timeCheck,$etime)) return 'notTime';
		if (strpos($etime,':')==1)
			$this->resexit = "0".$etime;
	}



	function newRes ($frs,$KFS_SUB_ACCOUNT_FK,$KFS_SUB_OBJECT_CODE_FK,$customer,$garageid,$dates,$stime,$etime,$gg,$option1,$option2,$comeGo,$extra,$addGuests='',$notes=false)
	{
		global $dbConn, $pdfConfirmFile;
		if (!isset($dbConn)) $dbConn = new database();

		$msg1 = $msg2 = $msg3 = '';
		$wasInserted = false;

		// frs validation check
		if (!preg_match("/^[\w\d\-\/ \.]*$/",$frs)) {
			$this->error = 'frsInvalid';
			return false;
		}
		if (!preg_match("/^[\w\d\-\/ \.]*$/",$KFS_SUB_ACCOUNT_FK)) {
			$this->error = 'subObjInvalid';
			return false;
		}
		if (!preg_match("/^[\w\d\-\/ \.]*$/",$KFS_SUB_OBJECT_CODE_FK)) {
			$this->error = 'subObjInvalid';
			return false;
		}

		// error check
		if ($dates && !is_array($dates))
			$dates = array($dates);
		elseif (!$dates) {
			$this->error = 'noDates';
			return false;
		}
		if (strpos($stime,':')==1)
			$stime = "0".$stime;
		if (strpos($etime,':')==1)
			$etime = "0".$etime;
		if (strlen($stime)<7 || strpos($stime,':')!=2 || substr(strtolower($stime),-1)!='m' || strlen($etime)<7 || strpos($etime,':')!=2 || substr(strtolower($etime),-1)!='m') {
			$this->error = 'notTime';
			return false;
		}
		else {
			$search = array('.');
			$replace = array('');
			$stime = str_replace($search,$replace,strtoupper($stime));
			$etime = str_replace($search,$replace,strtoupper($etime));
			$stimeHour = intval(substr($stime,0,2));
			$etimeHour = intval(substr($etime,0,2));
			if ($stimeHour==0 || $stimeHour>12 || $etimeHour==0 || $etimeHour>12) {
				$this->error = 'notTime';
				return false;
			}
		}

		if ($gg=="guest") {
			if (!count($option1)) $this->error = 'oneGuest';
			$spaces = count($option1);
		}
		elseif ($gg=="group") {
			if (is_array($option1)) {
				if (!count($option1) || (count($option1)==1 && !trim($option1[0]))) $this->error = 'groupName';
				if (!trim($option1[0])) $this->error = 'groupName';
			}
			elseif (!trim($option1)) $this->error = 'groupName';
			$spaces = $option2;
		}

		//if (!$addGuests && $addGuests!=0)
		//	$this->error = 'addGuests';
		//elseif (ord(substr($addGuests,0,1))<48 || ord(substr($addGuests,0,1))>57 || ord(substr($addGuests,-1))<48 || ord(substr($addGuests,-1))>57)
		//	$this->error = 'addGuests';

		if ($this->error) return false;

		if ($spaces>25 && $customer['auth']<4) {
			$this->error = 'groupSize';
			return false;
		}

		// make sure there are no duplicate dates
		$dates = array_unique($dates);

		//$dbConn->query("SELECT SYSDATE AS SSDD FROM dual");
		$dbConn->query("SELECT TO_CHAR(SYSDATE, 'MM/DD/YYYY HH:MI:SS AM') AS SSDD FROM dual");
		if ($dbConn->rows)
			$oraSysDate = $dbConn->results["SSDD"][0];

		// Safe query!  get department for this frs
		$dbConn->query("SELECT DEPT_NO_FK FROM PARKING.GR_FRS WHERE FRS='$frs'");
		if ($dbConn->rows) $dept = $dbConn->results["DEPT_NO_FK"][0];
		else {
			$this->error = 'frsInvalid';
			return false;
		}

		// loop through the dates
		for ($i=0; $i<count($dates); $i++) {
			$dateCheck = explode('/',$dates[$i]);

			// don't think $dc is even used anywhere!
			foreach ($dateCheck as $dc)
				if (strlen($dc)<2)
					$dc = "0".$dc;

			if (count($dateCheck)!=3 || strlen(implode('/',$dateCheck))!=10 || !checkdate($dateCheck[0],$dateCheck[1],$dateCheck[2])) {
				$this->error = 'notDate';
				$this->errordate = $dates[$i];
				return false;
			}
			$date = strtotime($dates[$i]);
			$formattedDate = date("m/d/y",$date);

			if ($formattedDate==date("m/d/y"))
				$this->error = "today";
			elseif ($date<strtotime("today"))
				$this->error = "beforeToday";

			if ($this->error && !$this->errordate) {
				$this->errordate = $formattedDate;
				return false;
			}

			// make sure the department has not reached their max
			$this->checkResCount($customer, $dept, $garageid, $formattedDate, $spaces);

			//***************************************************************************//
			//***************************************************************************//
			//***************************************************************************//
			//***************************************************************************//
			// Prevent garage reservations by customers at Cherry Ave. (8), Second St. (3),
			//  or Sixth St. (4) garages on football game days.
			// These reservations will be sold by Visitor Programs through the administrator
			//  section of the Departmental Visitor Garage Reservations System.
			// Uses checkGarageMax below
			// Added by jsc - 20070915
			if (($garageid==8) || ($garageid==3) || ($garageid==4)) {
				if ($formattedDate=="09/29/07")
					$spaces = 500;
				if ($formattedDate=="10/20/07")
					$spaces = 500;
				if ($formattedDate=="11/03/07")
					$spaces = 500;
				if ($formattedDate=="11/15/07")
					$spaces = 500;
			}
			//***************************************************************************//
			//***************************************************************************//
			//***************************************************************************//
			//***************************************************************************//

			// make sure the garage is not maxed
			if (!$this->error)
				$this->checkGarageMax($garageid,$formattedDate,$spaces);

			if (!$this->error) {

				if (!$extra)
					$extra = '0';

				$query = "INSERT INTO PARKING.GR_RESERVATION (RESERVATION_ID, ENTER_TIME, EXIT_TIME, RES_DATE, USER_ID_FK, GARAGE_ID_FK, ALLOW_EXTRA, PRICE, COME_AND_GO, GUESTS_OFFCAMPUS, DEPT_NO_FK, ACTIVE, FRS_FK, KFS_SUB_ACCOUNT_FK, KFS_SUB_OBJECT_CODE_FK, CREATION_DATE)";
				$query .= " VALUES (PARKING.GR_RESERVATION_ID.NEXTVAL, TO_DATE(:stime,'MM/DD/YYYY HH:MI AM'), TO_DATE(:etime,'MM/DD/YYYY HH:MI AM'), TO_DATE(:formattedDate,'MM/DD/YY'), :c_userid, :garageid, :extra,";
				$qVars = array(
									'stime' => '01/01/2005 ' . $stime,
									'etime' => '01/01/2005 ' . $etime,
									'formattedDate' => $formattedDate,
									'c_userid' => $customer["userid"],
									'garageid' => $garageid,
									'extra' => $extra
									);
				if (($garageid==7) || ($garageid==13) ) {
					$new_price =9;
					$COME_AND_GO_SQL = ",0,";
				} else {
					if ($comeGo=="1") {
						// second st garage is 3
						$new_price = ($garageid==3) ? $_SESSION['G_price_comeandgo_second'] : $_SESSION['G_price_comeandgo'];
						$COME_AND_GO_SQL = ",1,";
					} else {
						// second st garage is 3
						$new_price = ($garageid==3) ? $_SESSION['G_price_second'] : $_SESSION['G_price_regular'];
						$COME_AND_GO_SQL = ",0,";
					}
				}
				// PBC Lot 10003 does not have comego
				$new_price = ($garageid==12) ? $_SESSION['G_price_pbc_10003'] : $new_price;

				$query .= $new_price;
				$query .= $COME_AND_GO_SQL;

				if ($addGuests=='')
					$addGuests = 0;

				//$query .= "$addGuests,'$dept',1,'$frs',SYSDATE)";
				$query .= ":addGuests, :dept, 1, :frs, :KFS_SUB_ACCOUNT_FK, :KFS_SUB_OBJECT_CODE_FK, TO_DATE('$oraSysDate', 'MM/DD/YYYY HH:MI:SS AM'))";
				$qVars['addGuests']	= $addGuests;
				$qVars['dept']			= $dept;
				$qVars['frs']			= $frs;
				$qVars['KFS_SUB_ACCOUNT_FK'] = $KFS_SUB_ACCOUNT_FK;
				$qVars['KFS_SUB_OBJECT_CODE_FK'] = $KFS_SUB_OBJECT_CODE_FK;
				$conf = $dbConn->sSeqInsert($query, "PARKING.GR_RESERVATION_ID", $qVars);
				$wasInserted = "query: $query"."
						qvars:
						'stime' => '01/01/2005 ' . $stime,
						'etime' => '01/01/2005 ' . $etime,
						'formattedDate' => $formattedDate,
						'c_userid' => ".$customer["userid"].",
						'garageid' => $garageid,
						'extra' => $extra,
						'addGuests' => $addGuests,
						'dept' => $dept,
						'frs' => $frs,
						'KFS_SUB_ACCOUNT_FK' => $KFS_SUB_ACCOUNT_FK,
						'KFS_SUB_OBJECT_CODE_FK' => $KFS_SUB_OBJECT_CODE_FK,
						";

				// make sure res is actually there
				/*$dbConn->query("SELECT * FROM PARKING.GR_RESERVATION WHERE RESERVATION_ID=$conf");
				//if ($dbConn->error) {
				if (!$dbConn->rows) {
					$conf = false;
					$this->error = 'db';
					$this->errormsg = $dbConn->error;
					return false;
				}*/
				if (!$conf) {
					$this->error = 'noConf';
					$this->errordate = $dates[$i];
					return false;
				}

				if (!$this->conf && !$this->error) {
					// This should be the very first loop ($i is 0). Generate .pdf if bio-med.
					$this->conf = $conf;

					// get res ids (they will always be sequential if multiple dates)
					$this->resid = array($this->conf);
					$resCount = count($dates)-1;
					if ($resCount>0) {
						for ($resThru = ($this->conf+1); $resThru<=($this->conf+$resCount); $resThru++) {
							$this->resid[] = $resThru;
						}
					}
					// get frs
					$this->frs = $frs;
					$this->KFS_SUB_ACCOUNT_FK = $KFS_SUB_ACCOUNT_FK;
					$this->KFS_SUB_OBJECT_CODE_FK = $KFS_SUB_OBJECT_CODE_FK;
					// get garage name
					$this->garageName = getGarageByID($garageid);
					// get guest list or group name
					if ($gg=='guest' && !$this->guestList) {
						$option1 = array_unique($option1);
						$this->guestList = implode(' | ',$option1);
						$this->groupCount = count($option1);
					}
					elseif ($gg=='group' && !$this->guestList) {
						if (is_array($option1)) $this->guestList = $option1[0];
						else $this->guestList = $option1;
						$this->groupCount = $option2;
					}

					$garageLinkTxt1 = $garageLinkTxt2 = "";
					$garageTxt = getGarageByID($garageid);
					$makePDF = preg_match('/(BioMedical)/i', $garageTxt);
					$pbc_lot_num2 = preg_match('/(10003)/i', $garageTxt) ? '10003' : '10002';
					if ($pbc_lot_num2=='10003') {  // added br drw on 9/20/2017 so 10003 will not get pdf 
						$makePDF=false;
					}
					if ($makePDF)
					{
						$pbc_lot_num = preg_match('/(10003)/i', $garageTxt) ? '10003' : '10002';
						$pbc_lot_loc = ($pbc_lot_num=='10003') ? "Lot 10003,\nLocated at 550 E Van Buren, 85004" : "Lot 10002,\nLocated at 714 E Van Buren, 85004";
						// Use 'Phoenix BioMedical Campus' because don't want "Phoenix BioMedical 10003"
						$garageTxt = 'Phoenix BioMedical Campus ' . $pbc_lot_loc;
						$garageLinkTxt1 = "To view a map of Phoenix BioMedical parking lots, please visit our web site:\nhttps://parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf\n\n";
						$garageLinkTxt2 =         "parking.arizona.edu/pdf/maps/phoenixmedicalcenterlot.pdf";
					}

					$note = "Created";
					$resDateTime = "$dates[0] from $stime to $etime";
					$codeEmailNotice="";
					if ($garageTxt=="South Stadium Garage" || $garageTxt=="Highland Avenue Garage" ) {
							$codeEmailNotice = "\n *You will receive a separate email with validation codes.";
					}
					$msg1 .= "\nThis message is to confirm that your parking reservation has been placed. $codeEmailNotice \n\n";

					$msg3 .= $this->groupCount . " space(s) will be reserved in the ";
					$msg3 .= "$garageTxt\n";
					$msg3 .= "$resDateTime\n";
					$recurAppend  = '';
					if (count($dates)>1) {
						$datesEdit = $dates;
						array_shift($datesEdit); // Because $dates[0] was already used above.
						$resDateRecur = "Recurring on ".implode(', ',$datesEdit);
						$recurAppend  = ''; // " (each day)"; // got rid of this - SR https://www.pts.arizona.edu/servicerequest/index.php?rqid=c12958611
						$msg3 .= "$resDateRecur.\n";
					}
					$msg3 .= "Guest List/Group Name: ".unmake_htmlentities($this->guestList)."\n\n";
					$msg3 .= "This reservation will be billed to KFS account ".$this->frs.".\n";
					if (@$this->KFS_SUB_ACCOUNT_FK)
						$msg3 .= "    (KFS Sub Acct.:".$this->KFS_SUB_ACCOUNT_FK.")\n";
					if (@$this->KFS_SUB_OBJECT_CODE_FK)
						$msg3 .= "    (Sub Obj. Code:".$this->KFS_SUB_OBJECT_CODE_FK.")\n";
					$msg3 .= "\n";
					$justConNums=implode(", ",$this->resid);
					$confNums = "Confirmation Number(s): ".implode(", ",$this->resid);
					$msg3 .= "$confNums\n\n";
					$msg3 .= "$garageLinkTxt1";
						if ($garageTxt!=="South Stadium Garage" && $garageTxt!=="Highland Avenue Garage" && $garageTxt!=="Second Street Garage") {
							$msg3 .= "Please share the following instructions with your guest: \n https://parking.arizona.edu/pdf/garage-instructions.pdf \n\n";
						}
					
					if ($garageTxt=="Second Street Garage") {
						$msg3 .= "Please share the following instructions with your guest: \n https://parking.arizona.edu/pdf/garage-instructions.pdf  \n\n ";
					}
				//	$msg3 .= "$garageLinkTxt1";
					
					
					$msg3 .= "Visitor Programs\nUA Parking & Transportation Services\n1117 E. Sixth Street\nTucson, AZ 85721-0181\n(520) 621-3710\n";
					/*$dbConn->query("SELECT RESERVATION_ID FROM PARKING.GR_RESERVATION INNER JOIN PARKING.GR_GUEST ON RESERVATION_ID=RESERVATION_ID_FK WHERE RESERVATION_ID IN (".implode(",",$this->resid).")");
					if (!$dbConn->rows || $dbConn->rows!=count($this->resid)) {
						$this->error = 'db';
						return false;
					}else*/

					if ($makePDF) {

						// Get department name.
						$deptNameTmp = '';
						if ($dept && ctype_alnum($dept)) {
							$dbConn->query("SELECT DEPT_NAME FROM PARKING.GR_DEPARTMENT WHERE DEPT_NO='$dept'");
							if ($dbConn->rows)
								$deptNameTmp = $dbConn->results['DEPT_NAME'][0];
						}
						// Just make some random-ish pdf file name so that it will be hard to find it.
						$pdfConfirmFile = $this->resid[0] . '_' . ($this->resid[0] * 13 + 846756) . '.pdf';

						$msg2 = "You can print your [PDF] confirmation here: \nhttps://parking.arizona.edu/parking/garage-reservation/resPDF/$pdfConfirmFile\n\n";

						require('/var/www2/include/pdf/class.ezpdf.php');

						$pdf = new Cezpdf();
						$pdf->selectFont('/var/www2/include/pdf/fonts/Helvetica.afm');
						$pdf->ezText($garageTxt, 21, array("right"=>150, "justification"=>"center"));
						$pdf->ezText($garageLinkTxt2, 14, array("right"=>150, "justification"=>"center"));
						$pdf->ezText("\nDepartment: $deptNameTmp", 16, array("right"=>150));
						$pdf->ezText("\n$confNums", 20, array("right"=>150));
						$pdf->ezText("\n$resDateTime", 20, array("right"=>150));
						if (isset($resDateRecur))
							$pdf->ezText("$resDateRecur", 18, array("right"=>150));
						//$pdf->ezText("Spaces: $this->groupCount"."$recurAppend   Additional Guests: $addGuests", 18, array("right"=>150));
						$pdf->ezText("Spaces: $this->groupCount"."$recurAppend", 18, array("right"=>150));
						$pdf->ezText("\n\n\n\n\n\nPlace on driver side dashboard of vehicle, without obstruction", 12, array("justification"=>"center"));
						$pdf->ezText("\n- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - \n", 10, array("justification"=>"center"));
						//$pdf->ezText("\n\n\nBusiness Office\nParking & Transportation Services \n1117 E. Sixth Street \nPO Box 210181 \nTucson, AZ 85721-0181 \nPH: (520) 621-6912\n", 10);
						$pdf->addPngFromFile($_SERVER['DOCUMENT_ROOT'].'/parking/garage-reservation/administrator/pts_logo.png', 460, 735, 120);
						$pdf->addPngFromFile($_SERVER['DOCUMENT_ROOT'].'/parking/garage-reservation/administrator/ptsAddress.png', 450, 615, 130);
						$pdf->selectFont('/var/www2/include/pdf/fonts/Times-BoldItalic.afm');
						$pdf->saveState();
						$pdf->setColor(0.9,0.9,0.9);
						file_put_contents($_SERVER['DOCUMENT_ROOT']."/parking/garage-reservation/resPDF/$pdfConfirmFile", $pdf->ezOutput());
					} else {
			
						
					}
				}

				elseif ($i>0) {
					$note = "Duplicated from $this->conf";
				}

				if (!$this->error) {
					// 20060130, the NOTE_PK was being violated meaning that it was attempting to enter a reservation note twice
					if (!isset($noteUsed))
						$noteUsed = array();
					if (!in_array($conf,$noteUsed)) {
						$noteUsed[] = $conf;
						$this->resNote($conf, $customer["userid"], $note);
					}
					if ($gg=="guest") $this->addGuest($conf,$option1,1,0,0,true);
					else $this->addGuest($conf,$option1,$option2,0,0,true);
				}
			}
			else {
				return false;
			}
		}
		if (isset($_SESSION['cuinfo']['email'])) {
			
			
								if ($garageTxt=="South Stadium Garage" || $garageTxt=="Highland Avenue Garage" ) {


									$gLocation=($garageTxt=="South Stadium Garage")? "STA" : "HND" ;
									require_once('/var/www2/include/flowbird-include/reservationcontroller.php');
									$numberOfTickets=count($dates)*$this->groupCount;
									$rc=new ReservationController();
									$kfsInformation=$rc->getKFSInformation($frs);
									$package=new \stdClass();
									$package->FBTICKETVALUE=9;
									$package->FBTICKETTYPE='SINGLE';
									$package->FBTICKETLOCATION=$gLocation;
									$package->FBNUMBEROFTICKETS=$numberOfTickets;
									$package->TOTALORDERCOST=$numberOfTickets*9;
									$package->CUSTOMERNAME= $_SESSION['eds_data']['sn'] . ', ' . $_SESSION['eds_data']['givenname'];
									$package->CUSTOMEREMAIL=$_SESSION['eds_data']['mail'];
									$package->CUSTOMERPHONE=$_SESSION['eds_data']['employeephone'];
									$package->KFSNUMBER=$frs;
									$package->DEPARTMENTNAME=$kfsInformation->DEPARTMENTNAME;
									$package->RESERVATIONDATE=$dates[0];
									$package->RESERVATIONVISITORCOUNT=$this->groupCount;
									$package->RESERVATIONNUMBER=$justConNums;
									$package->RESERVATIONDATES=implode(", ",$dates);
// echo var_dump($package);
// exit;
								$notifcationRecipiants=$rc->processFlowbirdReservation($package);



		        $from = "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\nBcc:<PTS-IT-Emails@email.arizona.edu>\r\n";
   $recipient="jennyb@arizona.edu,staceyg@arizona.edu";
									$recipient=$notifcationRecipiants;
   $subject=$garageTxt.' New Garage Reservation';
   $text=$msg3;
   $result=mail($recipient, $subject, $msg3, "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\nBcc:<PTS-IT-Emails@email.arizona.edu>\r\n");
		} else {
			$wasEmailed = $this->send_email($_SESSION['cuinfo']['email'], 'Garage Reservation Confirmation', $msg1.$msg2.$msg3, "", "PTS-IT-Emails@email.arizona.edu");
			// mail($_SESSION['cuinfo']['email'], 'Garage Reservation Confirmation', $msg1.$msg2.$msg3, "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\nBcc:<PTS-IT-Emails@email.arizona.edu>\r\n");
			//mail('jbrabec@email.arizona.edu', 'Garage Reservation conf 2', $msg1.$msg2.$msg3, "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\n");			
		}
			
			

			
			
			
			
			
			
		} else {
			$wasEmailed = false;
		}

		if ($wasInserted && !$wasEmailed) {
			$msg_err = '~~~~~~~~~~~~~~ CUST EMAIL: ' . $_SESSION['cuinfo']['email'] . "\n\n";
			$msg_err .= '~~~~~~~~~~~~~~ QUERY: ' . $wasInserted."\n\n";
			$msg_err .= '~~~~~~~~~~~~~~ EMAIL MSG: ' . $msg1.$msg2.$msg3;
			//	mail('jbrabec@email.arizona.edu', 'Garage Reservation Inserted but NOT emailed', $msg_err, "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\nBcc:<PTS-IT-Emails@email.arizona.edu>\r\n");
			$this->send_email($_SESSION['cuinfo']['email'], 'Garage Reservation Inserted but NOT emailed.', $msg_err, "");
			// mail('PTS-IT-Emails@email.arizona.edu', 'Garage Reservation Inserted but NOT emailed', $msg_err, "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\n");
		}
	}



	function getRes ($resid)
	{
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		if (!preg_match("/^[\w\d\-\/ \.]*$/",$resid)) {
			echo '!!!!!!!!!! ERROR: Bad Reservation ID !!!!!!!!!!!!';
			return false;
		}

		// Safe Query!
		$dbConn->query("SELECT R.*, TO_CHAR(RES_DATE,'MM/DD/YYYY') AS RESDATE, TO_CHAR(ENTER_TIME,'HH:MI AM') AS RESSTART, TO_CHAR(EXIT_TIME,'HH:MI AM') AS RESEND, GARAGE_NAME, D.DEPT_NAME, U.USER_NAME FROM PARKING.GR_RESERVATION R INNER JOIN PARKING.GR_DEPARTMENT D ON DEPT_NO_FK=DEPT_NO INNER JOIN PARKING.GR_USER U ON USER_ID_FK=USER_ID, PARKING.GR_GARAGE WHERE GARAGE_ID=GARAGE_ID_FK AND RESERVATION_ID=$resid");

		if (!$dbConn->rows) return false;
		elseif ($dbConn->rows!=1) return false;
		else {
			$this->resinfo = $dbConn->results;

			foreach ($this->resTranspose as $var=>$field) {
				$this->$var = $dbConn->results[$field][0];
			}
			$this->deptName = $dbConn->results["DEPT_NAME"][0];
			$this->garageName = $dbConn->results["GARAGE_NAME"][0];
			$this->userName = $dbConn->results["USER_NAME"][0];
			$this->active = $dbConn->results["ACTIVE"][0];

			$this->getGuests($resid);


			// Find other reservations which have pretty much everything the same except the date. (safe query)
			$dbConn->query("SELECT USER_ID_FK, TO_CHAR(CREATION_DATE, 'DD-Mon-YYYY HH:MI:SS AM') AS CCD FROM PARKING.GR_RESERVATION WHERE RESERVATION_ID=$resid");
			// query is safe!
			$query = "SELECT RESERVATION_ID, TO_CHAR(RES_DATE,'MM/DD/YYYY') AS RESDATE FROM PARKING.GR_RESERVATION
						WHERE ACTIVE=1 AND
							TO_CHAR(CREATION_DATE, 'DD-Mon-YYYY HH:MI:SS AM') = '" . $dbConn->results["CCD"][0] . "' AND
							USER_ID_FK = '" . $dbConn->results["USER_ID_FK"][0] . "'
							ORDER BY RESDATE";
			$dbConn->query($query);
			if ($dbConn->rows) {
				$this->otherDates = array();
				for ($i=0; $i<$dbConn->rows; $i++) {
					$RESERVATION_ID = $dbConn->results["RESERVATION_ID"][$i]; // drw change
					$this->otherDates[$RESERVATION_ID] = $dbConn->results["RESDATE"][$i]; // drw change
				}
			}
		}
	}


	function getOtherRes ($resid)
	{
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		if (!preg_match("/^[\w\d\-\/ \.]*$/",$resid)) {
			echo '!!!!!!!!!! ERROR: Bad Reservation ID !!!!!!!!!!!!';
			return false;
		}

		// Safe Query!
		$dbConn->query("SELECT RESERVATION_ID_FK,TO_CHAR(RES_DATE,'MM/DD/YYYY') AS RESDATE FROM PARKING.GR_RESERVATION_NOTE WHERE NOTE='Duplicated from $resid' AND TRUNC(RES_DATE)>TRUNC(SYSDATE) AND ACTIVE=1 ORDER BY RESERVATION_ID_FK");

		if ($dbConn->rows) {
			$otherRes = array();
			for ($i=0; $i<$dbConn->rows; $i++) {
				$otherRes[$dbConn->results["RESERVATION_ID_FK"][$i]] = $dbConn->results["RESDATE"][$i];
			}
			return $otherRes;
		}
		else return false;
	}



	function editRes ($resid,$edits,$guests=false,$sizeChange=0)
	{
		global $dbConn,$customer;
		if (!isset($dbConn)) $dbConn = new database();

		$qVars = array();
		$sexInsert_a = $sexInsert_b = $sexInsert_c = '';

		// error checks
		if (isset($edits['RES_DATE'])) {
			//$date = substr($edits['RES_DATE'],9,10);
			$date = $edits['RES_DATE'];
			$dateCheck = explode('/',$date);
			// validate date
			if (count($dateCheck)!=3 || strlen($date)!=10 || !checkdate($dateCheck[0],$dateCheck[1],$dateCheck[2]))
				$this->error = 'notDate';
			// before today
			elseif (strtotime($date)<strtotime("today"))
				$this->error = 'beforeToday';
			// today
			elseif ($date==date("m/d/Y"))
				$this->error = 'today';
			$sexInsert_a = "RES_DATE=TO_DATE(:q_RES_DATE,'MM/DD/YYYY'),";
			$qVars["q_RES_DATE"] = $date;
		}
		else {
			$date = $this->resinfo['RESDATE'][0];
		}

		if ($this->error)
			return false;

			// start time
		if (isset($edits['ENTER_TIME'])) {
			//$stime = substr($edits['ENTER_TIME'],18,8);
			$stime = $edits['ENTER_TIME'];
			if (substr($stime,-1)=="'") $stime = substr($stime,0,strlen($stime)-1);
			if (strlen($stime)<8) $stime = "0".$stime;
			if (strlen($stime)<8 || strpos($stime,':')!=2 || substr(strtolower($stime),-1)!='m') $this->error = 'notTime';
			$sexInsert_b = "ENTER_TIME=TO_DATE(:q_ENTER_TIME,'MM/DD/YY HH:MI AM'),";
			$qVars["q_ENTER_TIME"] = "01/01/05 $stime";
		}
			// end time
		if (isset($edits['EXIT_TIME'])) {
			//$etime = substr($edits['EXIT_TIME'],18,8);
			$etime = $edits['EXIT_TIME'];
			if (substr($etime,-1)=="'") $etime = substr($etime,0,strlen($etime)-1);
			if (strlen($etime)<8) $etime = "0".$etime;
			if (strlen($etime)<8 || strpos($etime,':')!=2 || substr(strtolower($etime),-1)!='m') $this->error = 'notTime';
			$sexInsert_c = "EXIT_TIME=TO_DATE(:q_EXIT_TIME,'MM/DD/YY HH:MI AM'),";
			$qVars["q_EXIT_TIME"] = "01/01/05 $etime";
		}

		if ($this->error) return false;

		if (isset($guests['GROUP_SIZE']) && $guests['GROUP_SIZE']>25 && $customer['auth']<4)
			$this->error = 'groupSize';
		elseif (isset($guests['totalSize']) && $guests['totalSize']>25 && $customer['auth']<4)
			$this->error = 'groupSize';

		if (isset($guests['add']) && count($guests['add'])) {
			if (!isset($spaces)) {
				if (count($this->groupCount) >1)
					$spaces = count($this->groupCount);
				else
					$spaces = $this->groupCount[0];
			}

			// make sure the department has not reached their max
			$this->checkResCount($customer, $this->deptno,$this->garageid,$date,$sizeChange);
			// make sure the garage is not maxed
			$this->checkGarageMax($this->garageid,$date,$spaces);

		} else {
			//jody 2008-02-05  Created this elese because was allowing 25+ garage spaces to be inserted.

			// make sure the department has not reached their max
			$this->checkResCount($customer,$this->deptno,$this->garageid,$date,$sizeChange);
			// make sure the garage is not maxed
			$this->checkGarageMax($this->garageid,$date,$spaces);
		}

		if ($this->error)
			return false;

		$query = "UPDATE PARKING.GR_RESERVATION SET $sexInsert_a $sexInsert_b $sexInsert_c";
		//$editAll = array();
		$tmpQuery = '';
		$skipFields = array("RES_DATE","ENTER_TIME","EXIT_TIME");
		foreach ($edits as $field=>$val) {
			if (in_array($field,$skipFields)) {
				//$editAll[] = "$field=$val";
			} elseif ($field) {
				//$editAll[] = "$field=".$dbConn->format($val,true,false);
				$tmpQuery .= "$field=:q_$field, ";
				$qVars["q_$field"] = $dbConn->sFormat($val,true,false);
			}
		}

		if ($tmpQuery) {
			$tmpQuery = preg_replace('/^(.*)\,\s*$/', '$1', $tmpQuery); // chop off the trailing ", "
		} else {
			$query = preg_replace('/^(.*)\,\s*$/', '$1', $query); // chop off the trailing ", "
		}

		$tmpQuery_r = implode(",",$resid);
		if (!preg_match("/^[\w\d\-\/ \.\,]*$/",$tmpQuery_r)) {
			echo '!!!!!!!!!! ERROR: BAD ResID List !!!!!!!!!!!!!';
			return false;
		}

		$query .= " $tmpQuery WHERE RESERVATION_ID IN ($tmpQuery_r)";
		//if (count($editAll)) {
		if ($tmpQuery_r && ($sexInsert_a || $sexInsert_b || $sexInsert_c || $tmpQuery)) {
			//$dbConn->query($query);
			$dbConn->sQuery($query, $qVars);
			$this->resNote($resid, $customer["userid"], "Edited ".implode(",",array_keys($edits)));
		}
		if ($guests) {
			foreach ($guests as $action=>$guest) {
				if ($action=="add") {
					$this->addGuest($resid, $guest, $guests["GROUP_SIZE"], 1, $sizeChange);
				} elseif ($action=="kill") {
					$this->killGuest($resid, $guest, $sizeChange);
				}
			}
			if (isset($guests['edit']) || isset($guests['sizeedit']))
				$this->editGroup($guests,$sizeChange);
		}
		return true;
	}



	function resNote ($resid, $userid, $note, $sizeChange=0, $cashierNot=0)
	{
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		if (!is_array($resid)) $resid = array($resid);
		foreach ($resid as $res) {

			// sleep for one second if this is the second+ note, because oracle has a constraint on records inserted at same time.
			if($this->numNotes++ > 0)
				sleep(1);

			$query = "INSERT INTO PARKING.GR_RESERVATION_NOTE (RESERVATION_ID_FK,USER_ID_FK,NOTE,DATE_RECORDED,SIZE_CHANGE,CASHIER_DISPLAY) VALUES(:res,:userid,:note,SYSDATE,:sizeChange,:cashierNot)";
			$qVars = array('res'=>$res, 'userid'=>$userid, 'note'=>$note, 'sizeChange'=>$sizeChange, 'cashierNot'=>$cashierNot);
			$dbConn->sQuery($query, $qVars);
		}
	}



	function getGuests ($resid) {
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();
		// frs validation check
		if (!preg_match("/^[\w\d\-\/ \.]*$/",$resid)) {
			$this->error = 'frsInvalid';
			return false;
		}
		// Safe query!
		$dbConn->query("SELECT * FROM PARKING.GR_GUEST WHERE RESERVATION_ID_FK='$resid'");
		$this->guestList = array();
		$this->groupCount = array();
		if ($dbConn->rows) {
			$this->guestList = $dbConn->results["GUEST_NAME"];
			$this->groupCount = $dbConn->results["GROUP_SIZE"];
		}
		else $this->error = "noGuests";
	}



	function addGuest ($resid,$guest,$size,$addon,$sizeChange=0,$new=false) {
		global $dbConn,$customer;
		if (!isset($dbConn)) $dbConn = new database();
		if (!is_array($guest)) $guest = array($guest);
		else $guest = array_values($guest);
		if (count($guest) && $guest[0]) {
			foreach ($guest as $g) {
				if ($g) {
					$g = ucwords(strtolower(trim($g)));
					$sortName = substr($g,strrpos($g," ")+1);
					if (!is_array($resid)) $resid = array($resid);
					foreach ($resid as $res) {
						$g = $dbConn->sFormat($g,true,false,150);
						$sortName = $dbConn->sFormat($sortName,true,false,150);
						// bs xsl ampersand fix
						$search = array(' & ','"');
						$replace = array(' &amp; ','&quot;');
						$g = str_replace($search,$replace,$g);
						if ($res) {
							$query = "INSERT INTO PARKING.GR_GUEST (GUEST_NAME,RESERVATION_ID_FK,GROUP_EXITED,GROUP_SIZE,ADDON,SORT_NAME) VALUES(:q_g,:q_res,0,:q_size,:q_addon,:q_sortName)";
							if (!$sortName)
								$sortName = ' ';
							$qVars = array('q_g'=>$g, 'q_res'=>$res, 'q_size'=>$size, 'q_addon'=>$addon, 'q_sortName'=>$sortName);
							$dbConn->sQuery($query, $qVars);
						}
					}
				}
			}
			if (!$new)
				$this->resNote($resid, $customer["userid"], "Added Guest(s): ".implode(" | ",$guest), $sizeChange, 1);
		}
	}



	function killGuest ($resid,$guest,$sizeChange=0) {
		global $dbConn,$customer;
		if (!isset($dbConn)) $dbConn = new database();

		if (!is_array($resid))
			$resid = array($resid);
		if (!is_array($guest))
			$guest = array($guest);
		else
			$guest = array_values($guest);

		if (count($guest) && $guest[0]) {

			// Make sure $resid contains only digits or spaces
			if (!preg_match('/^[\d ]*$/', implode('',$resid))) {
				echo '!!!!!!!!!! ERROR: Bad Reservation IDs !!!!!!!!!!!!';
				return '';
			}
			if (!is_array($guest)) {
				echo '!!!!!!!!!! ERROR: NO GUESTS IN SEARCH CRITERIA !!!!!!!!!!!!';
				return '';
			}

 			$sqlAppend = '';
 			$j = 0;
 			$qVars = array();
 			foreach ($guest as $ky => $aGuest) {
 				if ($sqlAppend)
 					$sqlAppend .= ' OR ';
 				$jj = "aGuest_$j";
 				$j++;
    			$sqlAppend = "LOWER(GUEST_NAME) = LOWER(:$jj)";
    			$qVars[$jj] = $aGuest;
 			}

			//$dbConn->query("DELETE FROM PARKING.GR_GUEST WHERE RESERVATION_ID_FK IN (".implode(",",$resid).") AND GUEST_NAME IN ('".implode("','",$guest)."')");
			$query = "DELETE FROM PARKING.GR_GUEST WHERE RESERVATION_ID_FK IN (".implode(",",$resid).") AND ($sqlAppend)";
			$dbConn->sQuery($query, $qVars);

			$this->resNote($resid, $customer["userid"], "Removed Guest(s): ".implode(" | ",$guest), $sizeChange, 1);
		}
	}



	function editGroup ($guests,$sizeChange) {
		global $dbConn,$customer;
		if (!isset($dbConn)) $dbConn = new database();
		$note = '';

		// make sure digits or spaces only
		if (!preg_match('/^[\d ]*$/', $this->conf)) {
			echo '!!!!!!!!!! ERROR: Bad Reservation ID in editGroup !!!!!!!!!!!!';
			return '';
		}
		if (isset($guests['sizeedit'])) {
			if (!preg_match('/^[\d ]*$/', $guests['sizeedit'])) {
				echo '!!!!!!!!!! ERROR: Group size must be a number !!!!!!!!!!!!';
				return '';
			}
		}

		if (isset($guests['edit'])) {
			//$dbConn->query("UPDATE PARKING.GR_GUEST SET GUEST_NAME='".$guests['edit']."' WHERE RESERVATION_ID_FK=$this->conf");
			$query = "UPDATE PARKING.GR_GUEST SET GUEST_NAME=:aguest WHERE RESERVATION_ID_FK=:resid";
 			$qVars = array('aguest' => $guests['edit'], 'resid' => $this->conf);
			$dbConn->sQuery($query, $qVars);

			$note = "Change group name";
		}

		if (isset($guests['sizeedit'])) {
			//$dbConn->query("UPDATE PARKING.GR_GUEST SET GROUP_SIZE='".$guests['sizeedit']."' WHERE RESERVATION_ID_FK=$this->conf");
			$query = "UPDATE PARKING.GR_GUEST SET GROUP_SIZE=:sizeedit WHERE RESERVATION_ID_FK=:resid";
 			$qVars = array('sizeedit' => $guests['sizeedit'], 'resid' => $this->conf);
			$dbConn->sQuery($query, $qVars);
		}

		//  2/5/2008 - Removed this because resNote was being calld somewhere else, and this caused an error because field DATE_RECORDED can't have same time / id.
		//if ($note) $this->resNote($this->conf,$customer['userid'],$note,$sizeChange,1);
	}



	function canCancel ($resdate) {
		if (is_array($resdate))
			$resdate = array($resdate);
		$dadate = date("m/d/Y");
		foreach ($resdate as $res) {
			if ($res==$dadate)
				return false;
		}
		return true;
	}



	function cancelRes ($resid) {
		global $dbConn,$customer;
		if (!isset($dbConn)) $dbConn = new database();

		if (!is_array($resid))
			$resid = array($resid);

		// Make sure $resid contains only digits or spaces
		if (!preg_match('/^[\d ]*$/', implode('',$resid))) {
			echo '!!!!!!!!!! ERROR: Bad Reservation IDs in cancelRes !!!!!!!!!!!!';
			return '';
		}
		// query is safe! see preg_match above.
		$dbConn->query("UPDATE PARKING.GR_RESERVATION SET ACTIVE=0 WHERE RESERVATION_ID IN (".implode(",",$resid).")");
		$this->resNote($resid, $customer["userid"], "Cancelled at " . date('Y-m-d H:i:s'));
	}



	function resurrect ($resid) {
		global $dbConn,$customer;
		if (!isset($dbConn)) $dbConn = new database();

		if (!is_array($resid))
			$resid = array($resid);

		// Make sure $resid contains only digits or spaces
		if (!preg_match('/^[\d ]*$/', implode('',$resid))) {
			echo '!!!!!!!!!! ERROR: Bad Reservation IDs in resurrect !!!!!!!!!!!!';
			return '';
		}
		// query is safe! see preg_match above.
		$dbConn->query("UPDATE PARKING.GR_RESERVATION SET ACTIVE=1 WHERE RESERVATION_ID IN (".implode(",",$resid).")");
		$this->resNote($resid, $customer["userid"], "Revived");
	}



	function checkResOwner ($currUser, $resUser) {
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		if ($currUser["userid"] == $resUser["userid"]) {
			$this->owner = true;
			return true;
		}
		else {
			$this->owner = false;
			return false;
		}
	}



	function checkMultiResOwner ($currUser,$resid) {
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		if (!is_array($resid))
			$resid = array($resid);

		// Make sure $resid contains only digits or spaces
		if (!preg_match('/^[\d ]*$/', implode('',$resid))) {
			echo '!!!!!!!!!! ERROR: Bad Reservation IDs in resurrect !!!!!!!!!!!!';
			return '';
		}

		$return = array('pass'=>array(),'fail'=>array());

		// query is safe! see preg_match above.
		$dbConn->query("SELECT RESERVATION_ID,USER_ID_FK FROM PARKING.GR_RESERVATION WHERE RESERVATION_ID IN (".implode(',',$resid).") ORDER BY RESERVATION_ID");

		for ($i=0; $i<$dbConn->rows; $i++) {
			if ($currUser == $dbConn->results['USER_ID_FK'][$i])
				$return['pass'][] = $dbConn->results['RESERVATION_ID'][$i];
			else
				$return['fail'][] = $dbConn->results['RESERVATION_ID'][$i];
		}

		return $return;
	}



	function checkResCount ($customer, $dept, $garageid, $resdate, $spacesProposed=0) {
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		//$dbConn->query("SELECT SUM(GROUP_SIZE) AS TOTALCOUNT FROM PARKING.GR_GUEST INNER JOIN PARKING.GR_RESERVATION ON RESERVATION_ID_FK=RESERVATION_ID WHERE DEPT_NO_FK='$dept' AND TRUNC(RES_DATE)=TO_DATE('$resdate','MM/DD/YY') AND GARAGE_ID_FK=$garageid AND ACTIVE=1");

		$query = "SELECT SUM(GROUP_SIZE) AS TOTALCOUNT FROM PARKING.GR_GUEST INNER JOIN PARKING.GR_RESERVATION ON RESERVATION_ID_FK=RESERVATION_ID WHERE DEPT_NO_FK=:dept AND TRUNC(RES_DATE)=TO_DATE(:resdate,'MM/DD/YY') AND GARAGE_ID_FK=:garageid AND ACTIVE=1";
		$qVars = array('dept' => $dept, 'resdate' => $resdate, 'garageid' => $garageid);

		$dbConn->sQuery($query, $qVars);

		if ($dbConn->rows && ($dbConn->results["TOTALCOUNT"][0]+$spacesProposed) > 25 && $customer['auth']<4) {
			$this->error = "resCount";
			$this->errordate = $resdate;
			return $dbConn->results["TOTALCOUNT"][0];

		}
	}



	function checkGarageMax ($garageid, $resdate, $spaces)
	{
		global $dbConn;
		if (!isset($dbConn)) $dbConn = new database();

		//$dbConn->query("SELECT SUM(GROUP_SIZE) AS TOTALCOUNT FROM PARKING.GR_GUEST INNER JOIN PARKING.GR_RESERVATION ON RESERVATION_ID_FK=RESERVATION_ID
		// WHERE GARAGE_ID_FK=$garageid AND TRUNC(RES_DATE)=TO_DATE('$resdate','MM/DD/YY') AND ACTIVE=1");
		$query = "SELECT SUM(GROUP_SIZE) AS TOTALCOUNT FROM PARKING.GR_GUEST INNER JOIN PARKING.GR_RESERVATION ON RESERVATION_ID_FK=RESERVATION_ID "
				 . "WHERE GARAGE_ID_FK=:garageid AND TRUNC(RES_DATE)=TO_DATE(:resdate,'MM/DD/YY') AND ACTIVE=1";
		$qVars = array('garageid' => $garageid, 'resdate' => $resdate);
		$dbConn->sQuery($query, $qVars);

		if ($dbConn->rows)
		{
			$count = $dbConn->results["TOTALCOUNT"][0];

			$query = "SELECT VISITOR_MAX FROM PARKING.GR_GARAGE WHERE GARAGE_ID=:garageid";
 			$qVars = array('garageid' => $garageid);
			$dbConn->sQuery($query, $qVars);

			if ($dbConn->rows && ($count+$spaces) > $dbConn->results["VISITOR_MAX"][0])
			{
				if (@$_POST['spacesOrig'] && @$_POST['spaces'] && ($_POST['spaces'] <= $_POST['spacesOrig'])) {
					; // Customer is editing his reservation, and the number of spaces is lower or equal to his origional.  So no need to check for max.
				} else {
					$this->error = "garageMax";
					$this->errordate = $resdate;
				}
			}
		}
	}



	function errorOut ($error,$resdate='') {
		$errors = array(
			'duplicateGuests' => "There were duplicate names found: $this->guestList",
			'db'=>"There has been a database error. Please try again. If the problem continues, please contact PTS Visitor Programs at $this->phone<br/>$this->errormsg",
			'noConf'=>"No confirmation number was returned when making the reservation. The reservation has most likely failed due to a system error. Please contact PTS Visitor Programs at $this->phone during normal business hours to complete your reservation.",
			'groupName'=>'Please enter a group name',
			'oneGuest'=>'Please enter at least one guest in the guest list',
			'beforeToday'=>'The date you entered is before today. Reservations can only be made on or after the next business day after today.',
			'today'=>"Reservations for the current date cannot be made online. Please call PTS Visitor Programs at $this->phone if you would like a reservation for today.",
			'groupSize'=>"Reservations are limited to 25 total spaces. To make a larger reservation, please contact PTS Visitor Programs at $this->phone.",
			"resCount"=>"Your department has already reserved spaces in this garage for this date $resdate, but is limited to 25 total spaces. If you would like to reserve more, please contact PTS Visitor Programs at $this->phone.",
			"garageMax"=>"<br/>The garage you selected has already reached its capacity for $resdate.<br/> Please select another garage for this date, or <br/>for further garage availability information on $resdate,<br/> please contact PTS Visitor Programs at <u/>$this->phone</u/>.",
			"weekend"=>"The date you selected falls on a day when the garages are open (campus holiday or weekend day). Reservations are not necessary on these dates.",
			'notDate'=>'The date you selected is not a valid date. Please format all dates as MM/DD/YYYY (e.g. - 01/01/2005). Please check the information and try again.',
			'notTime'=>'The time you entered for this reservation is invalid. Please format all times HH:MI AM (e.g. - 08:00 AM). Please check the information and try again.',
			'noDates'=>'No dates were selected in the reservation. Please check the information and try again.',
			'addGuests'=>'Please enter a number for Additional Guests in the space provided',
			'noGuests'=>'No guests were entered. Please try again.',
			'frsInvalid'=>'The KFS Number you supplied is invalid. Please try again.',
			'subObjInvalid'=>'The KFS Sub Acct., or Sub Obj. Code that you supplied is invalid. Please try again.'
		);
		$return = '<div align="center"><div style="background-color:#FFD147; padding:3px; border:1px solid red; text-align:center; width:70%; text-align:left;" class="warning">An Error Has Occurred';
		if ($resdate)
			$return .= " for the reservation requested on $resdate";
		$return .= ": {$errors[$error]}</div></div>\n";
		//if ($error=='db') mail("PTS-IT-Emails@email.arizona.edu","GR DB Error",$this->errormsg,"From: PTS-IT-Emails@email.arizona.edu");
		return $return;
	}

	// $result=mail($recipient, $subject, $msg3, "From:\"PTS Visitor Programs\" <PTS-ParkingReservations@email.arizona.edu>\r\nBcc:<PTS-IT-Emails@email.arizona.edu>\r\n");
	function send_email($recipient, $subject, $message, $from = '', $bcc = '') {
		$mail = new PHPMailer(true);

		// Load env if not already loaded
		$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__.'/../../..');
		$dotenv->load();
		try {
			// Server settings
			$mail->isSMTP();
			$mail->Host = $_ENV['MAIL_HOST']; // Set the SMTP server to send through
			$mail->SMTPAuth = true;
			$mail->Username = $_ENV['MAIL_USERNAME']; // SMTP username
			$mail->Password = $_ENV['MAIL_PASSWORD']; // SMTP password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;
			// Recipients
			if ($from != '') {
				$mail->setFrom($from); // Set custom from address if provided
			} else {
				// Default from address
				$mail->setFrom('baas-aws-ses@arizona.edu', 'PTS Visitor Programs');
			}
			$mail->addAddress($recipient); // Add recipient email
			if($bcc != '') {
				$mail->addBCC($bcc); // Add BCC if provided
			}

			// Content
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $message;

			$mail->send();
			$wasEmailed = true;
		} catch (Exception $e) {
			$wasEmailed = false;
			error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
			echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}

		return $wasEmailed;
	}
}
?>