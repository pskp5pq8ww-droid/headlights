<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();
require_once __DIR__ . '/../../includes/bookings.php';

try {
    $bookings = readBookings();
    booking_json_response(['success' => true, 'metrics' => getBookingStats($bookings)]);
} catch (Throwable $e) {
    booking_log_error('Admin metrics API failed: ' . $e->getMessage());
    booking_json_response(['success' => false, 'message' => 'Unable to load metrics. Please check server storage or permissions.'], 500);
}
