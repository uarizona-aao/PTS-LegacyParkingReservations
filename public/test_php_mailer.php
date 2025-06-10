<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Include Composer's autoloader

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();                                            // Use SMTP
    $mail->Host       = 'email-smtp.us-west-2.amazonaws.com';   // Amazon SES SMTP endpoint
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'AKIASQ6T4ZHV4JIGW4IY';                   // Your Amazon SES SMTP username
    $mail->Password   = 'BESvEoV8r34MO97UQUO3hcWr/5iF5nl+QLlPrekr17mb';                   // Your Amazon SES SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
    $mail->Port       = 587;                                    // TCP port for TLS (use 465 for SSL)

    // Recipients
    $mail->setFrom('baas-aws-ses@arizona.edu', 'Parking Test');      // Sender's email and name
    $mail->addAddress('daltont@arizona.edu', 'Recipient Name'); // Add a recipient

    // Content
    $mail->isHTML(true);                                        // Set email format to HTML
    $mail->Subject = 'Test Email from Amazon SES';
    $mail->Body    = '<p>This is a test email sent using Amazon SES and PHPMailer.</p>';
    $mail->AltBody = 'This is a test email sent using Amazon SES and PHPMailer.';

    $mail->send();
    echo 'Message has been sent successfully';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}