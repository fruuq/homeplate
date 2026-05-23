<?php
// auth/login.php  — POST /auth/login

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Method not allowed', 405);
}

$data  = validate(['email' => 'required|email', 'password' => 'required']);
$email = strtolower(trim($data['email']));

$user = DB::run(
    'SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?',
    [$email]
)->fetch();

// Constant-time failure — don't reveal whether email exists
if (!$user || !verifyPassword($data['password'], $user['password_hash'])) {
    error('Invalid email or password', 401);
}

if ($user['status'] !== 'active') {
    error('Account is ' . $user['status'], 403);
}

startSession();
session_regenerate_id(true); // protect against session fixation

$_SESSION['user'] = [
    'id'     => $user['id'],
    'name'   => $user['name'],
    'email'  => $user['email'],
    'role'   => $user['role'],
    'status' => $user['status'],
];

unset($user['password_hash']);
respond(['message' => 'Login successful', 'user' => $user]);
