<?php
// api/admin.php
// GET   /api/admin/dashboard  — platform analytics
// GET   /api/admin/users      — list all users with filters
// PATCH /api/admin/users/{id} — ban/unban/activate

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$parts  = array_filter(explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/')));
$sub    = $parts[1] ?? null;      // 'dashboard' | 'users'
$userId = (int)($parts[2] ?? 0);

requireRole('admin');

// ─── GET /api/admin/dashboard ────────────────────────────────
if ($method === 'GET' && $sub === 'dashboard') {
    $users = DB::run(
        "SELECT COUNT(*) AS total,
                SUM(role = 'customer') AS customers,
                SUM(role = 'cook')     AS cooks,
                SUM(status = 'banned') AS banned
         FROM users"
    )->fetch();

    $orders = DB::run(
        "SELECT COUNT(*) AS total,
                SUM(status = 'delivered')  AS delivered,
                SUM(status = 'cancelled')  AS cancelled,
                SUM(total)                 AS revenue
         FROM orders"
    )->fetch();

    $pending_cooks = (int) DB::run(
        "SELECT COUNT(*) FROM cook_profiles WHERE verification = 'pending'"
    )->fetchColumn();

    $open_reports = (int) DB::run(
        "SELECT COUNT(*) FROM reports WHERE status = 'open'"
    )->fetchColumn();

    $recent_orders = DB::run(
        "SELECT o.id, o.status, o.total, o.created_at, u.name AS customer
         FROM orders o JOIN users u ON u.id = o.customer_id
         ORDER BY o.created_at DESC LIMIT 10"
    )->fetchAll();

    respond([
        'users'          => $users,
        'orders'         => $orders,
        'pending_cooks'  => $pending_cooks,
        'open_reports'   => $open_reports,
        'recent_orders'  => $recent_orders,
    ]);
}

// ─── GET /api/admin/users ────────────────────────────────────
if ($method === 'GET' && $sub === 'users') {
    $page   = paginate((int)($_GET['page'] ?? 1));
    $role   = $_GET['role']   ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['q']      ?? null;

    $where  = ['1=1'];
    $params = [];

    if ($role)   { $where[] = 'role = ?';              $params[] = $role; }
    if ($status) { $where[] = 'status = ?';            $params[] = $status; }
    if ($search) { $where[] = 'name LIKE ? OR email LIKE ?'; $params[] = "%$search%"; $params[] = "%$search%"; }

    $whereSQL = implode(' AND ', $where);

    $total = (int) DB::run("SELECT COUNT(*) FROM users WHERE $whereSQL", $params)->fetchColumn();
    $params[] = $page['limit'];
    $params[] = $page['offset'];

    $users = DB::run(
        "SELECT id, name, email, role, status, created_at
         FROM users WHERE $whereSQL ORDER BY created_at DESC LIMIT ? OFFSET ?",
        $params
    )->fetchAll();

    respond(['data' => $users, 'total' => $total]);
}

// ─── PATCH /api/admin/users/{id} ─────────────────────────────
if ($method === 'PATCH' && $sub === 'users' && $userId) {
    $admin  = currentUser();
    $status = input('status'); // 'active' | 'inactive' | 'banned'

    if (!in_array($status, ['active','inactive','banned'], true)) {
        error('status must be active, inactive, or banned');
    }

    DB::run('UPDATE users SET status = ? WHERE id = ?', [$status, $userId]);

    DB::run(
        'INSERT INTO admin_logs (admin_id, action, target, details) VALUES (?, ?, ?, ?)',
        [$admin['id'], "set_status_$status", "user:$userId", '']
    );

    respond(['message' => "User status set to $status"]);
}

error('Not found', 404);
