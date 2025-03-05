<h1>Invoices</h1>
<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once $docRoot.'/parking/garage-reservation/garage-reservation-include.php';
?>
<div id="main">
 <br />
 <div class="container" >
	<div class="row">
	 <div class="col-sm-4 col-md-4 col-lg-4 hidden-xs">
	 <?php
	 include_once $docRoot.'/parking/parking-menu-include.php';
	 ?>
	 </div>
	 <!-- end side nav menu -->
	 <div id="mainContent" class="col-sm-8 col-md-8 col-lg-8"  >
	 <ol class="breadcrumb">
		<li><a href="/">Home</a></li>
		<li><a href="/parking/">Parking & Permits</a></li>
		<li class="active">Garage Reservation</li>
	 </ol>
	 <h1  class="page-heading">Department Visitor Garage Reservation</h1>
	 <hr />
	 <div id="editableContent">


<?php
spinnerWaiting();

if ($auth < 3)
	exitWithBottom('You are not authorized.');
?>

<dynamic content="invoice"/>

<?php
function get_invoice() {
    make_invoices();

    // Read PDF directory for existing files
    $base = '/var/www2/html/External/parking/garage-reservation/pdf';
    if(!is_dir($base)) throw new Exception("Cannot read invoice directory.");
    $dh = opendir($base);
    $files = array();
    while(false !== ($file = readdir($dh))) {
        if($file == '.' or $file == '..' or $file == 'custom.pdf') continue;
        list($month, $year) = explode('-', $file);
	$month = intval($month);
	$year = intval($year);
        $stamp = mktime(0,0,0,$month,1,$year);
        $files[$stamp] = $file;
    }

    // Check database for other files
    $db = get_db();
    $db->query("select distinct to_char(RES_DATE, 'MM-YY') MONTH from PARKING.GR_RESERVATION where PAYMENT_ID_FK is null and ACTIVE = 1");
    $months = $db->get_results();
    foreach($months as $month) {
        list($month, $year) = explode('-', $month['MONTH']);
        $stamp = mktime(0,0,0,$month,1,$year);
        if(!isset($files[$stamp])) $files[$stamp] = null;
    }

    ksort($files);

    $results = '<p>';
    $last_year = 0;
    foreach($files as $stamp => $file) {
        $year = date('Y', $stamp);
        if($year != $last_year) {
            $results .= "</p><h2>$year</h2><p>";
            $last_year = $year;
        }
        $name = date('F', $stamp);
        $mmyy = date('m-y', $stamp);

        $results .= "<br/>";

        if(!$file) $results .= "<i>$name</i> [<a href='?gen=$mmyy'>Generate</a>]";
        else $results .= "<img src='/images/icons/pdf.gif' width='16' height='16' border='0' align='absmiddle' alt='Viewing this link requires Adobe Acrobat Viewer.'/> <a target='_blank'   href='../pdf/$file'>$name</a> [<a href='?gen=$mmyy'>Regenerate</a>]";
    }
    $results .= '</p>';

    // Custom PDF section
    $form = new form('Custom Invoice');
    $frsfield = field_factory::get_frs_field();
    $frsfield->get_renderer()->set_trailing_text(' (Leave blank for all accounts)');
    $frsfield->get_validator()->set_required(false);
    $form->add($frsfield);

    $start = field_factory::get_short_date_field('Start', null, 'last month', false);
    $start->get_renderer()->set_nolabel();
    $start->get_renderer()->set_leading_text('Invoice between ');
    $start->get_renderer()->set_trailing_text(' and ');
    $end = field_factory::get_short_date_field('End', null, 'today', false);
    $end->get_renderer()->set_nolabel();
    $form->add(field_factory::get_item_row(new collection($start, $end)));

    if(isset($GLOBALS['pdf_made'])) {
        $link = $GLOBALS['pdf_made'] ? "<img src='/images/icons/pdf.gif' width='16' height='16' border='0' align='absmiddle' alt='Viewing this link requires Adobe Acrobat Viewer.'/> <a target='_blank'   href='/parking/garage-reservation/pdf/custom.pdf'>Custom Invoice File</a>" : '<i>No Invoices</i>';
        $form->add(field_factory::get_note($link));
    }

    $form->add(field_factory::get_button('Generate'));
    $results .= $form->get_xml();

    $results .= '<p>[<a href="index.php">Go Back</a>]</p>';

    return $results;
}

function get_months() {

}

function make_invoices() {
    require_once 'pdf/class.ezpdf.php';
    $db = get_db();

    // Generate a selected PDF
    if(isset($_GET['gen'])) {
        $month = $_GET['gen'];
        $filename = "/var/www2/html/External/parking/garage-reservation/pdf/$month.pdf";

        $month = str_replace('-','/',$month);

        $pdf = new Cezpdf();
        $db->query("select distinct FRS_FK from PARKING.GR_RESERVATION where PAYMENT_ID_FK is null and to_char(RES_DATE, 'MM/YY') = '$month' and ACTIVE = 1 ORDER BY FRS_FK");
        foreach($db->get_results() as $result)
            generate($pdf, $result['FRS_FK'], $month);

        if(file_exists($filename)) unlink($filename);
        file_put_contents($filename, $pdf->output());
    }

    // Make a custom PDF
    if(isset($_POST['submit_button']) and $_POST['submit_button'] == 'Generate') {
        $pdf = new Cezpdf();

        $start_date = $_POST['Start'];
        $end_date = $_POST['End'];
        $set_frs = $_POST['KFS_Number'] ? ' and FRS_FK = \''.$_POST['KFS_Number']."'" : '';
        $db->query("select distinct FRS_FK from PARKING.GR_RESERVATION where PAYMENT_ID_FK is null and RES_DATE between to_date('$start_date', 'mm/dd/yy') and to_date('$end_date', 'mm/dd/yy') and ACTIVE = 1 $set_frs ORDER BY FRS_FK");
        if($db->num_rows()) {
            unset($GLOBALS['doc_count']);
            foreach($db->get_results() as $result)
                generate($pdf, $result['FRS_FK'], $start_date, $end_date);

            file_put_contents("/var/www2/html/External/parking/garage-reservation/pdf/custom.pdf", $pdf->ezOutput());
            $GLOBALS['pdf_made'] = true;
        }
        else $GLOBALS['pdf_made'] = false;
    }
}

function generate(Cezpdf $pdf, $frs, $month, $end_date = null) {
    ////////////////////
    // Header Section //
    ////////////////////

    global $doc_count;
    if(!isset($doc_count)) $doc_count = 1;
    else {
        $doc_count++;
        $pdf->ezNewPage();
    }

    $pdf->selectFont('/var/www2/include/pdf/fonts/Times-Bold.afm');
    if(sizeof(explode('/', $month)) == 3 and $end_date) list($mm, $dd, $yy) = explode('/', $end_date);
    else list($mm, $yy) = explode('/', $month);
    $monthname = date('F t, Y', mktime(0,0,0,$mm,1,$yy));
    $pdf->ezText("INVOICE - $monthname", 18);

    $pdf->selectFont('/var/www2/include/pdf/fonts/Times.afm');
    $pdf->ezText("Business Office\nParking and Transportation Services\n1117 E. Sixth Street\nPO Box 210181\nTucson, AZ 85721-0181\nPH: (520) 621-6912\n" ,10);

    $pdf->addPngFromFile('/var/www2/html/External/parking/garage-reservation/administrator/pts_logo.png', 480, 730, 80);

    $pdf->selectFont('/var/www2/include/pdf/fonts/Times-BoldItalic.afm');
    $pdf->saveState();
    $pdf->setColor(0.9,0.9,0.9);
    $pdf->filledRectangle($pdf->ez['leftMargin'],$pdf->y-$pdf->getFontHeight(10)+$pdf->getFontDecender(10),$pdf->ez['pageWidth']-$pdf->ez['leftMargin']-$pdf->ez['rightMargin'],$pdf->getFontHeight(10));
    $pdf->restoreState();
    $pdf->ezText("This invoice will post to FRS no later than 5 days after the invoice date.", 10, array('justification' => 'center'));

    $pdf->selectFont('/var/www2/include/pdf/fonts/Times-Bold.afm');
    $pdf->ezText("\nCharges Incurred for Parking Garage Reservations\n");

    $db = get_db();
    $db->query("select * from PARKING.GR_DEPARTMENT, PARKING.GR_FRS where FRS = '$frs' and DEPT_NO = DEPT_NO_FK");
    $customer = $db->get_from_top('DEPT_NAME');
    $dept_no = $db->get_from_top('DEPT_NO');
    $po_box = $db->get_from_top('PO_BOX');
    $frs_info = trim($db->get_from_top('DESCRIPTION'));

    // PO Boxes: 210xxx = building, 245xxx = AHSC
    if($db->query("select * from PARKING.GR_ADDRESS where DEPT_NO_FK = '$dept_no'")) {
        $zip = $db->get_from_top('ZIP');
        if(substr($zip, -4)) $zip = substr($zip, 0, 5);
        $address = $db->get_from_top('STREET') ."\n". $db->get_from_top('CITY') .", ". $db->get_from_top('STATE')." $zip";
    }
    else {
        $location = (substr($po_box, 0, 3) == '245') ? 'AHSC' : 'CAMPUS';
        $address = "PO Box $po_box\n$location";
    }

    $pdf->selectFont('/var/www2/include/pdf/fonts/Times-Roman.afm');
    $pdf->ezText("ATTN: BUSINESS OFFICE\n\n$customer\n$address\nAccount # $frs ($frs_info)");

    //////////////////
    // Body Section //
    //////////////////

    if($end_date) $q_range = "RES_DATE between to_date('$month', 'mm/dd/yy') and to_date('$end_date', 'mm/dd/yy')";
    else $q_range = "to_char(RES_DATE, 'MM/YY') = '$month'";
    $db->query("select distinct USER_ID, USER_NAME from PARKING.GR_RESERVATION, PARKING.GR_USER where USER_ID_FK = USER_ID and FRS_FK = '$frs' and PAYMENT_ID_FK is null and ACTIVE = 1 and $q_range");
    foreach($db->get_results() as $result)
        pdf_section($pdf, $result['USER_ID'], $result['USER_NAME'], $q_range, $frs);

    $pdf->selectFont('/var/www2/include/pdf/fonts/Times-Bold.afm');
    $pdf->ezText("\n\nTotal Bill: ".sprintf('$%1.2f', $GLOBALS['total_cost']), 12, array('justification' => 'right'));
    $GLOBALS['total_cost'] = 0;

    /*
    $pdf->ezSetY(48);
    $pdf->selectFont('/var/www2/include/pdf/fonts/Times-Bold.afm');
    $pdf->ezText("All delinquent accounts will automatically be deducted from FRS after 60 days.", 12, array('justification' => 'center'));

    $pdf->saveState();
    $pdf->setLineStyle(1);
    $pdf->line($pdf->ez['leftMargin'],54, $pdf->ez['pageWidth']-$pdf->ez['leftMargin']-$pdf->ez['rightMargin'], 54);
    $pdf->restoreState();
    */
}

function pdf_section(Cezpdf $pdf, $user_id, $user_name, $q_range, $frs) {
    $pdf->selectFont('/var/www2/include/pdf/fonts/Times-Bold.afm');
    $pdf->ezText("\n<u>Authorizing Person: $user_name</u>", 12);

    $db = get_db();
    $db->query("select RESERVATION_ID, RES_DATE, GARAGE_NAME, (select sum(GROUP_SIZE) from PARKING.GR_GUEST where RESERVATION_ID_FK = RESERVATION_ID) SPACES, PRICE from PARKING.GR_RESERVATION, PARKING.GR_GARAGE where GARAGE_ID_FK = GARAGE_ID and USER_ID_FK = $user_id and FRS_FK = '$frs' and PAYMENT_ID_FK is null and ACTIVE = 1 and $q_range order by RES_DATE");
    foreach($db->get_results() as $result)
        pdf_row($pdf, $result);
}

function pdf_row(Cezpdf $pdf, $record) {
    $date = date('m/d/y', strtotime($record['RES_DATE']));
    $garage = str_pad($record['GARAGE_NAME'], 21);
    $spaces = str_pad($record['SPACES'], 3);
    $cost = str_pad(sprintf('$%1.2f', $record['PRICE']), 6);
    $total = str_pad(sprintf('$%1.2f', $record['PRICE'] * $spaces), 7);

    if(!isset($GLOBALS['total_cost'])) $GLOBALS['total_cost'] = 0;
    $GLOBALS['total_cost'] += $record['PRICE'] * $spaces;

    $id = $record['RESERVATION_ID'];
    $db = get_db();
    $db->query("select * from PARKING.GR_GUEST where RESERVATION_ID_FK = $id order by upper(SORT_NAME)");
    if($db->num_rows() == 1 and $spaces > 1) {
        $guestword = 'Group';
        $names = $db->get_from_top('GUEST_NAME');
    }
    else {
        $guestword = $spaces == 1 ? 'Guest' : 'Guests';
        $names = array();
        foreach($db->get_results() as $guest)
            $names[] = $guest['GUEST_NAME'];
        $names = implode(', ', $names);
    }
    $guests = "<b>$guestword:</b> $names";

    $pdf->selectFont('/var/www2/include/pdf/fonts/Courier.afm');
    $pdf->ezText("\n<b>Date</b> $date  <b>Garage</b> $garage  <b>Spaces</b> $spaces  <b>Cost</b> $cost  <b>Total Cost</b> $total", 10);
    $pdf->ezText("$guests\n", 10);
}
?>
