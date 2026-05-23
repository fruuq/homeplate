<?php
// api/messages.php
// GET  /api/messages?with={userId}  — conversation thread
// POST /api/messages                — send message
// GET  /api/messages/conversations  — inbox (list of unique conversations)

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$sub    = trim($_SERVER['PATH_INFO'] ?? '', '/');

if ($method === 'GET' && $sub === 'conversations') {
    $user = requireAuth();
    $convos = DB::run(
        "SELECT
            IF(m.sender_id = ?, m.receiver_id, m.sender_id) AS partner_id,
            u.name AS partner_name, u.avatar AS partner_avatar,
            MAX(m.created_at) AS last_at,
            SUM(m.is_read = 0 AND m.receiver_id = ?) AS unread_count,
            SUBSTRING(m.body, 1, 80) AS last_message
         FROM messages m
         JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
         WHERE m.sender_id = ? OR m.receiver_id = ?
         GROUP BY partner_id
         ORDER BY last_at DESC",
        [$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]
    )->fetchAll();

    respond(['data' => $convos]);
}

if ($method === 'GET') {
    $user       = requireAuth();
    $partnerId  = (int)($_GET['with'] ?? 0);
    if (!$partnerId) error('"with" param required');

    $page = paginate((int)($_GET['page'] ?? 1), 50);

    $thread = DB::run(
        "SELECT m.id, m.sender_id, m.body, m.is_read, m.created_at
         FROM messages m
         WHERE (m.sender_id = ? AND m.receiver_id = ?)
            OR (m.sender_id = ? AND m.receiver_id = ?)
         ORDER BY m.created_at DESC
         LIMIT ? OFFSET ?",
        [$user['id'], $partnerId, $partnerId, $user['id'], $page['limit'], $page['offset']]
    )->fetchAll();

    // Mark received messages as read
    DB::run(
        "UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0",
        [$user['id'], $partnerId]
    );

    respond(['data' => array_reverse($thread)]);
}

if ($method === 'POST') {
    $user = requireAuth();
    $data = validate(['receiver_id' => 'required|numeric', 'body' => 'required|min:1']);

    $receiverId = (int)$data['receiver_id'];
    if ($receiverId === $user['id']) error('Cannot message yourself');

    $receiver = DB::run('SELECT id, name FROM users WHERE id = ? AND status = "active"', [$receiverId])->fetch();
    if (!$receiver) error('Recipient not found', 404);

    DB::run(
        'INSERT INTO messages (sender_id, receiver_id, body) VALUES (?, ?, ?)',
        [$user['id'], $receiverId, $data['body']]
    );

    notify($receiverId, 'new_message', "New message from {$user['name']}", substr($data['body'], 0, 100), "/messages?with={$user['id']}");

    respond(['message' => 'Sent'], 201);
}
