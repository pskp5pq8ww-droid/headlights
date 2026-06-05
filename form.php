<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
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

if (!$name || !$phone || !$email || !$suburb || !$vehicle || !$date || !$time || !$package) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$subject   = "New Booking Request – {$name} ({$vehicle})";

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

$headers  = "From: noreply@shiningheadlights.com.au\r\n";
$headers .= "Reply-To: {$safeEmail}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

if (mail($to, $subject, $body, $headers)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not send your request. Please call us directly.']);
}
