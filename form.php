<?php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

// ── Storage path ────────────────────────────────────────────────────────────
// One level above public_html (outside web root) → /home/uXXX/Storagehighlights
define('STORAGE_BASE', dirname($_SERVER['DOCUMENT_ROOT']) . '/Storagehighlights');
define('BOOKINGS_DIR', STORAGE_BASE . '/bookings');
define('LOGS_DIR',     STORAGE_BASE . '/logs');

function ensureStorageDirs(): void {
    foreach ([STORAGE_BASE, BOOKINGS_DIR, LOGS_DIR] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
    }
    // Extra .htaccess inside storage just in case the folder ends up web-accessible
    $htaccess = STORAGE_BASE . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
        chmod($htaccess, 0600);
    }
}

function saveBooking(array $data): void {
    ensureStorageDirs();
    $filename = BOOKINGS_DIR . '/booking_' . date('Y-m-d_His') . '_' . bin2hex(random_bytes(4)) . '.json';
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    chmod($filename, 0600);
}

function logError(string $msg): void {
    ensureStorageDirs();
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    file_put_contents(LOGS_DIR . '/errors.log', $line, FILE_APPEND | LOCK_EX);
}

// ── Request guard ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Sanitise inputs ───────────────────────────────────────────────────────────
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

$photoNames = [];
if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
    foreach ($_FILES['photos']['name'] as $i => $fname) {
        if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && $fname) {
            $photoNames[] = htmlspecialchars(basename($fname), ENT_QUOTES, 'UTF-8');
        }
    }
}

// ── Validation ────────────────────────────────────────────────────────────────
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

// ── Save booking to /Storagehighlights/bookings/ ──────────────────────────────
$bookingData = [
    'received_at' => date('Y-m-d H:i:s'),
    'name'        => $name,
    'phone'       => $phone,
    'email'       => $safeEmail,
    'suburb'      => $suburb,
    'vehicle'     => $vehicle,
    'date'        => $date,
    'time'        => $time,
    'package'     => $package,
    'message'     => $message,
    'photos'      => $photoNames,
    'ip'          => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),  // hashed, not raw
];

try {
    saveBooking($bookingData);
} catch (Throwable $e) {
    logError('saveBooking failed: ' . $e->getMessage());
}

// ── Send email ────────────────────────────────────────────────────────────────
$subject  = "New Booking - {$name} ({$vehicle})";
$body     = "New booking from shiningheadlights.com.au\n\n";
$body    .= "Name:     {$name}\nPhone:    {$phone}\nEmail:    {$safeEmail}\n";
$body    .= "Location: {$suburb}\nVehicle:  {$vehicle}\n";
$body    .= "Date:     {$date}\nTime:     {$time}\nPackage:  {$package}\n";
if ($message)    $body .= "\nMessage:\n{$message}\n";
if ($photoNames) $body .= "\nPhotos attached by customer: " . implode(', ', $photoNames) . "\n";

$headers  = "From: hello@shiningheadlights.com.au\r\n";
$headers .= "Reply-To: {$safeEmail}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($to, $subject, $body, $headers);

if (!$sent) logError("mail() failed for booking: {$name} <{$safeEmail}>");

ob_end_clean();
header('Content-Type: application/json');
echo json_encode($sent
    ? ['success' => true]
    : ['success' => false, 'message' => 'Could not send your request. Please call us directly.']
);
