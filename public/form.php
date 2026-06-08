<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/bookings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$required = [
    'name' => 'Full name is required.',
    'phone' => 'Phone is required.',
    'email' => 'A valid email is required.',
    'customer_address' => 'Service address or suburb is required.',
    'vehicle' => 'Vehicle make and model is required.',
    'date' => 'Preferred date is required.',
    'time' => 'Preferred time window is required.',
    'package' => 'Package is required.',
];

$errors = [];
foreach ($required as $field => $message) {
    if (clean_text($_POST[$field] ?? '') === '') $errors[$field] = $message;
}
if (!filter_var(clean_text($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'A valid email is required.';
}
if ($errors) {
    booking_json_response([
        'success' => false,
        'message' => 'Please complete all required fields.',
        'errors' => $errors,
    ], 422);
}

try {
    $booking = createBooking([
        'fullName' => $_POST['name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'addressOrSuburb' => $_POST['customer_address'] ?? '',
        'vehicleMakeModel' => $_POST['vehicle'] ?? '',
        'preferredDate' => $_POST['date'] ?? '',
        'preferredTimeWindow' => $_POST['time'] ?? '',
        'packageSelected' => $_POST['package'] ?? '',
        'headlightCondition' => $_POST['headlight_condition'] ?? '',
        'vehicleLocationType' => $_POST['vehicle_location_type'] ?? '',
        'numberOfHeadlights' => $_POST['number_of_headlights'] ?? '2',
        'preferredContactMethod' => $_POST['preferred_contact_method'] ?? 'Phone',
        'message' => $_POST['message'] ?? '',
        'source' => $_POST['source'] ?? 'public_booking_form',
    ]);

    $photos = handle_booking_uploads($booking['id']);
    if ($photos) {
        $booking = updateBooking($booking['id'], ['photos' => $photos]) ?? $booking;
    }

    send_booking_email($booking);

    booking_json_response([
        'success' => true,
        'booking_id' => $booking['id'],
        'message' => "Thanks! Your booking request has been received. We'll contact you shortly to confirm your mobile service.",
    ]);
} catch (Throwable $e) {
    booking_log_error('Public booking failed: ' . $e->getMessage());
    booking_json_response([
        'success' => false,
        'message' => 'Something went wrong. Please try again or contact us directly.',
    ], 500);
}

function handle_booking_uploads(string $bookingId): array {
    if (empty($_FILES['photos']['name']) || !is_array($_FILES['photos']['name'])) return [];

    $saved = [];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $dir = booking_upload_dir($bookingId);
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        booking_log_error('Unable to create upload directory for booking ' . $bookingId);
        return [];
    }

    foreach ($_FILES['photos']['name'] as $index => $originalName) {
        $error = $_FILES['photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) {
            booking_log_error('Upload error ' . $error . ' for booking ' . $bookingId);
            continue;
        }
        $tmp = $_FILES['photos']['tmp_name'][$index] ?? '';
        $size = (int)($_FILES['photos']['size'][$index] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024 || !is_uploaded_file($tmp)) continue;

        $mime = function_exists('mime_content_type') ? (mime_content_type($tmp) ?: '') : (string)($_FILES['photos']['type'][$index] ?? '');
        if (!isset($allowed[$mime])) continue;

        $filename = 'photo_' . ($index + 1) . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
        $target = $dir . '/' . $filename;
        if (move_uploaded_file($tmp, $target)) {
            @chmod($target, 0600);
            $saved[] = [
                'file' => $filename,
                'originalName' => clean_text($originalName, 160),
                'mime' => $mime,
                'size' => $size,
            ];
        }
    }
    return $saved;
}

function send_booking_email(array $booking): void {
    $adminEmail = getenv('ADMIN_EMAIL') ?: ($GLOBALS['SITE']['email'] ?? 'hello@shiningheadlights.com.au');
    $subject = 'New booking request - ' . ($booking['fullName'] ?? 'Customer');
    $body = "New booking request from shiningheadlights.com.au\n\n";
    $body .= "Name: " . ($booking['fullName'] ?? '') . "\n";
    $body .= "Phone: " . ($booking['phone'] ?? '') . "\n";
    $body .= "Email: " . ($booking['email'] ?? '') . "\n";
    $body .= "Location: " . ($booking['addressOrSuburb'] ?? '') . "\n";
    $body .= "Vehicle: " . ($booking['vehicleMakeModel'] ?? '') . "\n";
    $body .= "Date: " . ($booking['preferredDate'] ?? '') . "\n";
    $body .= "Time: " . ($booking['preferredTimeWindow'] ?? '') . "\n";
    $body .= "Package: " . ($booking['packageSelected'] ?? '') . "\n";
    $body .= "Condition: " . ($booking['headlightCondition'] ?? '') . "\n";
    $body .= "Preferred contact: " . ($booking['preferredContactMethod'] ?? '') . "\n";
    $body .= "Message: " . ($booking['message'] ?? '') . "\n";

    $headers = "From: " . ($GLOBALS['SITE']['email'] ?? 'hello@shiningheadlights.com.au') . "\r\n";
    $headers .= "Reply-To: " . ($booking['email'] ?? '') . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!@mail($adminEmail, $subject, $body, $headers)) {
        booking_log_error('mail() failed for booking ' . ($booking['id'] ?? 'unknown'));
    }
}
