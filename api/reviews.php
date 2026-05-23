<?php
// api/reviews.php
// POST /api/reviews              — submit review (customer, after delivered order)
// GET  /api/reviews?meal_id=X   — get reviews for a meal

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $user = requireRole('customer');

    $data = validate([
        'order_id' => 'required|numeric',
        'rating'   => 'required|numeric',
    ]);

    $orderId = (int)$data['order_id'];
    $rating  = (int)$data['rating'];

    if ($rating < 1 || $rating > 5) error('Rating must be between 1 and 5');

    $order = DB::run(
        "SELECT * FROM orders WHERE id = ? AND customer_id = ? AND status = 'delivered'",
        [$orderId, $user['id']]
    )->fetch();

    if (!$order) error('You can only review a delivered order', 403);

    // One review per order
    $exists = DB::run('SELECT id FROM reviews WHERE order_id = ?', [$orderId])->fetch();
    if ($exists) error('You have already reviewed this order', 409);

    // Get the primary meal from first order item
    $item = DB::run('SELECT meal_id FROM order_items WHERE order_id = ? LIMIT 1', [$orderId])->fetch();
    if (!$item) error('Order has no items', 500);

    DB::run(
        'INSERT INTO reviews (order_id, meal_id, customer_id, rating, comment) VALUES (?, ?, ?, ?, ?)',
        [$orderId, $item['meal_id'], $user['id'], $rating, $data['comment'] ?? null]
    );

    // Update meal rating_avg and rating_count
    DB::run(
        'UPDATE meals
         SET rating_count = rating_count + 1,
             rating_avg   = (rating_avg * rating_count + ?) / (rating_count + 1)
         WHERE id = ?',
        [$rating, $item['meal_id']]
    );

    // Update cook rating similarly
    DB::run(
        'UPDATE cook_profiles
         SET rating_count = rating_count + 1,
             rating_avg   = (rating_avg * rating_count + ?) / (rating_count + 1)
         WHERE user_id = ?',
        [$rating, $order['cook_id']]
    );

    respond(['message' => 'Review submitted'], 201);
}

if ($method === 'GET') {
    $mealId = (int)($_GET['meal_id'] ?? 0);
    if (!$mealId) error('meal_id is required');

    $page    = paginate((int)($_GET['page'] ?? 1));
    $reviews = DB::run(
        "SELECT r.rating, r.comment, r.created_at, u.name AS customer_name
         FROM reviews r JOIN users u ON u.id = r.customer_id
         WHERE r.meal_id = ?
         ORDER BY r.created_at DESC
         LIMIT ? OFFSET ?",
        [$mealId, $page['limit'], $page['offset']]
    )->fetchAll();

    respond(['data' => $reviews]);
}
