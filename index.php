<?php
$pageTitle = 'Fresh Homemade Food Delivered';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
  <div class="container position-relative" style="z-index:1">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <h1>Homemade Food,<br><span style="color:var(--hp-orange)">Delivered</span> to Your Door</h1>
        <p class="my-3">Connect with verified home cooks in your area. Real food, real flavors, made with love.</p>
        <form class="hero-search" onsubmit="searchMeals(event)">
          <i class="fas fa-search text-muted"></i>
          <input type="text" id="hero-q" placeholder="Search for mansaf, maqluba, knafeh…">
          <button type="submit" class="btn-hp btn">Search</button>
        </form>
        <div class="d-flex gap-4 mt-4">
          <div style="color:#fff">
            <div style="font-size:1.6rem;font-weight:800">85+</div>
            <div style="font-size:.85rem;opacity:.7">Survey respondents</div>
          </div>
          <div style="color:#fff">
            <div style="font-size:1.6rem;font-weight:800">81%</div>
            <div style="font-size:.85rem;opacity:.7">Would use the platform</div>
          </div>
          <div style="color:#fff">
            <div style="font-size:1.6rem;font-weight:800">54%</div>
            <div style="font-size:.85rem;opacity:.7">Prefer homemade meals</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-flex justify-content-end">
        <div style="width:420px;height:320px;background:rgba(255,107,53,.15);border-radius:var(--radius);display:flex;align-items:center;justify-content:center">
          <i class="fas fa-bowl-food" style="font-size:8rem;color:var(--hp-orange);opacity:.7"></i>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Categories -->
<section class="container my-5">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <div>
      <div class="section-title">Browse by Category</div>
      <div class="section-sub">Find the perfect meal type</div>
    </div>
    <a href="/meals.php" class="btn-hp-outline btn btn-sm">See All</a>
  </div>
  <div class="d-flex gap-2 flex-wrap" id="categories-row">
    <div class="cat-pill active" onclick="filterCat(0, this)"><i class="fas fa-th"></i> All</div>
  </div>
</section>

<!-- Featured Meals -->
<section class="container mb-5">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <div>
      <div class="section-title">Featured Meals</div>
      <div class="section-sub">Top-rated meals from verified cooks</div>
    </div>
    <a href="/meals.php" class="btn-hp-outline btn btn-sm">View All</a>
  </div>
  <div class="row g-3" id="meals-grid">
    <?php for ($i = 0; $i < 6; $i++): ?>
    <div class="col-sm-6 col-lg-4">
      <div class="card-hp meal-card h-100" style="animation:pulse 1.5s infinite">
        <div class="img-placeholder"><i class="fas fa-spinner fa-spin"></i></div>
        <div class="card-body"><div class="bg-secondary rounded" style="height:.8rem;width:60%;margin-bottom:.5rem;opacity:.2"></div></div>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</section>

<!-- How it works -->
<section style="background:var(--hp-light);padding:4rem 0">
  <div class="container">
    <div class="text-center mb-4">
      <div class="section-title">How It Works</div>
      <div class="section-sub">Three simple steps to enjoy homemade food</div>
    </div>
    <div class="row g-4 justify-content-center">
      <?php
      $steps = [
        ['fa-search', 'Browse Meals', 'Explore hundreds of homemade meals from verified cooks near you. Filter by category, price, or rating.'],
        ['fa-cart-shopping', 'Place Your Order', 'Select your favorites, checkout securely with Stripe or PayPal, and get instant confirmation.'],
        ['fa-truck', 'Get it Delivered', 'Track your order in real time from your cook\'s kitchen to your doorstep.'],
      ];
      foreach ($steps as $i => $s): ?>
      <div class="col-md-4 text-center">
        <div style="width:72px;height:72px;background:var(--hp-orange);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center">
          <i class="fas <?= $s[0] ?>" style="color:#fff;font-size:1.6rem"></i>
        </div>
        <div style="font-size:1.15rem;font-weight:700;margin-bottom:.4rem"><?= $i+1 ?>. <?= $s[1] ?></div>
        <p style="color:var(--hp-gray);font-size:.93rem"><?= $s[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="container my-5 text-center">
  <div class="card-hp p-5" style="background:linear-gradient(135deg,var(--hp-dark),var(--hp-navy));color:#fff">
    <h2 style="font-weight:800">Are You a Home Cook?</h2>
    <p style="opacity:.8;max-width:500px;margin:.75rem auto 1.5rem">Turn your kitchen skills into income. Join Homeplate, get verified, and start selling your homemade meals to customers near you.</p>
    <a href="<?= $currentUser ? '/cook/apply.php' : '/register.php' ?>" class="btn-hp btn px-4">Get Started</a>
  </div>
</section>

<?php
$extraJs = <<<JS
// Load categories
API.get('/api/meals').then(() => {}).catch(() => {});

const CATS = [
  {id:0,name:'All',icon:'fa-th'},
  {id:1,name:'Breakfast',icon:'fa-sunrise'},{id:2,name:'Lunch',icon:'fa-sun'},
  {id:3,name:'Dinner',icon:'fa-moon'},{id:4,name:'Snacks',icon:'fa-egg-fried'},
  {id:5,name:'Desserts',icon:'fa-cake'},{id:6,name:'Beverages',icon:'fa-mug-hot'},
  {id:7,name:'Healthy',icon:'fa-heart'},{id:8,name:'Vegetarian',icon:'fa-leaf'},
];
const catRow = document.getElementById('categories-row');
CATS.slice(1).forEach(c => {
  catRow.innerHTML += `<div class="cat-pill" onclick="filterCat(${c.id},this)"><i class="fas ${c.icon}"></i> ${c.name}</div>`;
});

let activeCategory = 0;
async function filterCat(id, el) {
  activeCategory = id;
  document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  await loadMeals();
}

function searchMeals(e) {
  e.preventDefault();
  const q = document.getElementById('hero-q').value.trim();
  if (q) window.location = `/meals.php?q=${encodeURIComponent(q)}`;
}

async function loadMeals() {
  const grid = document.getElementById('meals-grid');
  grid.innerHTML = '<div class="text-center py-5 col-12"><div class="spinner-border text-orange"></div></div>';
  try {
    const params = new URLSearchParams({ sort:'rating_avg', page:1 });
    if (activeCategory) params.set('category', activeCategory);
    const data = await API.get(`/api/meals?${params}`);
    if (!data.data.length) {
      grid.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="fas fa-bowl-food fa-3x mb-3 d-block" style="opacity:.2"></i>No meals found</div>';
      return;
    }
    grid.innerHTML = data.data.slice(0,6).map(m => mealCard(m)).join('');
  } catch (e) {
    grid.innerHTML = `<div class="col-12 text-center py-4 text-danger">${e.message}</div>`;
  }
}

loadMeals();
JS;
include __DIR__ . '/includes/footer.php';
?>
