<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/services.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$fieldAliases = [
    'name' => ['name', 'fullName', 'full_name'],
    'phone' => ['phone', 'mobile'],
    'email' => ['email'],
    'customer_address' => ['customer_address', 'addressOrSuburb', 'address_or_suburb', 'address', 'suburb'],
    'vehicle' => ['vehicle', 'vehicleMakeModel', 'vehicle_make_model'],
    'vehicle_size' => ['vehicle_size', 'vehicleSize'],
    'date' => ['date', 'preferredDate', 'preferred_date'],
    'time' => ['time', 'preferredTimeWindow', 'preferred_time_window'],
    'package' => ['package', 'packageSelected', 'package_selected'],
    'terms_accepted' => ['terms_accepted', 'termsAccepted'],
];

$required = [
    'name' => 'Full name is required.',
    'phone' => 'Phone is required.',
    'email' => 'A valid email is required.',
    'customer_address' => 'Service address or suburb is required.',
    'vehicle' => 'Vehicle make and model is required.',
    'vehicle_size' => 'Vehicle size is required.',
    'date' => 'Preferred date is required.',
    'time' => 'Preferred time window is required.',
    'package' => 'Package is required.',
    'terms_accepted' => 'You must accept the Terms & Conditions before booking.',
];

$errors = [];
foreach ($required as $field => $message) {
    if (booking_post_value($fieldAliases[$field] ?? [$field]) === '') $errors[$field] = $message;
}
if (!terms_was_accepted($_POST['terms_accepted'] ?? $_POST['termsAccepted'] ?? '')) {
    $errors['terms_accepted'] = 'You must accept the Terms & Conditions before booking.';
}
if (!filter_var(booking_post_value($fieldAliases['email']), FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'A valid email is required.';
}
$vehicleSize = booking_post_value($fieldAliases['vehicle_size']);
if (!array_key_exists($vehicleSize, VEHICLE_SIZE_LABELS)) {
    $errors['vehicle_size'] = 'Please choose a valid vehicle size.';
}
if ($errors) {
    booking_json_response([
        'success' => false,
        'message' => 'Please complete all required fields.',
        'errors' => $errors,
    ], 422);
}

try {
    $selectedServices = build_selected_services_snapshot(
        $_POST['selected_services'] ?? [],
        $vehicleSize,
        $_POST['number_of_headlights'] ?? '2'
    );
    $estimatedTotal = selected_services_total($selectedServices);
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
        'vehicleSize' => $vehicleSize,
        'preferredDate' => booking_post_value($fieldAliases['date']),
        'preferredTimeWindow' => booking_post_value($fieldAliases['time']),
        'packageSelected' => booking_post_value($fieldAliases['package']),
        'headlightCondition' => $_POST['headlight_condition'] ?? '',
        'vehicleLocationType' => $_POST['vehicle_location_type'] ?? '',
        'numberOfHeadlights' => $_POST['number_of_headlights'] ?? '2',
        'preferredContactMethod' => $_POST['preferred_contact_method'] ?? 'Phone',
        'message' => $_POST['message'] ?? '',
        'selectedServices' => $selectedServices,
        'estimatedTotal' => $estimatedTotal,
        'pricingNote' => 'Final price may change after inspection if vehicle condition is excessive.',
        'source' => $_POST['source'] ?? 'public_booking_form',
        'termsAccepted' => terms_was_accepted($_POST['terms_accepted'] ?? $_POST['termsAccepted'] ?? ''),
        'termsVersion' => $_POST['terms_version'] ?? '2026-06-27-eofy',
    ]);

    $photos = handle_booking_uploads($booking['id']);
    if ($photos) {
        $booking = updateBooking($booking['id'], ['photos' => $photos]) ?? $booking;
    }

    send_booking_email($booking);

    // Server-side Meta conversion (Conversions API). Same event_id as the
    // browser pixel on /thank-you, so Meta de-duplicates and counts it once.
    require_once __DIR__ . '/includes/meta-capi.php';
    @meta_send_booking_event($booking, 'Lead', false, meta_source_url('/thank-you?booking=' . $booking['id']));

    booking_json_response([
        'success' => true,
        'booking_id' => $booking['id'],
        'redirect_url' => '/thank-you?booking=' . rawurlencode($booking['id']),
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

function terms_was_accepted(mixed $value): bool {
    return in_array(strtolower(trim((string)$value)), ['1', 'yes', 'true', 'on', 'accepted'], true);
}
