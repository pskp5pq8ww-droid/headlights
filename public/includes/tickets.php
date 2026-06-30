<?php
declare(strict_types=1);

require_once __DIR__ . '/bookings.php';

const TICKET_STATUSES = ['new', 'in_review', 'resolved', 'closed'];
const TICKET_CATEGORIES = ['question', 'complaint', 'claim', 'booking_help', 'other'];

function ticket_dir(): string {
    return booking_storage_base() . '/tickets';
}

function ensureTicketStorageExists(): void {
    ensureBookingStorageExists();
    $dir = ticket_dir();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create ticket storage directory.');
    }
}

function ticket_id(): string {
    try {
        return 'tk_' . date('YmdHis') . '_' . bin2hex(random_bytes(5));
    } catch (Throwable) {
        return 'tk_' . date('YmdHis') . '_' . substr(str_replace('.', '', uniqid('', true)), -8);
    }
}

function ticket_file_path(string $id): string {
    $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
    return ticket_dir() . '/ticket_' . $safe . '.json';
}

function normalize_ticket_status(string $status): string {
    $status = strtolower(trim($status));
    return in_array($status, TICKET_STATUSES, true) ? $status : 'new';
}

function normalize_ticket_category(string $category): string {
    $category = strtolower(trim($category));
    return in_array($category, TICKET_CATEGORIES, true) ? $category : 'other';
}

function normalize_ticket(array $ticket): array {
    $created = $ticket['createdAt'] ?? $ticket['created_at'] ?? booking_now();
    return [
        'id' => (string)($ticket['id'] ?? ticket_id()),
        'createdAt' => (string)$created,
        'updatedAt' => (string)($ticket['updatedAt'] ?? $ticket['updated_at'] ?? $created),
        'status' => normalize_ticket_status((string)($ticket['status'] ?? 'new')),
        'category' => normalize_ticket_category((string)($ticket['category'] ?? 'other')),
        'priority' => clean_text($ticket['priority'] ?? 'normal', 30),
        'name' => clean_text($ticket['name'] ?? $ticket['fullName'] ?? '', 120),
        'email' => clean_text($ticket['email'] ?? '', 160),
        'phone' => clean_text($ticket['phone'] ?? '', 60),
        'subject' => clean_text($ticket['subject'] ?? '', 160),
        'message' => clean_text($ticket['message'] ?? '', 3000),
        'page' => clean_text($ticket['page'] ?? '', 240),
        'source' => clean_text($ticket['source'] ?? 'website_ticket', 80),
        'adminNotes' => clean_text($ticket['adminNotes'] ?? $ticket['admin_notes'] ?? '', 2500),
        'lastResponse' => clean_text($ticket['lastResponse'] ?? $ticket['last_response'] ?? '', 2500),
        'lastResponseAt' => clean_text($ticket['lastResponseAt'] ?? $ticket['last_response_at'] ?? '', 80),
        'history' => is_array($ticket['history'] ?? null) ? $ticket['history'] : [],
        'deleted' => (bool)($ticket['deleted'] ?? false),
    ];
}

function writeTicket(array $ticket): array {
    ensureTicketStorageExists();
    $ticket = normalize_ticket($ticket);
    $path = ticket_file_path($ticket['id']);
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($ticket, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new RuntimeException('Unable to encode ticket JSON.');
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Unable to write ticket temp file.');
    @chmod($tmp, 0600);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to publish ticket file.');
    }
    @chmod($path, 0600);
    return $ticket;
}

function readTickets(bool $includeDeleted = false): array {
    ensureTicketStorageExists();
    $tickets = [];
    foreach (glob(ticket_dir() . '/ticket_*.json') ?: [] as $file) {
        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            @rename($file, $file . '.broken-' . date('YmdHis'));
            booking_log_error('Invalid ticket JSON backed up: ' . basename($file));
            continue;
        }
        $ticket = normalize_ticket($data);
        if (!$includeDeleted && !empty($ticket['deleted'])) continue;
        $ticket['_file'] = basename($file);
        $tickets[] = $ticket;
    }
    usort($tickets, fn($a, $b) => strcmp((string)$b['createdAt'], (string)$a['createdAt']));
    return $tickets;
}

function getTicketById(string $id): ?array {
    $path = ticket_file_path($id);
    if (!is_file($path)) return null;
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) ? normalize_ticket($data) : null;
}

function createTicket(array $data): array {
    $now = booking_now();
    $message = clean_text($data['message'] ?? '', 3000);
    $email = clean_text($data['email'] ?? '', 160);
    $phone = clean_text($data['phone'] ?? '', 60);
    if ($message === '') throw new InvalidArgumentException('Please write your message.');
    if ($email === '' && $phone === '') throw new InvalidArgumentException('Please leave an email or phone number.');

    $ticket = normalize_ticket([
        'id' => ticket_id(),
        'createdAt' => $now,
        'updatedAt' => $now,
        'status' => 'new',
        'category' => $data['category'] ?? 'other',
        'priority' => 'normal',
        'name' => $data['name'] ?? '',
        'email' => $email,
        'phone' => $phone,
        'subject' => $data['subject'] ?? '',
        'message' => $message,
        'page' => $data['page'] ?? '',
        'source' => $data['source'] ?? 'website_ticket',
        'history' => [[
            'at' => $now,
            'type' => 'created',
            'message' => 'Ticket received from website.',
        ]],
    ]);

    send_ticket_email($ticket);
    return writeTicket($ticket);
}

function updateTicket(string $id, array $updates): ?array {
    $ticket = getTicketById($id);
    if (!$ticket) return null;
    $oldStatus = $ticket['status'];
    foreach (['status', 'category', 'priority', 'name', 'email', 'phone', 'subject', 'message', 'adminNotes', 'lastResponse'] as $key) {
        if (!array_key_exists($key, $updates)) continue;
        if ($key === 'status') {
            $ticket[$key] = normalize_ticket_status((string)$updates[$key]);
        } elseif ($key === 'category') {
            $ticket[$key] = normalize_ticket_category((string)$updates[$key]);
        } else {
            $ticket[$key] = clean_text($updates[$key], in_array($key, ['message', 'adminNotes', 'lastResponse'], true) ? 3000 : 500);
        }
    }
    $ticket['updatedAt'] = booking_now();
    if (array_key_exists('lastResponse', $updates) && trim((string)$ticket['lastResponse']) !== '') {
        $ticket['lastResponseAt'] = $ticket['updatedAt'];
    }
    if ($oldStatus !== $ticket['status']) {
        $ticket['history'][] = [
            'at' => $ticket['updatedAt'],
            'type' => 'status',
            'message' => 'Status changed from ' . $oldStatus . ' to ' . $ticket['status'] . '.',
        ];
    }
    return writeTicket($ticket);
}

function deleteTicket(string $id): ?array {
    $ticket = getTicketById($id);
    if (!$ticket) return null;
    $ticket['deleted'] = true;
    $ticket['status'] = 'closed';
    $ticket['updatedAt'] = booking_now();
    $ticket['history'][] = [
        'at' => $ticket['updatedAt'],
        'type' => 'deleted',
        'message' => 'Ticket hidden from dashboard.',
    ];
    return writeTicket($ticket);
}

function ticket_status_label(string $status): string {
    return ucwords(str_replace('_', ' ', normalize_ticket_status($status)));
}

function ticket_category_label(string $category): string {
    return ucwords(str_replace('_', ' ', normalize_ticket_category($category)));
}

function getTicketStats(array $tickets = null): array {
    $tickets ??= readTickets();
    $stats = [
        'total' => count($tickets),
        'new' => 0,
        'in_review' => 0,
        'resolved' => 0,
        'closed' => 0,
        'open' => 0,
    ];
    foreach ($tickets as $ticket) {
        $status = normalize_ticket_status((string)($ticket['status'] ?? 'new'));
        $stats[$status]++;
        if (in_array($status, ['new', 'in_review'], true)) $stats['open']++;
    }
    return $stats;
}

function send_ticket_email(array $ticket): void {
    $adminEmail = getenv('ADMIN_EMAIL') ?: ($GLOBALS['SITE']['email'] ?? 'hello@shiningaus.com');
    $subject = 'New website ticket - ' . (($ticket['subject'] ?? '') ?: ticket_category_label((string)($ticket['category'] ?? 'other')));
    $body  = "Ticket from shiningaus.com\n\n";
    $body .= "Name: " . ($ticket['name'] ?? '') . "\n";
    $body .= "Phone: " . ($ticket['phone'] ?? '') . "\n";
    $body .= "Email: " . ($ticket['email'] ?? '') . "\n";
    $body .= "Category: " . ticket_category_label((string)($ticket['category'] ?? 'other')) . "\n";
    $body .= "Subject: " . ($ticket['subject'] ?? '') . "\n";
    $body .= "Page: " . ($ticket['page'] ?? '') . "\n";
    $body .= "Message: " . ($ticket['message'] ?? '') . "\n";

    $headers  = "From: " . ($GLOBALS['SITE']['email'] ?? 'hello@shiningaus.com') . "\r\n";
    if (!empty($ticket['email'])) $headers .= "Reply-To: " . $ticket['email'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!@mail($adminEmail, $subject, $body, $headers)) {
        booking_log_error('mail() failed for ticket ' . ($ticket['id'] ?? 'unknown'));
    }
}
