<?php
/**
 * SIMULATED payment gateway.
 * No real charge is made. This marks a saved booking as "paid" and stores a
 * fake reference so the flow and dashboard can be tested end-to-end.
 * Replace with a real Stripe/PayPal integration later — keep the same
 * input (booking_id) and the same JSON response shape.
 */
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

define('STORAGE_BASE', dirname($_SERVER['DOCUMENT_ROOT']) . '/Storagehighlights');
define('BOOKINGS_DIR', STORAGE_BASE . '/bookings');

function respond(array $data): void {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(['success' => false, 'message' => 'Method not allowed']);
}

$bookingId = trim($_POST['booking_id'] ?? '');
$cardNum   = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
$cardName  = trim($_POST['card_name'] ?? '');

// Validate the booking id format and locate the saved booking
if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}$/', $bookingId)) {
    respond(['success' => false, 'message' => 'Invalid booking reference.']);
}
$path = BOOKINGS_DIR . '/booking_' . $bookingId . '.json';
if (!is_file($path)) {
    respond(['success' => false, 'message' => 'Booking not found. Please contact us to complete payment.']);
}

if (strlen($cardNum) < 12 || $cardName === '') {
    respond(['success' => false, 'message' => 'Please enter valid card details.']);
}

// ── Simulated authorisation ──────────────────────────────────────────────────
// Any card is approved EXCEPT one ending in 0002, which simulates a decline.
if (substr($cardNum, -4) === '0002') {
    respond(['success' => false, 'message' => 'Payment declined (simulated). Please try another card.']);
}

$data = json_decode(file_get_contents($path), true);
if (!is_array($data)) {
    respond(['success' => false, 'message' => 'Could not read booking record.']);
}

$reference = 'SIM-' . strtoupper(bin2hex(random_bytes(5)));
$data['payment_status'] = 'paid';
$data['payment_ref']    = $reference;
$data['payment_method'] = 'Card (simulated)';
$data['card_last4']     = substr($cardNum, -4);
$data['paid_at']        = date('Y-m-d H:i:s');
if (($data['status'] ?? '') === 'Pending') {
    $data['status'] = 'Confirmed'; // auto-confirm once paid
}

file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

respond([
    'success'   => true,
    'reference' => $reference,
    'amount'    => $data['amount'] ?? 99,
    'message'   => 'Payment received (simulated).',
]);
