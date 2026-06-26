<?php
declare(strict_types=1);

/**
 * POST /api/payments/square
 *
 * Single-shot "create payment": validates the booking, validates the price
 * SERVER-SIDE, charges the card with Square, and only then saves the booking
 * as paid. Failed attempts are recorded separately in the payments store.
 *
 * Expected JSON body:
 *   {
 *     "sourceId": "<token from Web Payments SDK>",
 *     "idempotencyKey": "<unique per attempt>",
 *     "booking": { name, email, phone, package, ... }
 *   }
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/square.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

// ── Read input (JSON preferred, form-data fallback) ──────────────────────────
$raw = file_get_contents('php://input');
$input = json_decode((string)$raw, true);
if (!is_array($input)) $input = $_POST;

$sourceId       = trim((string)($input['sourceId'] ?? ''));
$idempotencyKey = trim((string)($input['idempotencyKey'] ?? ''));
$b              = is_array($input['booking'] ?? null) ? $input['booking'] : $input;

if ($idempotencyKey === '' || strlen($idempotencyKey) > 45) {
    $idempotencyKey = bin2hex(random_bytes(16));
}
if ($sourceId === '') {
    booking_json_response(['success' => false, 'message' => 'Missing payment token. Please re-enter your card.'], 422);
}

// ── Validate booking data ────────────────────────────────────────────────────
$name    = clean_text($b['name'] ?? $b['fullName'] ?? '', 120);
$email   = clean_text($b['email'] ?? '', 160);
$phone   = clean_text($b['phone'] ?? '', 60);
$address = clean_text($b['customer_address'] ?? $b['addressOrSuburb'] ?? $b['address'] ?? '', 240);
$vehicle = clean_text($b['vehicle'] ?? $b['vehicleMakeModel'] ?? '', 160);
$date    = clean_text($b['date'] ?? $b['preferredDate'] ?? '', 40);
$time    = clean_text($b['time'] ?? $b['preferredTimeWindow'] ?? '', 80);
$package = clean_text($b['package'] ?? $b['packageSelected'] ?? '', 120);

$errors = [];
if ($name === '')    $errors['name'] = 'Full name is required.';
if ($phone === '')   $errors['phone'] = 'Phone is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'A valid email is required.';
if ($address === '') $errors['customer_address'] = 'Service address or suburb is required.';
if ($date === '')    $errors['date'] = 'Preferred date is required.';
if ($time === '')    $errors['time'] = 'Preferred time window is required.';
if ($package === '') $errors['package'] = 'Service is required.';
if ($errors) {
    booking_json_response(['success' => false, 'message' => 'Please complete all required fields.', 'errors' => $errors], 422);
}

// ── Validate price SERVER-SIDE (never trust the client) ──────────────────────
$amountCents = booking_amount_cents($package);
if ($amountCents <= 0) {
    booking_json_response([
        'success' => false,
        'message' => 'This service is quote-only and cannot be paid online. Please submit a booking request instead.',
        'code' => 'QUOTE_ONLY',
    ], 422);
}
$amountDollars = $amountCents / 100;

// ── Charge the card ──────────────────────────────────────────────────────────
$result = square_create_payment($sourceId, $amountCents, $idempotencyKey, [
    'note'  => 'Headlight restoration: ' . $package,
    'email' => $email,
]);

if (!$result['ok']) {
    savePaymentRecord([
        'status'         => 'failed',
        'errorCode'      => $result['code'] ?? 'UNKNOWN',
        'errorMessage'   => $result['error'] ?? '',
        'amount'         => $amountDollars,
        'currency'       => square_currency(),
        'package'        => $package,
        'customerEmail'  => $email,
        'idempotencyKey' => $idempotencyKey,
        'environment'    => square_environment(),
    ]);
    booking_json_response(['success' => false, 'message' => $result['error'] ?? 'Payment failed. Please try another card.'], 402);
}

// ── Payment approved → create the booking as paid ────────────────────────────
$payment    = $result['payment'];
$paymentId  = (string)($payment['id'] ?? '');
$orderId    = (string)($payment['order_id'] ?? '');
$receiptUrl = (string)($payment['receipt_url'] ?? '');
$cardDetail = $payment['card_details']['card'] ?? [];
$cardBrand  = (string)($cardDetail['card_brand'] ?? '');
$cardLast4  = (string)($cardDetail['last_4'] ?? '');

try {
    $booking = createBooking([
        'fullName'             => $name,
        'phone'                => $phone,
        'email'                => $email,
        'addressOrSuburb'      => $address,
        'formattedAddress'     => $b['formatted_address'] ?? $b['formattedAddress'] ?? '',
        'addressSuburb'        => $b['address_suburb'] ?? $b['addressSuburb'] ?? '',
        'addressPostcode'      => $b['address_postcode'] ?? $b['addressPostcode'] ?? '',
        'addressState'         => $b['address_state'] ?? $b['addressState'] ?? '',
        'addressCountry'       => $b['address_country'] ?? $b['addressCountry'] ?? '',
        'googlePlaceId'        => $b['google_place_id'] ?? $b['googlePlaceId'] ?? '',
        'addressLat'           => $b['address_lat'] ?? $b['addressLat'] ?? '',
        'addressLng'           => $b['address_lng'] ?? $b['addressLng'] ?? '',
        'vehicleMakeModel'     => $vehicle,
        'preferredDate'        => $date,
        'preferredTimeWindow'  => $time,
        'packageSelected'      => $package,
        'headlightCondition'   => $b['headlight_condition'] ?? $b['headlightCondition'] ?? '',
        'vehicleLocationType'  => $b['vehicle_location_type'] ?? $b['vehicleLocationType'] ?? '',
        'numberOfHeadlights'   => $b['number_of_headlights'] ?? $b['numberOfHeadlights'] ?? '2',
        'preferredContactMethod' => $b['preferred_contact_method'] ?? $b['preferredContactMethod'] ?? 'Phone',
        'message'              => $b['message'] ?? '',
        'source'              => 'square_' . square_environment(),
    ]);

    $booking = updateBookingPayment($booking['id'], [
        'paymentStatus'    => 'paid',
        'amount'           => $amountDollars,
        'currency'         => square_currency(),
        'squarePaymentId'  => $paymentId,
        'squareOrderId'    => $orderId,
        'squareReceiptUrl' => $receiptUrl,
        'cardBrand'        => $cardBrand,
        'cardLast4'        => $cardLast4,
    ]) ?? $booking;

    savePaymentRecord([
        'status'          => 'paid',
        'bookingId'       => $booking['id'],
        'squarePaymentId' => $paymentId,
        'squareOrderId'   => $orderId,
        'squareReceiptUrl'=> $receiptUrl,
        'amount'          => $amountDollars,
        'currency'        => square_currency(),
        'package'         => $package,
        'customerEmail'   => $email,
        'cardBrand'       => $cardBrand,
        'cardLast4'       => $cardLast4,
        'idempotencyKey'  => $idempotencyKey,
        'environment'     => square_environment(),
    ]);

    square_log('Paid booking ' . $booking['id'] . ' payment ' . $paymentId . ' amount ' . $amountDollars . ' ' . square_currency());

    if (function_exists('send_booking_email')) {
        @send_booking_email($booking);
    }

    booking_json_response([
        'success'    => true,
        'bookingId'  => $booking['id'],
        'paymentId'  => $paymentId,
        'receiptUrl' => $receiptUrl,
        'amount'     => $amountDollars,
        'currency'   => square_currency(),
        'redirect_url' => '/thank-you?booking=' . rawurlencode($booking['id']),
        'message'    => 'Payment successful. Your booking is confirmed.',
    ]);
} catch (Throwable $e) {
    // Payment succeeded but we failed to persist — log loudly so it can be reconciled.
    square_log('CRITICAL: paid (Square ' . $paymentId . ') but booking save failed: ' . $e->getMessage());
    savePaymentRecord([
        'status'          => 'paid_unsaved',
        'squarePaymentId' => $paymentId,
        'amount'          => $amountDollars,
        'currency'        => square_currency(),
        'customerEmail'   => $email,
        'error'           => $e->getMessage(),
        'environment'     => square_environment(),
    ]);
    booking_json_response([
        'success'   => true,
        'paymentId' => $paymentId,
        'receiptUrl'=> $receiptUrl,
        'message'   => 'Your payment was received. We will email your confirmation shortly.',
    ]);
}
