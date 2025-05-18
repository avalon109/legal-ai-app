<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/auth_functions.php';

header('Content-Type: application/json');

// Check if user is authenticated
$auth_data = validateToken();
if (!$auth_data['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Make sure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate required fields
if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid email address is required'
    ]);
    exit;
}

if (!isset($data['content']) || empty($data['content'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Chat content is required'
    ]);
    exit;
}

// Get user info for personalization
$user_id = $auth_data['user_id'];
$db = new Database();
$user = $db->query("SELECT username, email FROM users WHERE id = ?", [$user_id])->fetch();

// Create a clean text version of the chat content
$chat_html = $data['content'];
$chat_text = strip_tags(str_replace(['<br>', '<p>', '</p>'], ["\n", "\n", ''], $chat_html));

// Set up email
$to = $data['email'];
$subject = "Your chat transcript";
$username = htmlspecialchars($user['username']);

// Create email body with both HTML and plain text versions
$boundary = md5(time());

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    'From: TenantRights@volantic.systems',
    'Reply-To: TenantRights@volantic.systems',
    'X-Mailer: PHP/' . phpversion()
];

// Plain text version
$message = "--" . $boundary . "\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= "Hello $username,\r\n\r\n";
$message .= "Here is your chat history from Legal AI Assistant:\r\n\r\n";
$message .= $chat_text . "\r\n\r\n";
$message .= "Thank you for using our service!\r\n";
$message .= "--" . $boundary . "\r\n";

// HTML version
$message .= "Content-Type: text/html; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= "<!DOCTYPE html>\r\n";
$message .= "<html><head><title>Your Chat History</title></head>\r\n";
$message .= "<body style='font-family: Arial, sans-serif;'>\r\n";
$message .= "<h2>Hello $username,</h2>\r\n";
$message .= "<p>Here is your chat history from Legal AI Assistant:</p>\r\n";
$message .= "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 5px;'>\r\n";
$message .= $chat_html . "\r\n";
$message .= "</div>\r\n";
$message .= "<p>Thank you for using our service!</p>\r\n";
$message .= "</body></html>\r\n";
$message .= "--" . $boundary . "--";

// Set mail server parameters for mail.volantic.systems on port 25
ini_set('SMTP', 'mail.volantic.systems');
ini_set('smtp_port', 25);
ini_set('sendmail_from', 'TenantRights@volantic.systems');

// Send email
$mail_sent = mail($to, $subject, $message, implode("\r\n", $headers));

if ($mail_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email'
    ]);
} 