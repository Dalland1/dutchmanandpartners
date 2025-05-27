<?php
header('Content-Type: application/json');

// CONFIG
$to = "info@dutchmanandpartners.com";
$recaptcha_secret = "6LfqwUgrAAAAAEcAUn8n-C9hLGrbXav5yIS8bXh1"; // replace this with your reCAPTCHA v2 secret

// Validate POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

// Validate required fields
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'No Subject');
$message = trim($_POST['message'] ?? '');
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

if (!$name || !$email || !$message || !$recaptcha_response) {
    http_response_code(400);
    echo json_encode(['message' => 'All fields are required including reCAPTCHA.']);
    exit;
}

// Verify reCAPTCHA
$recaptcha = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($recaptcha_secret) . "&response=" . urlencode($recaptcha_response));
$recaptcha_data = json_decode($recaptcha);

if (!$recaptcha_data->success) {
    http_response_code(400);
    echo json_encode(['message' => 'reCAPTCHA verification failed.']);
    exit;
}

// Build email
$headers = "From: $name <$email>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$body = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

// Send
$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode(['message' => 'Your message has been sent successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to send email. Try again later.']);
}
?>
