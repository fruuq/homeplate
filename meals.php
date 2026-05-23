<?php
$pageTitle = 'Browse Meals';
include __DIR__ . '/includes/header.php';
$initQ        = htmlspecialchars($_GET['q']        ?? '');
$initCategory = (int)($_GET['category'] ?? 0);
?>

<div class="container py-4">
  <div class="row g-4">

    <!-- Filters sidebar -->
    <div class="col-lg-3">
      <div class="filter-card sticky-top" style="top:80px">
        <h6><i class="fas fa-filter me-2 text-orange"></i>Filters</h6>

        <div class="mb-3">
          <label class="form-label">Search</label>
          <input type="text" class="form-control" id="f-search" value="<?= $initQ ?>" placeholder="Keyword…">
        </div>

        <div class="mb-3">
          <label class="form-label">Category</label>
          <select class="form-select" id="f-category">
            <option value="0">All Categories</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Price Range (JD)</label>
          <div class="d-flex gap-2">
            <input type="number" class="form-control" id="f-min" placeholder="Min" min="0">
            <input type="number" class="form-control" id="f-max" placeholder="Max" min="0">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Sort By</label>
          <select class="form-select" id="f-sort">
            <option value="created_at">Newest</option>
            <option value="rating_avg">Top Rated</option>
            <option value="price">Price</option>
          </select>
        </div>

        <button class="btn-hp btn w-100" onclick="applyFilters()">Apply Filters</button>
        <button class="btn w-100 mt-2 btn-outline-secondary" onclick="clearFilters()">Clear</button>
      </div>
    </div>

    <!-- Meals grid -->
    <div class="col-lg-9">
      <!-- Category pills -->
      <div class="d-flex gap-2 flex-wrap mb-3" id="cat-pills">
        <div class="cat-pill <?= $initCategory===0?'active':'' ?>" onclick="setCat(0,this)">
          <i class="fas fa-th"></i> All
        </div>
      </div>

      <!-- Results header -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span id="result-count" class="text-muted" style="font-size:.9rem">Loading…</span>
      </div>

      <div class="row g-3" id="meals-grid">
        <div class="col-12 text-center py-5">
          <div class="spinner-border text-orange"></div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-center mt-4" id="pagination"></div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
const CATS = [
  {id:1,name:'Breakfast',icon:'fa-sun'},{id:2,name:'Lunch',icon:'fa-utensils'},
  {id:3,name:'Dinner',icon:'fa-moon'},{id:4,name:'Snacks',icon:'fa-cookie'},
  {id:5,name:'Desserts',icon:'fa-cake-candles'},{id:6,name:'Beverages',icon:'fa-mug-hot'},
  {id:7,name:'Healthy',icon:'fa-heart'},{id:8,name:'Vegetarian',icon:'fa-leaf'},
];

const catPills = document.getElementById('cat-pills');
CATS.forEach(c => {
  catPills.innerHTML += `<div class="cat-pill" onclick="setCat(${c.id},this)"><i class="fas ${c.icon}"></i> ${c.name}</div>`;
});

// Restore category from URL
let initCat = parseInt(new URLSearchParams(location.search).get('category') || '0');
if (initCat) {
  document.querySelectorAll('.cat-pill').forEach(p => {
    if (p.textContent.trim().toLowerCase().includes(CATS.find(c=>c.id===initCat)?.name.toLowerCase())) {
      p.classList.add('active');
    }
  });
  document.getElementById('f-category').value = initCat;
}

// Populate category select
const catSel = document.getElementById('f-category');
CATS.forEach(c => catSel.innerHTML += `<option value="${c.id}">${c.name}</option>`);
catSel.value = initCat || 0;

let currentPage = 1;

function setCat(id, el) {
  document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('f-category').value = id;
  currentPage = 1;
  loadMeals();
}

function applyFilters() { currentPage = 1; loadMeals(); }
function clearFilters() {
  document.getElementById('f-search').value = '';
  document.getElementById('f-category').value = 0;
  document.getElementById('f-min').value = '';
  document.getElementById('f-max').value = '';
  document.getElementById('f-sort').value = 'created_at';
  document.querySelectorAll('.cat-pill').forEach((p,i) => p.classList.toggle('active', i===0));
  currentPage = 1;
  loadMeals();
}

async function loadMeals() {
  const grid = document.getElementById('meals-grid');
  grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-orange"></div></div>';

  const params = new URLSearchParams();
  const q   = document.getElementById('f-search').value.trim();
  const cat = document.getElementById('f-category').value;
  const min = document.getElementById('f-min').value;
  const max = document.getElementById('f-max').value;
  const srt = document.getElementById('f-sort').value;

  if (q)   params.set('q', q);
  if (cat && cat != 0) params.set('category', cat);
  if (min) params.set('min_price', min);
  if (max) params.set('max_price', max);
  params.set('sort', srt);
  params.set('page', currentPage);

  try {
    const data = await API.get('/api/meals?' + params);
    const total = data.total;

    document.getElementById('result-count').textContent = `${total} meal${total===1?'':'s'} found`;

    if (!data.data.length) {
      grid.innerHTML = `<div class="col-12 text-center py-5 text-muted">
        <i class="fas fa-bowl-food fa-3x mb-3 d-block" style="opacity:.2"></i>
        No meals match your filters.
      </div>`;
    } else {
      grid.innerHTML = data.data.map(m => mealCard(m)).join('');
    }

    // Pagination
    const pages = Math.ceil(total / 20);
    buildPagination(pages);
  } catch (e) {
    grid.innerHTML = `<div class="col-12 text-center py-4 text-danger">${e.message}</div>`;
  }
}

function buildPagination(pages) {
  const pg = document.getElementById('pagination');
  if (pages <= 1) { pg.innerHTML = ''; return; }
  let html = '<ul class="pagination">';
  html += `<li class="page-item ${currentPage===1?'disabled':''}"><a class="page-link" onclick="gotoPage(${currentPage-1})">‹</a></li>`;
  for (let i = 1; i <= pages; i++) {
    html += `<li class="page-item ${i===currentPage?'active':''}"><a class="page-link" onclick="gotoPage(${i})">${i}</a></li>`;
  }
  html += `<li class="page-item ${currentPage===pages?'disabled':''}"><a class="page-link" onclick="gotoPage(${currentPage+1})">›</a></li>`;
  html += '</ul>';
  pg.innerHTML = html;
}
function gotoPage(p) { currentPage = p; loadMeals(); window.scrollTo(0,0); }

// Search on enter
document.getElementById('f-search').addEventListener('keydown', e => { if (e.key==='Enter') applyFilters(); });

// Init
const urlQ = new URLSearchParams(location.search).get('q');
if (urlQ) document.getElementById('f-search').value = urlQ;
loadMeals();
JS;
include __DIR__ . '/includes/footer.php';
?>
