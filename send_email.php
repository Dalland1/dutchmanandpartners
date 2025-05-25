<?php
// Allow requests from your specific origin
header("Access-Control-Allow-Origin: https://dutchmanandpartners.com"); 
// If you have a www version or other subdomains, you might need to handle that dynamically or add multiple
// header("Vary: Origin"); // Useful if you have multiple allowed origins

// Allow specific methods
header("Access-Control-Allow-Methods: POST, OPTIONS");
// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
// Ensure JSON response

// Handle OPTIONS request (pre-flight request)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// IMPORTANT: Replace with your actual reCAPTCHA SECRET KEY
define('RECAPTCHA_SECRET_KEY', '6LfqwUgrAAAAAEcAUn8n-C9hLGrbXav5yIS8bXh1');
// Ensure this script is accessed via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
// Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Error: This script only accepts POST requests.']);
exit;
}

// Get JSON input (since your frontend is sending FormData, we'll use $_POST)
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message_body = isset($_POST['message']) ?
trim($_POST['message']) : '';
$recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
// Basic validation
if (empty($name) || empty($email) || empty($message_body)) {
    http_response_code(400);
// Bad Request
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
exit;
}

// Verify reCAPTCHA
if (empty($recaptcha_response)) {
    http_response_code(400);
// Bad Request
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again. (No token)']);
exit;
}

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = [
    'secret'   => RECAPTCHA_SECRET_KEY,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR'] // Optional
];
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($recaptcha_data)
    ]
];
$context = stream_context_create($options);
$verify_response = file_get_contents($recaptcha_url, false, $context);
$response_data = json_decode($verify_response);
if (!$response_data || !$response_data->success) {
    $error_codes = isset($response_data->{'error-codes'}) ? implode(', ', $response_data->{'error-codes'}) : 'N/A';
    http_response_code(400);
// Bad Request
    echo json_encode([
        'success' => false, 
        'message' => 'reCAPTCHA verification failed. Please try again. (Google check failed: ' . $error_codes . ')'
    ]);
exit;
}

// If reCAPTCHA is successful, proceed to send email
$to = 'legal@dutchmanandpartners.com';
// Replace with your email address
$email_subject = "New Contact Form Submission: " . ($subject ?: 'No Subject');
$email_headers = "From: " . $name . " <" . $email . ">\r\n";
$email_headers .= "Reply-To: " . $email .
"\r\n";
$email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$email_body = "You have received a new message from your website contact form.\n\n";
$email_body .= "Name: " . $name . "\n";
$email_body .= "Email: " . $email . "\n";
$email_body .= "Subject: " . ($subject ?: 'Not Provided') . "\n";
$email_body .= "Message:\n" . $message_body . "\n";
// Example using PHP's mail() function.
// For more robust email sending, consider using a library like PHPMailer.
if (mail($to, $email_subject, $email_body, $email_headers)) {
    http_response_code(200);
// OK
    echo json_encode(['success' => true, 'message' => 'Message sent successfully! We will get back to you soon.']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again later. (Mail function failed)']);
}

exit;
?>
