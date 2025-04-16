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
$to = 'legal@dutchmanandpartners.com'; // Your recipient email
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

// Set headers for HTML email
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: no-reply@dutchmanandpartners.com\r\n";
$headers .= "Reply-To: $email\r\n";

// Send email
if (mail($to, $email_subject, $email_body, $headers)) {
    http_response_code(200);
    echo json_encode(['message' => 'Thank you! Your message has been sent successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Sorry, something went wrong. Please try again later.']);
}
?>
