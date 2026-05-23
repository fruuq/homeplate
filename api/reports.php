<?php
// api/reports.php
// POST /api/reports         — user submits abuse report
// GET  /api/reports         — admin: list reports
// PATCH /api/reports/{id}   — admin: resolve/dismiss

require_once __DIR__ . '/../includes/helpers.php';

$method   = $_SERVER['REQUEST_METHOD'];
$parts    = array_filter(explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/')));
$reportId = (int)($parts[1] ?? 0);

if ($method === 'POST') {
    $user = requireAuth();
    $data = validate([
        'target_type' => 'required',
        'target_id'   => 'required|numeric',
        'reason'      => 'required|min:5',
    ]);

    if (!in_array($data['target_type'], ['meal','user','review'], true)) {
        error('target_type must be meal, user, or review');
    }

    DB::run(
        'INSERT INTO reports (reporter_id, target_type, target_id, reason, details) VALUES (?, ?, ?, ?, ?)',
        [$user['id'], $data['target_type'], $data['target_id'], $data['reason'], $data['details'] ?? null]
    );

    respond(['message' => 'Report submitted. Our team will review it.'], 201);
}

if ($method === 'GET') {
    requireRole('admin');
    $status = $_GET['status'] ?? 'open';
    $page   = paginate((int)($_GET['page'] ?? 1));

    $reports = DB::run(
        "SELECT r.*, u.name AS reporter_name
         FROM reports r JOIN users u ON u.id = r.reporter_id
         WHERE r.status = ?
         ORDER BY r.created_at DESC LIMIT ? OFFSET ?",
        [$status, $page['limit'], $page['offset']]
    )->fetchAll();

    respond(['data' => $reports]);
}

if ($method === 'PATCH' && $reportId) {
    $admin  = requireRole('admin');
    $action = input('action'); // 'resolved' | 'dismissed'

    if (!in_array($action, ['resolved','dismissed'], true)) error('Invalid action');

    DB::run(
        'UPDATE reports SET status = ?, resolved_by = ? WHERE id = ?',
        [$action, $admin['id'], $reportId]
    );

    DB::run(
        'INSERT INTO admin_logs (admin_id, action, target) VALUES (?, ?, ?)',
        [$admin['id'], "report_$action", "report:$reportId"]
    );

    respond(['message' => "Report $action"]);
}
