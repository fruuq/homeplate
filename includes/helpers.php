<?php
// includes/helpers.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// ─── HTTP Response ────────────────────────────────────────────

function respond(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function error(string $message, int $status = 400): never
{
    respond(['error' => $message], $status);
}

// ─── Session / Auth ───────────────────────────────────────────

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => (APP_ENV === 'production'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function currentUser(): ?array
{
    startSession();
    return $_SESSION['user'] ?? null;
}

function requireAuth(): array
{
    $user = currentUser();
    if (!$user) {
        error('Unauthenticated', 401);
    }
    if ($user['status'] !== 'active') {
        error('Account is not active', 403);
    }
    return $user;
}

function requireRole(string ...$roles): array
{
    $user = requireAuth();
    if (!in_array($user['role'], $roles, true)) {
        error('Forbidden', 403);
    }
    return $user;
}

// ─── Input / Validation ───────────────────────────────────────

function body(): array
{
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function input(string $key, mixed $default = null): mixed
{
    $data = body();
    return $data[$key] ?? $_POST[$key] ?? $_GET[$key] ?? $default;
}

function validate(array $rules): array
{
    $data   = body() + $_POST;
    $errors = [];

    foreach ($rules as $field => $rule) {
        $value    = $data[$field] ?? null;
        $parts    = explode('|', $rule);

        foreach ($parts as $part) {
            [$type, $arg] = array_pad(explode(':', $part, 2), 2, null);

            match ($type) {
                'required' => ($value === null || $value === '') && ($errors[$field][] = "$field is required"),
                'email'    => ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) && ($errors[$field][] = "Invalid email"),
                'min'      => ($value !== null && strlen($value) < (int)$arg) && ($errors[$field][] = "$field must be at least $arg characters"),
                'max'      => ($value !== null && strlen($value) > (int)$arg) && ($errors[$field][] = "$field must not exceed $arg characters"),
                'numeric'  => ($value !== null && !is_numeric($value)) && ($errors[$field][] = "$field must be numeric"),
                'positive' => ($value !== null && (float)$value <= 0) && ($errors[$field][] = "$field must be positive"),
                default    => null,
            };
        }
    }

    if ($errors) {
        respond(['errors' => $errors], 422);
    }

    return $data;
}

// ─── Notifications ────────────────────────────────────────────

function notify(int $userId, string $type, string $title, string $body = '', string $link = ''): void
{
    DB::run(
        'INSERT INTO notifications (user_id, type, title, body, link)
         VALUES (?, ?, ?, ?, ?)',
        [$userId, $type, $title, $body, $link]
    );
}

// ─── File Upload ──────────────────────────────────────────────

function uploadImage(string $fieldName, string $destDir): string
{
    if (empty($_FILES[$fieldName]['tmp_name'])) {
        error("No file uploaded for $fieldName");
    }

    $file    = $_FILES[$fieldName];
    $maxSize = UPLOAD_MAX_MB * 1024 * 1024;

    if ($file['size'] > $maxSize) {
        error("File too large (max " . UPLOAD_MAX_MB . " MB)");
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMG_TYPES, true)) {
        error("Invalid file type. Allowed: jpeg, png, webp");
    }

    $ext      = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $filename = bin2hex(random_bytes(16)) . ".$ext";
    $dest     = rtrim($destDir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        error('Failed to save uploaded file', 500);
    }

    return $filename;
}

// ─── Misc ─────────────────────────────────────────────────────

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function paginate(int $page = 1, int $perPage = 20): array
{
    $page    = max(1, (int)($page));
    $offset  = ($page - 1) * $perPage;
    return ['limit' => $perPage, 'offset' => $offset, 'page' => $page];
}
