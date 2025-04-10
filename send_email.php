<?php
header('Content-Type: application/json');

// Validate reCAPTCHA first
$recaptcha_secret = 'YOUR_RECAPTCHA_SECRET';
$recaptcha_response = $_POST['g-recaptcha-response'];

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

if (!$recaptcha_result->success) {
    http_response_code(400);
    echo json_encode(['message' => 'Please complete the reCAPTCHA verification.']);
    exit;
}

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

// Email configuration
$to = 'legal@dutchmanandpartners.com';
$email_subject = "New Contact Form Submission: $subject";
$email_body = "You have received a new message from your website contact form.\n\n".
              "Name: $name\n".
              "Email: $email\n".
              "Subject: $subject\n\n".
              "Message:\n$message\n";

$headers = "From: noreply@dutchmanandpartners.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/".phpversion();

// Send email using SMTP (more reliable than mail())
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->isSMTP();
$mail->Host = 'mail.dutchmanandpartners.com';
$mail->SMTPAuth = true;
$mail->Username = 'noreply@dutchmanandpartners.com';
$mail->Password = 'YOUR_SMTP_PASSWORD';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('noreply@dutchmanandpartners.com', 'Website Contact Form');
$mail->addAddress('legal@dutchmanandpartners.com');
$mail->addReplyTo($email, $name);
$mail->Subject = $email_subject;
$mail->Body = $email_body;

if ($mail->send()) {
    http_response_code(200);
    echo json_encode(['message' => 'Thank you! Your message has been sent.']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Oops! Something went wrong. Please try again later.']);
}
?>