<?php
// api/profile.php
// GET    /api/profile           — get current user profile
// PUT    /api/profile           — update name/phone/avatar
// PUT    /api/profile/password  — change password
// DELETE /api/profile           — deactivate or delete account

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$sub    = trim($_SERVER['PATH_INFO'] ?? '', '/');
$user   = requireAuth();

if ($method === 'GET') {
    $profile = DB::run(
        'SELECT id, name, email, phone, avatar, role, status, created_at FROM users WHERE id = ?',
        [$user['id']]
    )->fetch();

    if ($user['role'] === 'cook') {
        $profile['cook'] = DB::run(
            'SELECT bio, specialty, verification, rating_avg, rating_count, total_earnings
             FROM cook_profiles WHERE user_id = ?',
            [$user['id']]
        )->fetch();
    }

    respond($profile);
}

if ($method === 'PUT' && $sub !== 'password') {
    $data   = body();
    $avatar = null;

    if (!empty($_FILES['avatar']['tmp_name'])) {
        $_FILES['image'] = $_FILES['avatar'];
        $avatar = uploadImage('image', UPLOAD_AVA_DIR);
    }

    DB::run(
        'UPDATE users SET name = COALESCE(?, name), phone = COALESCE(?, phone),
                          avatar = COALESCE(?, avatar)
         WHERE id = ?',
        [$data['name'] ?? null, $data['phone'] ?? null, $avatar, $user['id']]
    );

    // Refresh session name
    startSession();
    if (isset($data['name'])) $_SESSION['user']['name'] = $data['name'];

    respond(['message' => 'Profile updated']);
}

if ($method === 'PUT' && $sub === 'password') {
    $data = validate(['current_password' => 'required', 'new_password' => 'required|min:8']);

    $row = DB::run('SELECT password_hash FROM users WHERE id = ?', [$user['id']])->fetch();
    if (!verifyPassword($data['current_password'], $row['password_hash'])) {
        error('Current password is incorrect', 403);
    }

    DB::run(
        'UPDATE users SET password_hash = ? WHERE id = ?',
        [hashPassword($data['new_password']), $user['id']]
    );

    respond(['message' => 'Password changed. Please log in again.']);
}

if ($method === 'DELETE') {
    $action = input('action', 'deactivate'); // 'deactivate' | 'delete'

    if ($action === 'deactivate') {
        DB::run("UPDATE users SET status = 'inactive' WHERE id = ?", [$user['id']]);
        // Destroy session
        startSession(); session_unset(); session_destroy();
        respond(['message' => 'Account deactivated']);
    }

    if ($action === 'delete') {
        DB::run('DELETE FROM users WHERE id = ?', [$user['id']]);
        startSession(); session_unset(); session_destroy();
        respond(['message' => 'Account permanently deleted']);
    }

    error('action must be "deactivate" or "delete"');
}
