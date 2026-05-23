<?php
$pageTitle = 'Meal Detail';
include __DIR__ . '/includes/header.php';
$mealId = (int)($_GET['id'] ?? 0);
if (!$mealId) { header('Location: /meals.php'); exit; }
?>

<div class="container py-4" id="meal-page">
  <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
</div>

<?php
$extraJs = <<<'JS'
const mealId = {$mealId};

async function loadMeal() {
  try {
    const m = await API.get('/api/meals/' + mealId);
    document.title = m.title + ' — Homeplate';

    const img = m.image
      ? `<img src="/uploads/meals/${m.image}" class="w-100 rounded-3 object-fit-cover" style="max-height:420px">`
      : `<div class="img-placeholder rounded-3" style="height:300px;font-size:5rem"><i class="fas fa-utensils"></i></div>`;

    const stars = renderStars(m.rating_avg, m.rating_count);

    document.getElementById('meal-page').innerHTML = `
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/" class="text-orange">Home</a></li>
        <li class="breadcrumb-item"><a href="/meals.php" class="text-orange">Meals</a></li>
        <li class="breadcrumb-item active">${m.title}</li>
      </ol>
    </nav>

    <div class="row g-4">
      <div class="col-lg-7">${img}</div>
      <div class="col-lg-5">
        <div class="d-flex justify-content-between align-items-start">
          <h1 style="font-size:1.7rem;font-weight:800">${m.title}</h1>
          <button onclick="toggleFav(this,${m.id})" class="btn fav-btn p-1" style="color:#ccc;background:none;border:none;font-size:1.3rem">
            <i class="fas fa-heart"></i>
          </button>
        </div>

        ${m.category ? `<span class="badge bg-light text-dark border mb-2">${m.category}</span>` : ''}

        <div class="stars-lg mb-2">${stars}</div>

        <div class="d-flex align-items-center gap-2 mb-3">
          <img src="${m.cook_avatar ? '/uploads/avatars/'+m.cook_avatar : ''}" 
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
               width="36" height="36" class="rounded-circle object-fit-cover">
          <div style="width:36px;height:36px;background:var(--hp-orange);border-radius:50%;display:none;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem">
            ${m.cook_name.charAt(0).toUpperCase()}
          </div>
          <div>
            <div style="font-size:.9rem;font-weight:600">${m.cook_name}</div>
            <div class="text-muted" style="font-size:.78rem">${renderStars(m.cook_rating)} cook rating</div>
          </div>
          <a href="/cook.php?id=${m.cook_id}" class="ms-auto btn-hp-outline btn btn-sm">View Profile</a>
        </div>

        ${m.description ? `<p style="color:var(--hp-gray)">${m.description}</p>` : ''}

        ${m.ingredients ? `<div class="mb-2"><strong>Ingredients:</strong> <span class="text-muted">${m.ingredients}</span></div>` : ''}
        ${m.allergy_info ? `<div class="alert alert-warning py-2 px-3 mb-3"><i class="fas fa-triangle-exclamation me-1"></i><strong>Allergy:</strong> ${m.allergy_info}</div>` : ''}

        <div class="divider"></div>

        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="price" style="font-size:1.8rem">JD ${parseFloat(m.price).toFixed(2)}</div>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary rounded-circle" onclick="changeQty(-1)" style="width:36px;height:36px;padding:0">−</button>
            <span id="qty" style="font-size:1.1rem;font-weight:700;min-width:24px;text-align:center">1</span>
            <button class="btn btn-outline-secondary rounded-circle" onclick="changeQty(1)" style="width:36px;height:36px;padding:0">+</button>
          </div>
        </div>

        ${m.is_available
          ? `<button class="btn-hp btn w-100 py-2" onclick="addMealToCart(${JSON.stringify(JSON.stringify(m))})"><i class="fas fa-cart-plus me-2"></i>Add to Cart</button>`
          : `<button class="btn btn-secondary w-100 py-2" disabled><i class="fas fa-ban me-2"></i>Currently Unavailable</button>`
        }

        <a href="/messages.php?with=${m.cook_id}" class="btn-hp-outline btn w-100 py-2 mt-2">
          <i class="fas fa-comment-dots me-1"></i>Message Cook
        </a>
      </div>
    </div>

    <!-- Reviews -->
    <div class="mt-5">
      <h4 class="fw-bold mb-3"><i class="fas fa-star me-2 text-orange"></i>Reviews</h4>
      ${m.reviews.length ? m.reviews.map(r => `
        <div class="card-hp p-3 mb-3">
          <div class="d-flex justify-content-between">
            <strong>${r.customer_name}</strong>
            <div class="stars">${renderStars(r.rating)}</div>
          </div>
          ${r.comment ? `<p class="mb-0 mt-1 text-muted">${r.comment}</p>` : ''}
          <small class="text-muted">${fmtDate(r.created_at)}</small>
        </div>
      `).join('') : '<p class="text-muted">No reviews yet. Be the first to order!</p>'}
    </div>`;

  } catch (e) {
    document.getElementById('meal-page').innerHTML = `
      <div class="text-center py-5 text-danger"><i class="fas fa-exclamation-triangle fa-3x mb-3 d-block"></i>${e.message}</div>`;
  }
}

let qty = 1;
function changeQty(d) {
  qty = Math.max(1, qty + d);
  document.getElementById('qty').textContent = qty;
}

function addMealToCart(mealJson) {
  const meal = JSON.parse(mealJson);
  for (let i = 0; i < qty; i++) Cart.add(meal);
  Toast.success(`${meal.title} ×${qty} added to cart!`);
}

loadMeal();
JS;
include __DIR__ . '/includes/footer.php';
?>
