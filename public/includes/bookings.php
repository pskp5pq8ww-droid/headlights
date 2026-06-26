<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

const BOOKING_STATUSES = ['new', 'contacted', 'confirmed', 'completed', 'cancelled', 'no_show'];
// Separate from the workflow status above so existing dashboard logic is untouched.
const BOOKING_PAYMENT_STATUSES = ['unpaid', 'pending', 'paid', 'failed', 'refunded'];
const BOOKING_PACKAGE_PRICES = [
    'Basic Restore' => 99,
    'Crystal Restore' => 149,
    'Premium Protection Restore' => 199,
    'EOFY Launch Offer – $99' => 99,
    'Not sure / Quote' => 0,
];

function normalize_payment_status(string $status): string {
    $status = strtolower(trim($status));
    return in_array($status, BOOKING_PAYMENT_STATUSES, true) ? $status : 'unpaid';
}

/** Canonical price (in cents) for a package, validated server-side. 0 = quote only. */
function booking_amount_cents(string $package): int {
    return package_price($package) * 100;
}

function booking_storage_base(): string {
    $env = getenv('BOOKING_STORAGE_PATH');
    if ($env && trim($env) !== '') return rtrim($env, '/');
    return dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) . '/Storagehighlights';
}

function booking_dir(): string {
    return booking_storage_base() . '/bookings';
}

function booking_upload_dir(string $bookingId = ''): string {
    $base = booking_storage_base() . '/booking-uploads';
    return $bookingId !== '' ? $base . '/' . preg_replace('/[^A-Za-z0-9_-]/', '', $bookingId) : $base;
}

function booking_log_dir(): string {
    return booking_storage_base() . '/logs';
}

function ensureBookingStorageExists(): void {
    foreach ([booking_storage_base(), booking_dir(), booking_upload_dir(), booking_log_dir()] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create storage directory: ' . $dir);
        }
    }

    $htaccess = booking_storage_base() . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
        @chmod($htaccess, 0600);
    }
}

function booking_log_error(string $message): void {
    try {
        ensureBookingStorageExists();
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        @file_put_contents(booking_log_dir() . '/errors.log', $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable) {
        error_log($message);
    }
}

function booking_now(): string {
    return (new DateTimeImmutable('now', new DateTimeZone('Australia/Brisbane')))->format(DateTimeInterface::ATOM);
}

function booking_id(): string {
    try {
        return 'bk_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
    } catch (Throwable) {
        return 'bk_' . date('YmdHis') . '_' . substr(str_replace('.', '', uniqid('', true)), -10);
    }
}

function clean_text(mixed $value, int $max = 500): string {
    $text = trim((string)$value);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    $text = strip_tags($text);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) > $max) $text = mb_substr($text, 0, $max);
    } elseif (strlen($text) > $max) {
        $text = substr($text, 0, $max);
    }
    return $text;
}

function booking_file_path(string $id): string {
    $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
    return booking_dir() . '/booking_' . $safe . '.json';
}

function normalize_booking(array $booking): array {
    $created = $booking['createdAt'] ?? $booking['created_at'] ?? $booking['received_at'] ?? booking_now();
    $status = strtolower((string)($booking['status'] ?? 'new'));
    $legacyStatusMap = [
        'pending' => 'new',
        'confirmed' => 'confirmed',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ];
    $status = $legacyStatusMap[$status] ?? $status;
    if (!in_array($status, BOOKING_STATUSES, true)) $status = 'new';

    $address = $booking['addressOrSuburb'] ?? $booking['address_or_suburb'] ?? $booking['full_address'] ?? $booking['suburb'] ?? '';
    $package = $booking['packageSelected'] ?? $booking['package_selected'] ?? $booking['package'] ?? 'Not sure / Quote';

    return [
        'id' => (string)($booking['id'] ?? booking_id()),
        'createdAt' => (string)$created,
        'updatedAt' => (string)($booking['updatedAt'] ?? $booking['updated_at'] ?? $created),
        'status' => $status,
        'fullName' => (string)($booking['fullName'] ?? $booking['name'] ?? ''),
        'phone' => (string)($booking['phone'] ?? ''),
        'email' => (string)($booking['email'] ?? ''),
        'addressOrSuburb' => (string)$address,
        'formattedAddress' => (string)($booking['formattedAddress'] ?? $booking['formatted_address'] ?? ''),
        'addressSuburb' => (string)($booking['addressSuburb'] ?? $booking['address_suburb'] ?? ''),
        'addressPostcode' => (string)($booking['addressPostcode'] ?? $booking['address_postcode'] ?? ''),
        'addressState' => (string)($booking['addressState'] ?? $booking['address_state'] ?? ''),
        'addressCountry' => (string)($booking['addressCountry'] ?? $booking['address_country'] ?? ''),
        'googlePlaceId' => (string)($booking['googlePlaceId'] ?? $booking['google_place_id'] ?? ''),
        'addressLat' => (string)($booking['addressLat'] ?? $booking['address_lat'] ?? ''),
        'addressLng' => (string)($booking['addressLng'] ?? $booking['address_lng'] ?? ''),
        'vehicleMakeModel' => (string)($booking['vehicleMakeModel'] ?? $booking['vehicle'] ?? ''),
        'preferredDate' => (string)($booking['preferredDate'] ?? $booking['date'] ?? ''),
        'preferredTimeWindow' => (string)($booking['preferredTimeWindow'] ?? $booking['time'] ?? ''),
        'packageSelected' => (string)$package,
        'headlightCondition' => (string)($booking['headlightCondition'] ?? $booking['headlight_condition'] ?? ''),
        'vehicleLocationType' => (string)($booking['vehicleLocationType'] ?? $booking['vehicle_location_type'] ?? ''),
        'numberOfHeadlights' => (string)($booking['numberOfHeadlights'] ?? $booking['number_of_headlights'] ?? '2'),
        'preferredContactMethod' => (string)($booking['preferredContactMethod'] ?? $booking['preferred_contact_method'] ?? 'Phone'),
        'message' => (string)($booking['message'] ?? ''),
        'source' => (string)($booking['source'] ?? 'website'),
        // ── Payment fields (Square) ──────────────────────────────────────────
        'amount' => (float)($booking['amount'] ?? 0),
        'currency' => (string)($booking['currency'] ?? 'AUD'),
        'paymentStatus' => normalize_payment_status((string)($booking['paymentStatus'] ?? $booking['payment_status'] ?? 'unpaid')),
        'squarePaymentId' => (string)($booking['squarePaymentId'] ?? $booking['square_payment_id'] ?? ''),
        'squareOrderId' => (string)($booking['squareOrderId'] ?? $booking['square_order_id'] ?? ''),
        'squareReceiptUrl' => (string)($booking['squareReceiptUrl'] ?? $booking['square_receipt_url'] ?? ''),
        'cardBrand' => (string)($booking['cardBrand'] ?? ''),
        'cardLast4' => (string)($booking['cardLast4'] ?? ''),
        'paidAt' => (string)($booking['paidAt'] ?? ''),
        'adminNotes' => (string)($booking['adminNotes'] ?? $booking['admin_notes'] ?? ''),
        'followUpRequired' => (bool)($booking['followUpRequired'] ?? $booking['follow_up_required'] ?? false),
        'followUpDate' => (string)($booking['followUpDate'] ?? $booking['follow_up_date'] ?? ''),
        'photos' => is_array($booking['photos'] ?? null) ? $booking['photos'] : [],
        'history' => is_array($booking['history'] ?? null) ? $booking['history'] : [],
        'deleted' => (bool)($booking['deleted'] ?? false),
    ];
}

function readBookings(bool $includeDeleted = false): array {
    ensureBookingStorageExists();
    $bookings = [];

    foreach (glob(booking_dir() . '/booking_*.json') ?: [] as $file) {
        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            @rename($file, $file . '.broken-' . date('YmdHis'));
            booking_log_error('Invalid booking JSON backed up: ' . basename($file));
            continue;
        }
        $booking = normalize_booking($data);
        if (!$includeDeleted && !empty($booking['deleted'])) continue;
        $booking['_file'] = basename($file);
        $bookings[] = $booking;
    }

    usort($bookings, fn($a, $b) => strcmp((string)$b['createdAt'], (string)$a['createdAt']));
    return $bookings;
}

function writeBooking(array $booking): array {
    ensureBookingStorageExists();
    $booking = normalize_booking($booking);
    $path = booking_file_path($booking['id']);
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($booking, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new RuntimeException('Unable to encode booking JSON');
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Unable to write booking temp file');
    @chmod($tmp, 0600);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to publish booking file');
    }
    @chmod($path, 0600);
    return $booking;
}

function writeBookings(array $bookings): void {
    foreach ($bookings as $booking) writeBooking($booking);
}

function getBookingById(string $id): ?array {
    $path = booking_file_path($id);
    if (!is_file($path)) return null;
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) ? normalize_booking($data) : null;
}

function createBooking(array $data): array {
    $now = booking_now();
    $booking = normalize_booking([
        'id' => booking_id(),
        'createdAt' => $now,
        'updatedAt' => $now,
        'status' => 'new',
        'fullName' => clean_text($data['fullName'] ?? $data['name'] ?? '', 120),
        'phone' => clean_text($data['phone'] ?? '', 60),
        'email' => clean_text($data['email'] ?? '', 160),
        'addressOrSuburb' => clean_text($data['addressOrSuburb'] ?? $data['customer_address'] ?? $data['suburb'] ?? '', 240),
        'formattedAddress' => clean_text($data['formattedAddress'] ?? $data['formatted_address'] ?? '', 300),
        'addressSuburb' => clean_text($data['addressSuburb'] ?? $data['address_suburb'] ?? '', 120),
        'addressPostcode' => clean_text($data['addressPostcode'] ?? $data['address_postcode'] ?? '', 20),
        'addressState' => clean_text($data['addressState'] ?? $data['address_state'] ?? '', 40),
        'addressCountry' => clean_text($data['addressCountry'] ?? $data['address_country'] ?? '', 80),
        'googlePlaceId' => clean_text($data['googlePlaceId'] ?? $data['google_place_id'] ?? '', 180),
        'addressLat' => clean_text($data['addressLat'] ?? $data['address_lat'] ?? '', 40),
        'addressLng' => clean_text($data['addressLng'] ?? $data['address_lng'] ?? '', 40),
        'vehicleMakeModel' => clean_text($data['vehicleMakeModel'] ?? $data['vehicle'] ?? '', 160),
        'preferredDate' => clean_text($data['preferredDate'] ?? $data['date'] ?? '', 40),
        'preferredTimeWindow' => clean_text($data['preferredTimeWindow'] ?? $data['time'] ?? '', 80),
        'packageSelected' => clean_text($data['packageSelected'] ?? $data['package'] ?? 'Not sure / Quote', 120),
        'headlightCondition' => clean_text($data['headlightCondition'] ?? '', 120),
        'vehicleLocationType' => clean_text($data['vehicleLocationType'] ?? '', 80),
        'numberOfHeadlights' => clean_text($data['numberOfHeadlights'] ?? '2', 10),
        'preferredContactMethod' => clean_text($data['preferredContactMethod'] ?? 'Phone', 40),
        'message' => clean_text($data['message'] ?? '', 2000),
        'source' => clean_text($data['source'] ?? 'website', 80),
        'adminNotes' => '',
        'history' => [[
            'at' => $now,
            'type' => 'created',
            'message' => 'Booking request received.',
        ]],
    ]);

    return writeBooking($booking);
}

function updateBooking(string $id, array $updates): ?array {
    $booking = getBookingById($id);
    if (!$booking) return null;
    $allowed = [
        'status', 'adminNotes', 'followUpRequired', 'followUpDate', 'preferredDate',
        'preferredTimeWindow', 'fullName', 'phone', 'email', 'addressOrSuburb',
        'formattedAddress', 'addressSuburb', 'addressPostcode', 'addressState',
        'addressCountry', 'googlePlaceId', 'addressLat', 'addressLng',
        'vehicleMakeModel', 'packageSelected', 'headlightCondition',
        'vehicleLocationType', 'numberOfHeadlights', 'preferredContactMethod', 'message', 'photos',
    ];
    $oldStatus = $booking['status'];
    foreach ($allowed as $key) {
        if (!array_key_exists($key, $updates)) continue;
        if ($key === 'status') {
            $status = strtolower(clean_text($updates[$key], 40));
            if (in_array($status, BOOKING_STATUSES, true)) $booking[$key] = $status;
        } elseif ($key === 'followUpRequired') {
            $booking[$key] = (bool)$updates[$key];
        } elseif ($key === 'photos' && is_array($updates[$key])) {
            $booking[$key] = $updates[$key];
        } else {
            $booking[$key] = clean_text($updates[$key], $key === 'adminNotes' ? 2000 : 500);
        }
    }
    $booking['updatedAt'] = booking_now();
    if ($oldStatus !== $booking['status']) {
        $booking['history'][] = [
            'at' => $booking['updatedAt'],
            'type' => 'status',
            'message' => 'Status changed from ' . $oldStatus . ' to ' . $booking['status'] . '.',
        ];
    }
    return writeBooking($booking);
}

/**
 * Apply a payment result to a booking. Keeps the workflow status separate and
 * records a history entry. Used by the Square payment endpoint.
 */
function updateBookingPayment(string $id, array $payment): ?array {
    $booking = getBookingById($id);
    if (!$booking) return null;
    $now = booking_now();
    $status = normalize_payment_status((string)($payment['paymentStatus'] ?? 'paid'));
    $booking['paymentStatus'] = $status;
    if (isset($payment['amount'])) $booking['amount'] = (float)$payment['amount'];
    if (!empty($payment['currency'])) $booking['currency'] = (string)$payment['currency'];
    foreach (['squarePaymentId', 'squareOrderId', 'squareReceiptUrl', 'cardBrand', 'cardLast4'] as $key) {
        if (isset($payment[$key])) $booking[$key] = (string)$payment[$key];
    }
    if ($status === 'paid') {
        $booking['paidAt'] = $now;
        if (in_array($booking['status'], ['new'], true)) $booking['status'] = 'confirmed';
    }
    $booking['updatedAt'] = $now;
    $booking['history'][] = [
        'at' => $now,
        'type' => 'payment',
        'message' => 'Payment ' . $status . ($booking['squarePaymentId'] ? ' (Square ' . $booking['squarePaymentId'] . ')' : '') . '.',
    ];
    return writeBooking($booking);
}

function deleteBooking(string $id): ?array {
    $booking = getBookingById($id);
    if (!$booking) return null;
    $booking['deleted'] = true;
    $booking['status'] = 'cancelled';
    $booking['updatedAt'] = booking_now();
    $booking['history'][] = [
        'at' => $booking['updatedAt'],
        'type' => 'deleted',
        'message' => 'Booking hidden from dashboard.',
    ];
    return writeBooking($booking);
}

function getBookingsByDate(string $date): array {
    return array_values(array_filter(readBookings(), fn($b) => ($b['preferredDate'] ?? '') === $date));
}

function package_price(string $package): int {
    return BOOKING_PACKAGE_PRICES[$package] ?? 0;
}

function getBookingStats(array $bookings = null): array {
    $bookings ??= readBookings();
    $tz = new DateTimeZone('Australia/Brisbane');
    $now = new DateTimeImmutable('now', $tz);
    $today = $now->format('Y-m-d');
    $weekStart = $now->modify('monday this week')->format('Y-m-d');
    $weekEnd = $now->modify('sunday this week')->format('Y-m-d');
    $month = $now->format('Y-m');
    $stats = [
        'total' => count($bookings),
        'new' => 0,
        'contacted' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'today' => 0,
        'week' => 0,
        'month' => 0,
        'estimatedRevenue' => 0,
        'paid' => 0,
        'paidRevenue' => 0,
        'todayCreated' => 0,
        'pendingFollowUps' => 0,
        'mostSelectedPackage' => 'None',
        'packageBreakdown' => [],
        'topSuburbs' => [],
    ];
    $suburbs = [];
    foreach ($bookings as $b) {
        $status = $b['status'] ?? 'new';
        if (isset($stats[$status])) $stats[$status]++;
        $date = $b['preferredDate'] ?? '';
        if ($date === $today) $stats['today']++;
        if ($date >= $weekStart && $date <= $weekEnd && $date !== '') $stats['week']++;
        if (str_starts_with($date, $month)) $stats['month']++;
        if (in_array($status, ['confirmed', 'completed'], true)) {
            $stats['estimatedRevenue'] += package_price($b['packageSelected'] ?? '');
        }
        if (($b['paymentStatus'] ?? 'unpaid') === 'paid') {
            $stats['paid']++;
            $stats['paidRevenue'] += (float)($b['amount'] ?? package_price($b['packageSelected'] ?? ''));
        }
        if (str_starts_with((string)($b['createdAt'] ?? ''), $today)) $stats['todayCreated']++;
        if (!empty($b['followUpRequired']) || $status === 'new' || $status === 'contacted') $stats['pendingFollowUps']++;
        $pkg = $b['packageSelected'] ?: 'Not sure / Quote';
        $stats['packageBreakdown'][$pkg] = ($stats['packageBreakdown'][$pkg] ?? 0) + 1;
        $area = trim((string)($b['addressOrSuburb'] ?? ''));
        if ($area !== '') $suburbs[$area] = ($suburbs[$area] ?? 0) + 1;
    }
    arsort($stats['packageBreakdown']);
    arsort($suburbs);
    $stats['topSuburbs'] = array_slice($suburbs, 0, 5, true);
    $stats['mostSelectedPackage'] = array_key_first($stats['packageBreakdown']) ?: 'None';
    $stats['conversionEstimate'] = $stats['total'] > 0 ? round((($stats['confirmed'] + $stats['completed']) / $stats['total']) * 100, 1) : 0;
    return $stats;
}

function send_booking_email(array $booking): void {
    $adminEmail = getenv('ADMIN_EMAIL') ?: ($GLOBALS['SITE']['email'] ?? 'hello@shiningheadlights.com.au');
    $paid = ($booking['paymentStatus'] ?? '') === 'paid';
    $subject = ($paid ? 'PAID booking' : 'New booking request') . ' - ' . ($booking['fullName'] ?? 'Customer');
    $body  = "Booking from shiningheadlights.com.au\n\n";
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
    if ($paid) {
        $body .= "\n-- Payment --\n";
        $body .= "Status: PAID\n";
        $body .= "Amount: " . ($booking['currency'] ?? 'AUD') . ' $' . number_format((float)($booking['amount'] ?? 0), 2) . "\n";
        $body .= "Square Payment ID: " . ($booking['squarePaymentId'] ?? '') . "\n";
        if (!empty($booking['squareReceiptUrl'])) $body .= "Receipt: " . $booking['squareReceiptUrl'] . "\n";
        if (!empty($booking['cardBrand'])) $body .= "Card: " . $booking['cardBrand'] . ' ****' . ($booking['cardLast4'] ?? '') . "\n";
    }

    $headers  = "From: " . ($GLOBALS['SITE']['email'] ?? 'hello@shiningheadlights.com.au') . "\r\n";
    $headers .= "Reply-To: " . ($booking['email'] ?? '') . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!@mail($adminEmail, $subject, $body, $headers)) {
        booking_log_error('mail() failed for booking ' . ($booking['id'] ?? 'unknown'));
    }
}

function booking_json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
