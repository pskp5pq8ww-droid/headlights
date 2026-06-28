<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/services.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

booking_json_response([
    'success' => true,
    'services' => read_services(true),
    'vehicleSizes' => VEHICLE_SIZE_LABELS,
    'pricingNote' => 'All prices are in AUD and listed as from prices. Final pricing depends on vehicle size, condition, access, level of oxidation, dirt, stains, pet hair, sand and the amount of work required. Any extra cost will be confirmed before work begins.',
]);

