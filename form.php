<?php
// Suppress PHP notices/warnings so they don't corrupt JSON output
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$to      = 'hello@shiningheadlights.com.au';
$name    = htmlspecialchars(trim($_POST['name']    ?? ''), ENT_QUOTES, 'UTF-8');
$phone   = htmlspecialchars(trim($_POST['phone']   ?? ''), ENT_QUOTES, 'UTF-8');
$email   = trim($_POST['email']   ?? '');
$suburb  = htmlspecialchars(trim($_POST['suburb']  ?? ''), ENT_QUOTES, 'UTF-8');
$vehicle = htmlspecialchars(trim($_POST['vehicle'] ?? ''), ENT_QUOTES, 'UTF-8');
$date    = htmlspecialchars(trim($_POST['date']    ?? ''), ENT_QUOTES, 'UTF-8');
$time    = htmlspecialchars(trim($_POST['time']    ?? ''), ENT_QUOTES, 'UTF-8');
$package = htmlspecialchars(trim($_POST['package'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
$uploadedPhotoNames = [];

if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
    foreach ($_FILES['photos']['name'] as $index => $fileName) {
        if ($_FILES['photos']['error'][$index] === UPLOAD_ERR_OK && $fileName) {
            $uploadedPhotoNames[] = htmlspecialchars(basename($fileName), ENT_QUOTES, 'UTF-8');
        }
    }
}

if (!$name || !$phone || !$email || !$suburb || !$vehicle || !$date || !$time || !$package) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

// Plain ASCII subject to avoid encoding issues on shared mail servers
$subject = "New Booking Request - {$name} ({$vehicle})";

$body  = "New booking request from shiningheadlights.com.au\n\n";
$body .= "Name:            {$name}\n";
$body .= "Phone:           {$phone}\n";
$body .= "Email:           {$safeEmail}\n";
$body .= "Suburb/Location: {$suburb}\n";
$body .= "Vehicle:         {$vehicle}\n";
$body .= "Preferred Date:  {$date}\n";
$body .= "Preferred Time:  {$time}\n";
$body .= "Package:         {$package}\n";
if ($message) {
    $body .= "Message:\n{$message}\n";
}
if ($uploadedPhotoNames) {
    $body .= "\nUploaded photo file names:\n";
    $body .= implode("\n", $uploadedPhotoNames) . "\n";
    $body .= "\nNote: photos are not attached by default. Ask the customer to send photos by reply if needed.\n";
}

// From must be a real email account configured on the Hostinger account
$headers  = "From: hello@shiningheadlights.com.au\r\n";
$headers .= "Reply-To: {$safeEmail}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($to, $subject, $body, $headers);

ob_end_clean();
header('Content-Type: application/json');
echo json_encode($sent
    ? ['success' => true]
    : ['success' => false, 'message' => 'Could not send your request. Please call us directly.']
);
