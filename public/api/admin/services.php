<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/services.php';

require_admin_api();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$prefix = '/api/admin/services';
$id = clean_text($_GET['id'] ?? '', 80);
if ($id === '' && str_starts_with($path, $prefix . '/')) {
    $id = clean_text(substr($path, strlen($prefix) + 1), 80);
}

try {
    if ($method === 'GET') {
        booking_json_response(['success' => true, 'services' => read_services(false)]);
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode((string)$raw, true);
    if (!is_array($payload)) $payload = $_POST;

    $services = read_services(false);

    if ($method === 'POST') {
        $service = validate_service_payload($payload);
        foreach ($services as $existing) {
            if ($existing['slug'] === $service['slug']) {
                throw new InvalidArgumentException('Slug must be unique.');
            }
        }
        $services[] = $service;
        write_services($services);
        booking_json_response(['success' => true, 'service' => $service, 'message' => 'Service created.']);
    }

    if (in_array($method, ['PUT', 'PATCH'], true)) {
        if ($id === '') throw new InvalidArgumentException('Missing service id.');
        $found = false;
        foreach ($services as &$existing) {
            if ($existing['id'] !== $id) continue;
            $candidate = validate_service_payload($payload, $existing);
            foreach ($services as $other) {
                if ($other['id'] !== $id && $other['slug'] === $candidate['slug']) {
                    throw new InvalidArgumentException('Slug must be unique.');
                }
            }
            $existing = $candidate;
            $found = true;
            break;
        }
        unset($existing);
        if (!$found) booking_json_response(['success' => false, 'message' => 'Service not found.'], 404);
        write_services($services);
        booking_json_response(['success' => true, 'message' => 'Service updated.']);
    }

    if ($method === 'DELETE') {
        if ($id === '') throw new InvalidArgumentException('Missing service id.');
        $found = false;
        foreach ($services as &$service) {
            if ($service['id'] !== $id) continue;
            $service['isActive'] = false;
            $service['updatedAt'] = booking_now();
            $found = true;
            break;
        }
        unset($service);
        if (!$found) booking_json_response(['success' => false, 'message' => 'Service not found.'], 404);
        write_services($services);
        booking_json_response(['success' => true, 'message' => 'Service deactivated.']);
    }

    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
} catch (InvalidArgumentException $e) {
    booking_json_response(['success' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    booking_log_error('Admin services API failed: ' . $e->getMessage());
    booking_json_response(['success' => false, 'message' => 'Unable to save service.'], 500);
}

