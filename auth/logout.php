<?php
// auth/logout.php  — POST /auth/logout

require_once __DIR__ . '/../includes/helpers.php';

requireAuth();
startSession();
session_unset();
session_destroy();
respond(['message' => 'Logged out']);
