<?php
/*
 * email.php
 * Convenient access to PHP's mail function
 * Tailored for PTS use
 */

class email {
    private $to, $subject, $message, $from;

    function __construct($to = null, $subject = null, $message = null, $from = 'UA Parking <PTS-ParkingInformation@email.arizona.edu>') {
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->from = $from;
    }

    function set_recipient($to) { $this->to = $to; }
    function set_recipient_name($name) { $this->to = "$name <$this->to>"; }
    function set_bcc($bcc) { $this->bcc = $bcc; }
    function set_subject($subject) { $this->subject = $subject; }
    function set_message($message) { $this->message = $message; }
    function set_sender($from) { $this->from = $from; }

    function add_pts_signature($division = null, $phone = '(520) 626-PARK (7275)') {
        $this->message .= "\n\n";
        if($division) $this->message .= "$division\n";
        $this->message .= "UA Parking & Transportation Services\n1117 E. Sixth Street\nTucson, AZ 85721-0181\n$phone";
    }

    function send() {
        if(!$this->to or !$this->subject or !$this->message) throw new Exception('Incomplete mail message.');
        $header = "from: $this->from\r\nReply-To: $this->from\r\n";
	if ($this->bcc) $header .= "Bcc: $this->bcc\r\n";
	$header .= "X-Mailer: PHP/5\r\n";
        mail($this->to, $this->subject, $this->message, $header);
    }
}
?>
