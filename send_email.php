<?php
header('Content-Type: application/json');

// Validate reCAPTCHA first
$recaptcha_secret = 'YOUR_RECAPTCHA_SECRET'; // Replace with your reCAPTCHA secret key
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptcha_response)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please complete the reCAPTCHA verification.']);
    exit;
}

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = [
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response
];

$recaptcha_options = [
    'http' => [
        'method' => 'POST',
        'content' => http_build_query($recaptcha_data)
    ]
];

$recaptcha_context = stream_context_create($recaptcha_options);
$recaptcha_result = json_decode(file_get_contents($recaptcha_url, false, $recaptcha_context));

if (!$recaptcha_result || !$recaptcha_result->success) {
    http_response_code(400);
    echo json_encode(['message' => 'reCAPTCHA verification failed.']);
    exit;
}

// Process form data
$name = strip_tags(trim($_POST['name'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject = strip_tags(trim($_POST['subject'] ?? ''));
$message = trim($_POST['message'] ?? '');

// Validate inputs
if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please fill in all required fields correctly.']);
    exit;
}

// Email configuration
$to = 'legal@dutchmanandpartners.com'; // Replace with your recipient email
$email_subject = "New Contact Form Submission: $subject";
$email_body = "You have received a new message from your website contact form.\n\n".
              "Name: $name\n".
              "Email: $email\n".
              "Subject: $subject\n\n".
              "Message:\n$message\n";

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = 'mail.dutchmanandpartners.com'; // Replace with your SMTP host
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@dutchmanandpartners.com'; // Replace with your SMTP username
    $mail->Password = 'YOUR_SMTP_PASSWORD'; // Replace with your SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Email headers
    $mail->setFrom('noreply@dutchmanandpartners.com', 'Website Contact Form');
    $mail->addAddress($to);
    $mail->addReplyTo($email, $name);
    $mail->Subject = $email_subject;
    $mail->Body = $email_body;

    // Send email
    $mail->send();
    http_response_code(200);
    echo json_encode(['message' => 'Thank you! Your message has been sent.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Oops! Something went wrong. Please try again later.']);
}
?>
