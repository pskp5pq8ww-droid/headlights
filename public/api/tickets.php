<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/tickets.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $input = $_POST;
    if (!$input) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) $input = $decoded;
    }
    $input['source'] = $input['source'] ?? 'support_widget';
    $ticket = createTicket($input);
    booking_json_response([
        'success' => true,
        'ticket' => [
            'id' => $ticket['id'],
            'status' => $ticket['status'],
            'createdAt' => $ticket['createdAt'],
        ],
    ]);
} catch (InvalidArgumentException $e) {
    booking_json_response(['success' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    booking_log_error('Public tickets API failed: ' . $e->getMessage());
    booking_json_response(['success' => false, 'message' => 'Unable to send your message right now. Please call or email us.'], 500);
}
