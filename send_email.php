<?php
// Set CORS headers for security and browser compatibility.
// In production, replace '*' with your actual domain (e.g., 'https://dutchmanandpartners.com').
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With"); // Added X-Requested-With for older JS

// Handle OPTIONS request (CORS pre-flight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// IMPORTANT: Replace with your actual reCAPTCHA SECRET KEY from Google reCAPTCHA Admin Console
// This key should NEVER be exposed client-side.
define('RECAPTCHA_SECRET_KEY', 'YOUR_RECAPTCHA_SECRET_KEY'); // Example: 6LeIxAcTAAAAABJygY_6_06jM-723_v-c48Wf-8S

// Define the recipient email address for the form submissions
$recipient_email = 'legal@dutchmanandpartners.com'; // IMPORTANT: Replace with your actual email

// Set content type for JSON response
header('Content-Type: application/json');

// Ensure this script is accessed via POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Error: This script only accepts POST requests.']);
    exit;
}

// Get and sanitize form data
// Using htmlspecialchars to prevent XSS in email content and logs
$name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8') : '';
$email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8') : '';
$subject = isset($_POST['subject']) ? htmlspecialchars(trim($_POST['subject']), ENT_QUOTES, 'UTF-8') : '';
$message_body = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8') : '';
$recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';

// Basic server-side validation
if (empty($name) || empty($email) || empty($message_body)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

// reCAPTCHA verification
if (empty($recaptcha_response)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again. (No token received)']);
    exit;
}

$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
$recaptcha_data = [
    'secret'   => RECAPTCHA_SECRET_KEY,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR'] // Optional, helps Google identify suspicious activity
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($recaptcha_data)
    ]
];

$context = stream_context_create($options);
$verify_response = @file_get_contents($recaptcha_url, false, $context); // Using @ to suppress warnings
$response_data = json_decode($verify_response, true); // Decode as associative array

if (!$response_data || !isset($response_data['success']) || $response_data['success'] !== true) {
    $error_codes = isset($response_data['error-codes']) ? implode(', ', $response_data['error-codes']) : 'N/A';
    error_log("reCAPTCHA verification failed for IP: " . $_SERVER['REMOTE_ADDR'] . " with errors: " . $error_codes); // Log error
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'reCAPTCHA verification failed. Please ensure you are not a robot. (Google check failed: ' . $error_codes . ')'
    ]);
    exit;
}

// If reCAPTCHA is successful, proceed to send email
$email_subject_prefix = "New Contact Form Submission";
$final_email_subject = $email_subject_prefix . ($subject ? ": " . $subject : "");

// Build email headers
$email_headers = "From: " . $name . " <" . $email . ">\r\n";
$email_headers .= "Reply-To: " . $email . "\r\n";
$email_headers .= "MIME-Version: 1.0\r\n"; // Added for better compatibility
$email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n"; // Explicitly set UTF-8

// Build email body
$email_body_content = "You have received a new message from your website contact form.\n\n";
$email_body_content .= "Name: " . $name . "\n";
$email_body_content .= "Email: " . $email . "\n";
$email_body_content .= "Subject: " . ($subject ?: 'Not Provided') . "\n";
$email_body_content .= "Message:\n" . $message_body . "\n";
$email_body_content .= "\n---\n";
$email_body_content .= "Sent from " . $_SERVER['HTTP_HOST'] . " on " . date('Y-m-d H:i:s') . "\n";


// Send the email using PHP's mail() function
if (mail($recipient_email, $final_email_subject, $email_body_content, $email_headers)) {
    http_response_code(200); // OK
    echo json_encode(['success' => true, 'message' => 'Message sent successfully! We will get back to you soon.']);
} else {
    // Log the mail failure for debugging
    error_log("Mail function failed to send email from " . $email . " to " . $recipient_email);
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again later. Our team has been notified of the issue.']);
}

exit;
?>
