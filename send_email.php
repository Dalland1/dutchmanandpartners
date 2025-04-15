<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'path/to/PHPMailer/src/Exception.php';
require 'path/to/PHPMailer/src/PHPMailer.php';
require 'path/to/PHPMailer/src/SMTP.php';

// Process form data
$name = strip_tags(trim($_POST['name']));
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$subject = strip_tags(trim($_POST['subject']));
$message = trim($_POST['message']);

// Validate inputs
if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please fill in all required fields correctly.']);
    exit;
}

$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'mail.yourdomain.com'; // Your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreply@yourdomain.com'; // SMTP username
    $mail->Password   = 'yourpassword'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('noreply@yourdomain.com', 'Website Contact Form');
    $mail->addAddress('legal@dutchmanandpartners.com');
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(false);
    $mail->Subject = "New Contact Form Submission: $subject";
    $mail->Body    = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

    $mail->send();
    http_response_code(200);
    echo json_encode(['message' => 'Thank you! Your message has been sent.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}
?>
