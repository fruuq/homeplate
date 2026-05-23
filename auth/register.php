<?php
// auth/register.php  — POST /auth/register

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Method not allowed', 405);
}

$data = validate([
    'name'     => 'required|min:2|max:120',
    'email'    => 'required|email',
    'password' => 'required|min:8|max:128',
    'phone'    => 'max:20',
]);

$email = strtolower(trim($data['email']));

// Duplicate check
$exists = DB::run('SELECT id FROM users WHERE email = ?', [$email])->fetch();
if ($exists) {
    error('Email already registered', 409);
}

$hash = hashPassword($data['password']);

DB::run(
    'INSERT INTO users (name, email, password_hash, phone) VALUES (?, ?, ?, ?)',
    [$data['name'], $email, $hash, $data['phone'] ?? null]
);

$userId = (int) DB::lastInsertId();

// Auto-start session
startSession();
$_SESSION['user'] = [
    'id'     => $userId,
    'name'   => $data['name'],
    'email'  => $email,
    'role'   => 'customer',
    'status' => 'active',
];

respond([
    'message' => 'Registration successful',
    'user'    => ['id' => $userId, 'name' => $data['name'], 'email' => $email, 'role' => 'customer'],
], 201);
