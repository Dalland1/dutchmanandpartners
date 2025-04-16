<?php
header('Content-Type: application/json');

// Validate reCAPTCHA first
$recaptcha_secret = '6Ldw8BorAAAAAPqHhxVc-sQI6Q089Mo6N9qQGyYk'; // Replace with your actual secret key
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptcha_response)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please complete the reCAPTCHA verification.']);
    exit;
}

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = [
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$recaptcha_options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($recaptcha_data)
    ]
];

$recaptcha_context = stream_context_create($recaptcha_options);
$recaptcha_result = json_decode(file_get_contents($recaptcha_url, false, $recaptcha_context));

if (!$recaptcha_result || !$recaptcha_result->success) {
    http_response_code(400);
    echo json_encode(['message' => 'reCAPTCHA verification failed. Please try again.']);
    exit;
}

// Process form data with sanitization
$name = strip_tags(trim($_POST['name'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject = strip_tags(trim($_POST['subject'] ?? 'Contact Form Submission'));
$message = strip_tags(trim($_POST['message'] ?? ''), "<p><br><a>"); // Allow basic HTML

// Validate inputs
if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please fill in all required fields correctly.']);
    exit;
}

// Email configuration
$to = 'your-email@example.com'; // Replace with your recipient email
$email_subject = "New Contact Form Submission: $subject";
$email_body = "
<html>
<head>
    <title>New Contact Form Submission</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .header { color: #333366; font-size: 18px; }
        .details { margin: 20px 0; }
        .message { white-space: pre-wrap; background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2 class=\"header\">New Contact Form Submission</h2>
    <div class=\"details\">
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> <a href=\"mailto:$email\">$email</a></p>
        <p><strong>Subject:</strong> $subject</p>
    </div>
    <div class=\"message\">
        <h3>Message:</h3>
        <p>$message</p>
    </div>
</body>
</html>
";

// Include PHPMailer (make sure you've installed it via Composer or manually)
require 'vendor/autoload.php'; // Path to autoload.php if using Composer
// OR if manually including:
// require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/SMTP.php';
// require 'path/to/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'mail.yourdomain.com'; // Your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@yourdomain.com'; // SMTP username
    $mail->Password = 'your-smtp-password'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
    $mail->Port = 587; // TCP port to connect to
    
    // Recipients
    $mail->setFrom('noreply@yourdomain.com', 'Website Contact Form');
    $mail->addAddress($to); // Primary recipient
    // $mail->addCC('cc@example.com'); // Uncomment to add CC
    // $mail->addBCC('bcc@example.com'); // Uncomment to add BCC
    $mail->addReplyTo($email, $name); // Set reply-to address
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = $email_subject;
    $mail->Body = $email_body;
    $mail->AltBody = strip_tags($email_body); // Plain text version
    
    $mail->send();
    http_response_code(200);
    echo json_encode(['message' => 'Thank you! Your message has been sent successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Mailer Error: ' . $mail->ErrorInfo); // Log the error
    echo json_encode(['message' => 'Sorry, something went wrong. Please try again later.']);
}
?>
