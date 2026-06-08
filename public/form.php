<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/bookings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$fieldAliases = [
    'name' => ['name', 'fullName', 'full_name'],
    'phone' => ['phone', 'mobile'],
    'email' => ['email'],
    'customer_address' => ['customer_address', 'addressOrSuburb', 'address_or_suburb', 'address', 'suburb'],
    'vehicle' => ['vehicle', 'vehicleMakeModel', 'vehicle_make_model'],
    'date' => ['date', 'preferredDate', 'preferred_date'],
    'time' => ['time', 'preferredTimeWindow', 'preferred_time_window'],
    'package' => ['package', 'packageSelected', 'package_selected'],
];

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
    if (booking_post_value($fieldAliases[$field] ?? [$field]) === '') $errors[$field] = $message;
}
if (!filter_var(booking_post_value($fieldAliases['email']), FILTER_VALIDATE_EMAIL)) {
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
        'fullName' => booking_post_value($fieldAliases['name']),
        'phone' => booking_post_value($fieldAliases['phone']),
        'email' => booking_post_value($fieldAliases['email']),
        'addressOrSuburb' => booking_post_value($fieldAliases['customer_address']),
        'formattedAddress' => $_POST['formatted_address'] ?? '',
        'addressSuburb' => $_POST['address_suburb'] ?? '',
        'addressPostcode' => $_POST['address_postcode'] ?? '',
        'addressState' => $_POST['address_state'] ?? '',
        'addressCountry' => $_POST['address_country'] ?? '',
        'googlePlaceId' => $_POST['google_place_id'] ?? '',
        'addressLat' => $_POST['address_lat'] ?? '',
        'addressLng' => $_POST['address_lng'] ?? '',
        'vehicleMakeModel' => booking_post_value($fieldAliases['vehicle']),
        'preferredDate' => booking_post_value($fieldAliases['date']),
        'preferredTimeWindow' => booking_post_value($fieldAliases['time']),
        'packageSelected' => booking_post_value($fieldAliases['package']),
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

function booking_post_value(array $names): string {
    foreach ($names as $name) {
        if (isset($_POST[$name]) && clean_text($_POST[$name]) !== '') {
            return clean_text($_POST[$name]);
        }
    }
    return '';
}

function send_booking_email(array $booking): void {
    $adminEmail = getenv('ADMIN_EMAIL') ?: ($GLOBALS['SITE']['email'] ?? 'hello@shiningheadlights.com.au');
    $subject = 'New booking request - ' . ($booking['fullName'] ?? 'Customer');
    $body = "New booking request from shiningheadlights.com.au\n\n";
    $body .= "Name: " . ($booking['fullName'] ?? '') . "\n";
    $body .= "Phone: " . ($booking['phone'] ?? '') . "\n";
    $body .= "Email: " . ($booking['email'] ?? '') . "\n";
    $body .= "Location: " . ($booking['addressOrSuburb'] ?? '') . "\n";
    if (!empty($booking['formattedAddress'])) $body .= "Google Address: " . $booking['formattedAddress'] . "\n";
    if (!empty($booking['addressSuburb'])) $body .= "Suburb: " . $booking['addressSuburb'] . "\n";
    if (!empty($booking['addressPostcode'])) $body .= "Postcode: " . $booking['addressPostcode'] . "\n";
    if (!empty($booking['addressState'])) $body .= "State: " . $booking['addressState'] . "\n";
    if (!empty($booking['addressCountry'])) $body .= "Country: " . $booking['addressCountry'] . "\n";
    if (!empty($booking['addressLat']) && !empty($booking['addressLng'])) $body .= "Map: https://www.google.com/maps?q=" . $booking['addressLat'] . "," . $booking['addressLng'] . "\n";
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
