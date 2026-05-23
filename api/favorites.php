<?php
// api/favorites.php — POST/DELETE toggle favorite, GET list
require_once __DIR__ . '/../includes/helpers.php';
$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();

if ($method === 'GET') {
    $page  = paginate((int)($_GET['page'] ?? 1));
    $favs  = DB::run(
        "SELECT m.id, m.title, m.price, m.image, m.rating_avg
         FROM favorites f JOIN meals m ON m.id = f.meal_id
         WHERE f.user_id = ? AND m.is_deleted = 0
         ORDER BY f.created_at DESC LIMIT ? OFFSET ?",
        [$user['id'], $page['limit'], $page['offset']]
    )->fetchAll();
    respond(['data' => $favs]);
}

if ($method === 'POST') {
    $mealId = (int)(body()['meal_id'] ?? 0);
    if (!$mealId) error('meal_id required');

    $meal = DB::run('SELECT id FROM meals WHERE id = ? AND is_deleted = 0', [$mealId])->fetch();
    if (!$meal) error('Meal not found', 404);

    DB::run(
        'INSERT IGNORE INTO favorites (user_id, meal_id) VALUES (?, ?)',
        [$user['id'], $mealId]
    );
    respond(['message' => 'Added to favorites'], 201);
}

if ($method === 'DELETE') {
    $mealId = (int)(body()['meal_id'] ?? 0);
    DB::run('DELETE FROM favorites WHERE user_id = ? AND meal_id = ?', [$user['id'], $mealId]);
    respond(['message' => 'Removed from favorites']);
}
