<?php
declare(strict_types=1);

/**
 * Public Square configuration for the browser.
 * Returns ONLY the application id + location id (never the access token).
 */
require_once __DIR__ . '/../includes/square.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['success' => true, 'config' => square_public_config()], JSON_UNESCAPED_SLASHES);
