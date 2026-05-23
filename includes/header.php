<?php
// includes/header.php
// Usage: include with $pageTitle and optional $bodyClass set beforehand

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

startSession();
$currentUser = currentUser();

$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';

// Determine active nav
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
function navActive(string $page): string {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — Homeplate</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="<?= $bodyClass ?>">

<div id="page-loader" class="page-loader">
  <div class="spinner-border text-orange" role="status"></div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-hp navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="/"><i class="fas fa-utensils me-1 text-orange"></i>Home<span>plate</span></a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= navActive('meals') ?>" href="/meals.php"><i class="fas fa-search me-1"></i>Browse Meals</a>
        </li>
        <?php if ($currentUser): ?>
        <li class="nav-item">
          <a class="nav-link <?= navActive('orders') ?>" href="/orders.php"><i class="fas fa-receipt me-1"></i>My Orders</a>
        </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav align-items-center gap-1">
        <!-- Cart -->
        <li class="nav-item">
          <a class="nav-link position-relative" href="/checkout.php">
            <i class="fas fa-shopping-cart fa-lg"></i>
            <span id="cart-badge" class="badge bg-orange rounded-pill ms-1" style="display:none;font-size:.7rem">0</span>
          </a>
        </li>

        <?php if ($currentUser): ?>
        <!-- Notifications -->
        <li class="nav-item">
          <a class="nav-link position-relative" href="/notifications.php">
            <i class="fas fa-bell fa-lg"></i>
            <span id="notif-dot" class="badge-notif" style="display:none"></span>
          </a>
        </li>

        <!-- Messages -->
        <li class="nav-item">
          <a class="nav-link <?= navActive('messages') ?>" href="/messages.php">
            <i class="fas fa-comment-dots fa-lg"></i>
          </a>
        </li>

        <!-- User dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
            <?php if (!empty($currentUser['avatar'])): ?>
              <img src="/uploads/avatars/<?= htmlspecialchars($currentUser['avatar']) ?>" width="30" height="30" class="rounded-circle object-fit-cover">
            <?php else: ?>
              <div style="width:30px;height:30px;background:var(--hp-orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem">
                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
            <span class="d-none d-md-inline" style="font-size:.9rem"><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:var(--radius)">
            <li><div class="dropdown-header"><strong><?= htmlspecialchars($currentUser['name']) ?></strong><br><small class="text-muted"><?= $currentUser['role'] ?></small></div></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user me-2 text-orange"></i>Profile</a></li>
            <li><a class="dropdown-item" href="/favorites.php"><i class="fas fa-heart me-2 text-orange"></i>Favorites</a></li>
            <?php if ($currentUser['role'] === 'cook'): ?>
            <li><hr class="dropdown-divider m-1"></li>
            <li><a class="dropdown-item" href="/cook/dashboard.php"><i class="fas fa-chart-line me-2 text-orange"></i>Cook Dashboard</a></li>
            <li><a class="dropdown-item" href="/cook/meals.php"><i class="fas fa-burger me-2 text-orange"></i>My Meals</a></li>
            <li><a class="dropdown-item" href="/cook/orders.php"><i class="fas fa-list me-2 text-orange"></i>Orders</a></li>
            <?php elseif ($currentUser['role'] === 'customer'): ?>
            <li><hr class="dropdown-divider m-1"></li>
            <li><a class="dropdown-item" href="/cook/apply.php"><i class="fas fa-chef-hat me-2 text-orange"></i>Become a Cook</a></li>
            <?php endif; ?>
            <?php if ($currentUser['role'] === 'admin'): ?>
            <li><hr class="dropdown-divider m-1"></li>
            <li><a class="dropdown-item" href="/admin/dashboard.php"><i class="fas fa-cog me-2 text-orange"></i>Admin Panel</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider m-1"></li>
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="logout()">
                <i class="fas fa-sign-out-alt me-2"></i>Log Out
              </a>
            </li>
          </ul>
        </li>

        <?php else: ?>
        <li class="nav-item">
          <a class="nav-link <?= navActive('login') ?>" href="/login.php">Log In</a>
        </li>
        <li class="nav-item">
          <a href="/register.php" class="btn-hp btn btn-sm">Sign Up</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<!-- /Navbar -->
