<?php
// api/orders.php
// POST   /api/orders              — place order (customer)
// GET    /api/orders              — list orders (customer: own, cook: received, admin: all)
// GET    /api/orders/{id}         — order detail
// PATCH  /api/orders/{id}/status  — update status (cook/admin)
// DELETE /api/orders/{id}         — cancel (customer, within window)

require_once __DIR__ . '/../includes/helpers.php';

$method   = $_SERVER['REQUEST_METHOD'];
$parts    = array_filter(explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/')));
$orderId  = (int)($parts[1] ?? 0);
$action   = $parts[2] ?? null;

// ─── POST /api/orders ─────────────────────────────────────────
if ($method === 'POST' && !$orderId) {
    $user = requireRole('customer');

    $data = validate([
        'delivery_address' => 'required|min:5',
        'items'            => 'required',
    ]);

    $items = $data['items']; // [['meal_id'=>1, 'quantity'=>2], ...]
    if (!is_array($items) || empty($items)) {
        error('Order must contain at least one item');
    }

    // Validate items, gather cook_id and prices
    $mealIds = array_column($items, 'meal_id');
    $placeholders = implode(',', array_fill(0, count($mealIds), '?'));
    $meals = DB::run(
        "SELECT id, cook_id, price, is_available, is_deleted FROM meals WHERE id IN ($placeholders)",
        $mealIds
    )->fetchAll(PDO::FETCH_UNIQUE);

    $subtotal = 0;
    $cookId   = null;
    $lineItems = [];

    foreach ($items as $item) {
        $m = $meals[$item['meal_id']] ?? null;
        if (!$m)                   error("Meal {$item['meal_id']} not found", 404);
        if ($m['is_deleted'])      error("Meal {$item['meal_id']} is no longer available");
        if (!$m['is_available'])   error("Meal {$item['meal_id']} is currently unavailable");

        // All items must be from same cook
        if ($cookId && $cookId !== (int)$m['cook_id']) {
            error('All items must belong to the same cook per order');
        }
        $cookId    = (int)$m['cook_id'];
        $qty       = max(1, (int)$item['quantity']);
        $subtotal += $m['price'] * $qty;
        $lineItems[] = ['meal_id' => $m['id'], 'qty' => $qty, 'price' => $m['price']];
    }

    $deliveryFee = DELIVERY_FEE;
    $total       = $subtotal + $deliveryFee;

    $pdo = DB::connect();
    $pdo->beginTransaction();
    try {
        DB::run(
            'INSERT INTO orders (customer_id, cook_id, delivery_address, subtotal, delivery_fee, total, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$user['id'], $cookId, $data['delivery_address'], $subtotal, $deliveryFee, $total, $data['notes'] ?? null]
        );
        $orderId = (int) DB::lastInsertId();

        foreach ($lineItems as $li) {
            DB::run(
                'INSERT INTO order_items (order_id, meal_id, quantity, unit_price) VALUES (?, ?, ?, ?)',
                [$orderId, $li['meal_id'], $li['qty'], $li['price']]
            );
        }

        // Create pending payment record
        DB::run(
            'INSERT INTO payments (order_id, amount) VALUES (?, ?)',
            [$orderId, $total]
        );

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error('Failed to place order', 500);
    }

    // Notify cook
    notify($cookId, 'new_order', 'New order received', "Order #$orderId from {$user['name']}", "/cook/orders/$orderId");
    // Notify customer
    notify($user['id'], 'order_placed', 'Order placed', "Your order #$orderId has been placed.", "/orders/$orderId");

    respond(['message' => 'Order placed', 'order_id' => $orderId, 'total' => $total], 201);
}

// ─── GET /api/orders ─────────────────────────────────────────
if ($method === 'GET' && !$orderId) {
    $user   = requireAuth();
    $page   = paginate((int)($_GET['page'] ?? 1));
    $status = $_GET['status'] ?? null;

    $where  = [];
    $params = [];

    if ($user['role'] === 'customer') {
        $where[]  = 'o.customer_id = ?';
        $params[] = $user['id'];
    } elseif ($user['role'] === 'cook') {
        $where[]  = 'o.cook_id = ?';
        $params[] = $user['id'];
    }
    // admin sees all

    if ($status) {
        $where[]  = 'o.status = ?';
        $params[] = $status;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = (int) DB::run("SELECT COUNT(*) FROM orders o $whereSQL", $params)->fetchColumn();
    $params[] = $page['limit'];
    $params[] = $page['offset'];

    $orders = DB::run(
        "SELECT o.id, o.status, o.total, o.created_at,
                c.name AS customer_name, ck.name AS cook_name
         FROM orders o
         JOIN users c  ON c.id  = o.customer_id
         JOIN users ck ON ck.id = o.cook_id
         $whereSQL
         ORDER BY o.created_at DESC
         LIMIT ? OFFSET ?",
        $params
    )->fetchAll();

    respond(['data' => $orders, 'total' => $total, 'page' => $page['page']]);
}

// ─── GET /api/orders/{id} ────────────────────────────────────
if ($method === 'GET' && $orderId) {
    $user  = requireAuth();
    $order = DB::run(
        "SELECT o.*, c.name AS customer_name, ck.name AS cook_name
         FROM orders o
         JOIN users c  ON c.id  = o.customer_id
         JOIN users ck ON ck.id = o.cook_id
         WHERE o.id = ?",
        [$orderId]
    )->fetch();

    if (!$order) error('Order not found', 404);

    // Access control
    if ($user['role'] === 'customer' && $order['customer_id'] !== $user['id']) error('Forbidden', 403);
    if ($user['role'] === 'cook'     && $order['cook_id']     !== $user['id']) error('Forbidden', 403);

    $order['items'] = DB::run(
        "SELECT oi.quantity, oi.unit_price, m.title, m.image
         FROM order_items oi JOIN meals m ON m.id = oi.meal_id
         WHERE oi.order_id = ?",
        [$orderId]
    )->fetchAll();

    $order['payment'] = DB::run(
        'SELECT gateway, status, paid_at FROM payments WHERE order_id = ?',
        [$orderId]
    )->fetch();

    respond($order);
}

// ─── PATCH /api/orders/{id}/status ───────────────────────────
if ($method === 'PATCH' && $orderId && $action === 'status') {
    $user  = requireRole('cook', 'admin');
    $order = DB::run('SELECT * FROM orders WHERE id = ?', [$orderId])->fetch();

    if (!$order) error('Order not found', 404);
    if ($user['role'] === 'cook' && $order['cook_id'] !== $user['id']) error('Forbidden', 403);

    $allowed = ['pending','accepted','preparing','ready','out_for_delivery','delivered'];
    $newStatus = input('status');

    if (!in_array($newStatus, $allowed, true)) {
        error('Invalid status. Allowed: ' . implode(', ', $allowed));
    }

    DB::run('UPDATE orders SET status = ? WHERE id = ?', [$newStatus, $orderId]);

    // Notify customer
    notify(
        $order['customer_id'],
        'order_status',
        "Order #$orderId — " . ucfirst(str_replace('_', ' ', $newStatus)),
        '',
        "/orders/$orderId"
    );

    respond(['message' => 'Status updated', 'status' => $newStatus]);
}

// ─── DELETE /api/orders/{id} — Cancel ────────────────────────
if ($method === 'DELETE' && $orderId) {
    $user  = requireRole('customer', 'admin');
    $order = DB::run('SELECT * FROM orders WHERE id = ?', [$orderId])->fetch();

    if (!$order) error('Order not found', 404);
    if ($user['role'] === 'customer' && $order['customer_id'] !== $user['id']) error('Forbidden', 403);

    if ($order['status'] === 'cancelled') error('Order is already cancelled');
    if (in_array($order['status'], ['delivered', 'out_for_delivery'], true)) {
        error('Cannot cancel an order that is already ' . $order['status']);
    }

    // Enforce cancellation window for customers
    if ($user['role'] === 'customer') {
        $elapsed = time() - strtotime($order['created_at']);
        if ($elapsed > CANCEL_WINDOW_SECONDS) {
            error('Cancellation window has passed (max ' . (CANCEL_WINDOW_SECONDS / 60) . ' minutes after placing)');
        }
    }

    $reason = input('reason', '');
    DB::run(
        "UPDATE orders SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = ? WHERE id = ?",
        [$reason, $orderId]
    );

    // Notify cook
    notify($order['cook_id'], 'order_cancelled', "Order #$orderId cancelled", $reason, "/cook/orders/$orderId");

    respond(['message' => 'Order cancelled']);
}

error('Not found', 404);
