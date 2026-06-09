<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';

$apiKey = maps_api_key();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode([
    'success' => true,
    'assetVersion' => ASSET_VER,
    'googleMapsConfigured' => $apiKey !== '',
    'bookingScriptShouldLoad' => $apiKey !== '',
], JSON_UNESCAPED_SLASHES);
