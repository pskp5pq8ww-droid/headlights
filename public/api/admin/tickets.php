<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_admin_api();
require_once __DIR__ . '/../../includes/tickets.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = clean_text($_GET['id'] ?? '', 80);

try {
    if ($method === 'GET') {
        if ($id !== '') {
            $ticket = getTicketById($id);
            if (!$ticket) booking_json_response(['success' => false, 'message' => 'Ticket not found'], 404);
            booking_json_response(['success' => true, 'ticket' => $ticket]);
        }
        booking_json_response(['success' => true, 'tickets' => readTickets()]);
    }

    if ($method === 'PATCH' || $method === 'POST') {
        $input = [];
        if ($method === 'PATCH') {
            $raw = file_get_contents('php://input');
            $input = json_decode((string)$raw, true) ?: [];
        } else {
            $input = $_POST;
        }
        $id = $id ?: clean_text($input['id'] ?? '', 80);
        if ($id === '') booking_json_response(['success' => false, 'message' => 'Missing ticket id'], 422);
        $ticket = updateTicket($id, $input);
        if (!$ticket) booking_json_response(['success' => false, 'message' => 'Ticket not found'], 404);
        booking_json_response(['success' => true, 'ticket' => $ticket]);
    }

    if ($method === 'DELETE') {
        if ($id === '') booking_json_response(['success' => false, 'message' => 'Missing ticket id'], 422);
        $ticket = deleteTicket($id);
        if (!$ticket) booking_json_response(['success' => false, 'message' => 'Ticket not found'], 404);
        booking_json_response(['success' => true, 'ticket' => $ticket]);
    }

    booking_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    booking_log_error('Admin tickets API failed: ' . $e->getMessage());
    booking_json_response(['success' => false, 'message' => 'Unable to load tickets. Please check server storage or permissions.'], 500);
}
