<?php
/**
 * DEPRECATED — simulated payment gateway removed.
 *
 * Real payments are now processed by Square via:
 *   POST /api/payments/square   (see public/api/payments/square.php)
 * driven by the booking wizard payment step on /book.
 *
 * This stub remains only so any old bookmark/link returns a clear message
 * instead of charging nothing. Safe to delete once no longer referenced.
 */
declare(strict_types=1);

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'message' => 'This payment endpoint has been retired. Please book and pay at /book.',
    'redirect_url' => '/book',
]);
