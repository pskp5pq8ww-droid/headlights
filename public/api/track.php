<?php
declare(strict_types=1);

/**
 * POST /api/track  — receives a fire-and-forget analytics beacon.
 * Returns 204 No Content as fast as possible.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/analytics.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode((string)$raw, true);
if (!is_array($payload)) $payload = $_POST;

try {
    analytics_record($payload);
} catch (Throwable $e) {
    // Never surface tracking errors to the visitor.
    error_log('[track] ' . $e->getMessage());
}

http_response_code(204);
