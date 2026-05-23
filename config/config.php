<?php
// config/config.php

define('APP_NAME', 'Homeplate');
define('APP_URL',  $_ENV['APP_URL'] ?? 'https://homeplatesw.page.gd');
define('APP_ENV',  'production');

// JWT / session
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);  // 7 days in seconds

// File uploads
define('UPLOAD_MAX_MB',    5);
define('UPLOAD_MEAL_DIR',  __DIR__ . '/../uploads/meals/');
define('UPLOAD_AVA_DIR',   __DIR__ . '/../uploads/avatars/');
define('UPLOAD_ID_DIR',    __DIR__ . '/../uploads/ids/');
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Stripe (replace with real keys)
define('STRIPE_SECRET_KEY',      'sk_test_...');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');

// PayPal
define('PAYPAL_CLIENT_ID',     '...');
define('PAYPAL_CLIENT_SECRET', '...');
define('PAYPAL_MODE',          'sandbox'); // 'live' in production

// Delivery fee (flat rate for now)
define('DELIVERY_FEE', 1.50);

// Order cancellation window (seconds)
define('CANCEL_WINDOW_SECONDS', 5 * 60); // 5 minutes
