<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_admin();
require_once __DIR__ . '/../../includes/bookings.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = clean_text($_GET['id'] ?? '', 80);

try {
    if ($method === 'GET') {
        if ($id !== '') {
            $booking = getBookingById($id);
            if (!$booking) booking_json_response(['success' => false, 'message' => 'Booking not found'], 404);
            booking_json_response(['success' => true, 'booking' => $booking]);
        }

        $date = clean_text($_GET['date'] ?? '', 40);
        $bookings = $date !== '' ? getBookingsByDate($date) : readBookings();
        booking_json_response(['success' => true, 'bookings' => $bookings]);
    }

    if ($method === 'PATCH' || $method === 'POST') {
        $input = [];
        if ($method === 'PATCH') {
            $raw = file_get_contents('php://input');
            $input = json_decode((string)$raw, true) ?: [];
        } else {
            $input = $_POST;
        }
        $id = $id ?: clean_text($input['id'] ?? '', 80);
        if ($id === '') booking_json_response(['success' => false, 'message' => 'Missing booking id'], 422);
        $booking = updateBooking($id, $input);
        if (!$booking) booking_json_response(['success' => false, 'message' => 'Booking not found'], 404);
        booking_json_response(['success' => true, 'booking' => $booking]);
    }

    if ($method === 'DELETE') {
        if ($id === '') booking_json_response(['success' => false, 'message' => 'Missing booking id'], 422);
        $booking = updateBooking($id, ['status' => 'cancelled']);
        if (!$booking) booking_json_response(['success' => false, 'message' => 'Booking not found'], 404);
        booking_json_response(['success' => true, 'booking' => $booking]);
    }

    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    booking_log_error('Admin bookings API failed: ' . $e->getMessage());
    booking_json_response(['success' => false, 'message' => 'Unable to load bookings. Please check server storage or permissions.'], 500);
}
