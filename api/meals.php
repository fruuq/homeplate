<?php
// api/meals.php
// GET    /api/meals            — browse / search / filter
// GET    /api/meals/{id}       — meal detail
// POST   /api/meals            — create (cook only)
// PUT    /api/meals/{id}       — update (cook only, own meal)
// DELETE /api/meals/{id}       — soft-delete (cook only, own meal)
// PATCH  /api/meals/{id}/availability — toggle availability

require_once __DIR__ . '/../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
// Simple routing via PATH_INFO or query param ?id=
$pathParts = array_filter(explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/')));
$mealId    = (int)($pathParts[1] ?? 0);
$subAction = $pathParts[2] ?? null; // e.g. "availability"

// ─── GET /api/meals ───────────────────────────────────────────
if ($method === 'GET' && !$mealId) {
    $search   = trim($_GET['q']       ?? '');
    $category = (int)($_GET['category'] ?? 0);
    $minPrice = (float)($_GET['min_price'] ?? 0);
    $maxPrice = (float)($_GET['max_price'] ?? 0);
    $cookId   = (int)($_GET['cook_id']   ?? 0);
    $sort     = $_GET['sort'] ?? 'created_at'; // created_at | price | rating
    $page     = paginate((int)($_GET['page'] ?? 1));

    $allowed_sorts = ['created_at', 'price', 'rating_avg'];
    if (!in_array($sort, $allowed_sorts, true)) $sort = 'created_at';

    $where  = ['m.is_available = 1', 'm.is_deleted = 0', 'cp.verification = "approved"'];
    $params = [];

    if ($search) {
        $where[]  = 'MATCH(m.title, m.description, m.ingredients) AGAINST(? IN BOOLEAN MODE)';
        $params[] = $search . '*';
    }
    if ($category) {
        $where[]  = 'm.category_id = ?';
        $params[] = $category;
    }
    if ($minPrice > 0) {
        $where[]  = 'm.price >= ?';
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $where[]  = 'm.price <= ?';
        $params[] = $maxPrice;
    }
    if ($cookId) {
        $where[]  = 'm.cook_id = ?';
        $params[] = $cookId;
    }

    $whereSQL = implode(' AND ', $where);

    $total = (int) DB::run(
        "SELECT COUNT(*) FROM meals m
         JOIN cook_profiles cp ON cp.user_id = m.cook_id
         WHERE $whereSQL",
        $params
    )->fetchColumn();

    $params[] = $page['limit'];
    $params[] = $page['offset'];

    $meals = DB::run(
        "SELECT m.id, m.title, m.description, m.price, m.image,
                m.rating_avg, m.rating_count, m.is_available,
                mc.name AS category,
                u.id AS cook_id, u.name AS cook_name, cp.rating_avg AS cook_rating
         FROM meals m
         LEFT JOIN meal_categories mc ON mc.id = m.category_id
         JOIN users u ON u.id = m.cook_id
         JOIN cook_profiles cp ON cp.user_id = m.cook_id
         WHERE $whereSQL
         ORDER BY m.$sort DESC
         LIMIT ? OFFSET ?",
        $params
    )->fetchAll();

    respond(['data' => $meals, 'total' => $total, 'page' => $page['page']]);
}

// ─── GET /api/meals/{id} ──────────────────────────────────────
if ($method === 'GET' && $mealId) {
    $meal = DB::run(
        "SELECT m.*, mc.name AS category,
                u.id AS cook_id, u.name AS cook_name, u.avatar AS cook_avatar,
                cp.bio AS cook_bio, cp.rating_avg AS cook_rating, cp.rating_count AS cook_rating_count
         FROM meals m
         LEFT JOIN meal_categories mc ON mc.id = m.category_id
         JOIN users u ON u.id = m.cook_id
         JOIN cook_profiles cp ON cp.user_id = m.cook_id
         WHERE m.id = ? AND m.is_deleted = 0",
        [$mealId]
    )->fetch();

    if (!$meal) error('Meal not found', 404);

    // Attach latest 10 reviews
    $meal['reviews'] = DB::run(
        "SELECT r.rating, r.comment, r.created_at, u.name AS customer_name
         FROM reviews r JOIN users u ON u.id = r.customer_id
         WHERE r.meal_id = ? ORDER BY r.created_at DESC LIMIT 10",
        [$mealId]
    )->fetchAll();

    respond($meal);
}

// ─── POST /api/meals ─────────────────────────────────────────
if ($method === 'POST' && !$mealId) {
    $cook = requireRole('cook');

    // Verify cook is approved
    $profile = DB::run(
        'SELECT verification FROM cook_profiles WHERE user_id = ?',
        [$cook['id']]
    )->fetch();

    if (!$profile || $profile['verification'] !== 'approved') {
        error('Cook account is not verified', 403);
    }

    $data = validate([
        'title'       => 'required|min:3|max:200',
        'price'       => 'required|numeric|positive',
        'category_id' => 'numeric',
    ]);

    $image = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $image = uploadImage('image', UPLOAD_MEAL_DIR);
    }

    DB::run(
        'INSERT INTO meals (cook_id, category_id, title, description, ingredients, allergy_info, price, image)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $cook['id'],
            $data['category_id'] ?: null,
            $data['title'],
            $data['description'] ?? null,
            $data['ingredients'] ?? null,
            $data['allergy_info'] ?? null,
            $data['price'],
            $image,
        ]
    );

    respond(['message' => 'Meal created', 'id' => (int) DB::lastInsertId()], 201);
}

// ─── PUT /api/meals/{id} ──────────────────────────────────────
if ($method === 'PUT' && $mealId) {
    $cook = requireRole('cook');
    $meal = DB::run('SELECT * FROM meals WHERE id = ? AND is_deleted = 0', [$mealId])->fetch();

    if (!$meal)                         error('Meal not found', 404);
    if ($meal['cook_id'] !== $cook['id']) error('Forbidden', 403);

    $data  = body();
    $image = $meal['image'];

    if (!empty($_FILES['image']['tmp_name'])) {
        $image = uploadImage('image', UPLOAD_MEAL_DIR);
        // Optionally delete old image here
    }

    DB::run(
        'UPDATE meals SET title=?, description=?, ingredients=?, allergy_info=?,
                          price=?, category_id=?, image=?
         WHERE id = ?',
        [
            $data['title']        ?? $meal['title'],
            $data['description']  ?? $meal['description'],
            $data['ingredients']  ?? $meal['ingredients'],
            $data['allergy_info'] ?? $meal['allergy_info'],
            $data['price']        ?? $meal['price'],
            $data['category_id']  ?? $meal['category_id'],
            $image,
            $mealId,
        ]
    );

    respond(['message' => 'Meal updated']);
}

// ─── DELETE /api/meals/{id} ───────────────────────────────────
if ($method === 'DELETE' && $mealId) {
    $cook = requireRole('cook', 'admin');
    $meal = DB::run('SELECT cook_id FROM meals WHERE id = ? AND is_deleted = 0', [$mealId])->fetch();

    if (!$meal) error('Meal not found', 404);
    if ($cook['role'] !== 'admin' && $meal['cook_id'] !== $cook['id']) error('Forbidden', 403);

    DB::run('UPDATE meals SET is_deleted = 1 WHERE id = ?', [$mealId]);
    respond(['message' => 'Meal deleted']);
}

// ─── PATCH /api/meals/{id}/availability ───────────────────────
if ($method === 'PATCH' && $mealId && $subAction === 'availability') {
    $cook = requireRole('cook');
    $meal = DB::run('SELECT cook_id, is_available FROM meals WHERE id = ? AND is_deleted = 0', [$mealId])->fetch();

    if (!$meal)                           error('Meal not found', 404);
    if ($meal['cook_id'] !== $cook['id']) error('Forbidden', 403);

    $toggle = $meal['is_available'] ? 0 : 1;
    DB::run('UPDATE meals SET is_available = ? WHERE id = ?', [$toggle, $mealId]);
    respond(['is_available' => (bool)$toggle]);
}

error('Not found', 404);
