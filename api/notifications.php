<?php
// api/notifications.php
// GET   /api/notifications         — list user notifications
// PATCH /api/notifications/read    — mark all as read

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user = requireAuth();
    $page = paginate((int)($_GET['page'] ?? 1));

    $notes = DB::run(
        'SELECT id, type, title, body, link, is_read, created_at
         FROM notifications WHERE user_id = ?
         ORDER BY created_at DESC LIMIT ? OFFSET ?',
        [$user['id'], $page['limit'], $page['offset']]
    )->fetchAll();

    $unread = (int) DB::run(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
        [$user['id']]
    )->fetchColumn();

    respond(['data' => $notes, 'unread' => $unread]);
}

if ($method === 'PATCH') {
    $user = requireAuth();
    DB::run('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$user['id']]);
    respond(['message' => 'Marked all as read']);
}
