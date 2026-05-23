<?php
// api/cooks.php
// POST  /api/cooks/apply          — customer applies to become a cook
// GET   /api/cooks                — list approved cooks (public)
// GET   /api/cooks/{id}           — cook profile (public)
// GET   /api/cooks/dashboard      — cook's own stats
// PATCH /api/cooks/{id}/verify    — admin: approve/reject

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$parts  = array_filter(explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/')));
$cookId = is_numeric($parts[1] ?? '') ? (int)$parts[1] : null;
$sub    = $cookId ? ($parts[2] ?? null) : ($parts[1] ?? null);

// ─── POST /api/cooks/apply ───────────────────────────────────
if ($method === 'POST' && $sub === 'apply') {
    $user = requireRole('customer');

    $existing = DB::run('SELECT id FROM cook_profiles WHERE user_id = ?', [$user['id']])->fetch();
    if ($existing) error('You have already submitted a cook application', 409);

    $data = validate(['bio' => 'required|min:20']);

    $idDoc = null;
    if (!empty($_FILES['id_document']['tmp_name'])) {
        // Reuse uploadImage but target ID directory
        $_FILES['image'] = $_FILES['id_document'];
        $idDoc = uploadImage('image', UPLOAD_ID_DIR);
    }

    DB::run(
        'INSERT INTO cook_profiles (user_id, bio, specialty, id_document) VALUES (?, ?, ?, ?)',
        [$user['id'], $data['bio'], $data['specialty'] ?? null, $idDoc]
    );

    respond(['message' => 'Application submitted. An admin will review it shortly.'], 201);
}

// ─── GET /api/cooks/dashboard ────────────────────────────────
if ($method === 'GET' && $sub === 'dashboard') {
    $cook = requireRole('cook');

    $stats = DB::run(
        "SELECT
            COUNT(*)                                        AS total_meals,
            SUM(is_available AND NOT is_deleted)            AS available_meals
         FROM meals WHERE cook_id = ? AND is_deleted = 0",
        [$cook['id']]
    )->fetch();

    $orderStats = DB::run(
        "SELECT status, COUNT(*) AS cnt, SUM(total) AS revenue
         FROM orders WHERE cook_id = ? GROUP BY status",
        [$cook['id']]
    )->fetchAll();

    $profile = DB::run(
        'SELECT bio, specialty, verification, rating_avg, rating_count, total_earnings
         FROM cook_profiles WHERE user_id = ?',
        [$cook['id']]
    )->fetch();

    respond([
        'profile'      => $profile,
        'meal_stats'   => $stats,
        'order_stats'  => $orderStats,
    ]);
}

// ─── GET /api/cooks ──────────────────────────────────────────
if ($method === 'GET' && !$cookId && $sub !== 'dashboard') {
    $page = paginate((int)($_GET['page'] ?? 1));

    $cooks = DB::run(
        "SELECT u.id, u.name, u.avatar, cp.bio, cp.specialty, cp.rating_avg, cp.rating_count
         FROM users u
         JOIN cook_profiles cp ON cp.user_id = u.id
         WHERE u.role = 'cook' AND u.status = 'active' AND cp.verification = 'approved'
         ORDER BY cp.rating_avg DESC
         LIMIT ? OFFSET ?",
        [$page['limit'], $page['offset']]
    )->fetchAll();

    respond(['data' => $cooks]);
}

// ─── GET /api/cooks/{id} ─────────────────────────────────────
if ($method === 'GET' && $cookId) {
    $cook = DB::run(
        "SELECT u.id, u.name, u.avatar, cp.bio, cp.specialty, cp.rating_avg, cp.rating_count
         FROM users u
         JOIN cook_profiles cp ON cp.user_id = u.id
         WHERE u.id = ? AND cp.verification = 'approved'",
        [$cookId]
    )->fetch();

    if (!$cook) error('Cook not found', 404);

    $cook['meals'] = DB::run(
        "SELECT id, title, price, image, rating_avg
         FROM meals WHERE cook_id = ? AND is_available = 1 AND is_deleted = 0
         ORDER BY rating_avg DESC LIMIT 10",
        [$cookId]
    )->fetchAll();

    respond($cook);
}

// ─── PATCH /api/cooks/{id}/verify ────────────────────────────
if ($method === 'PATCH' && $cookId && $sub === 'verify') {
    $admin = requireRole('admin');

    $profile = DB::run(
        'SELECT user_id FROM cook_profiles WHERE user_id = ?',
        [$cookId]
    )->fetch();

    if (!$profile) error('Cook application not found', 404);

    $decision = input('decision'); // 'approved' or 'rejected'
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        error('decision must be "approved" or "rejected"');
    }

    DB::run(
        "UPDATE cook_profiles SET verification = ?, verified_at = NOW(), verified_by = ?
         WHERE user_id = ?",
        [$decision, $admin['id'], $cookId]
    );

    if ($decision === 'approved') {
        // Promote user role
        DB::run("UPDATE users SET role = 'cook' WHERE id = ?", [$cookId]);
        // Refresh session role if admin is also the cook (edge case, skip)
    }

    // Log action
    DB::run(
        'INSERT INTO admin_logs (admin_id, action, target, details) VALUES (?, ?, ?, ?)',
        [$admin['id'], "cook_$decision", "user:$cookId", input('note', '')]
    );

    notify(
        $cookId,
        'cook_verified',
        $decision === 'approved' ? 'Your cook application was approved!' : 'Cook application rejected',
        input('note', ''),
        '/cook/dashboard'
    );

    respond(['message' => "Cook $decision"]);
}

error('Not found', 404);
