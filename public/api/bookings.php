<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bookings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$_POST['source'] = $_POST['source'] ?? 'public_api';
require __DIR__ . '/../form.php';
