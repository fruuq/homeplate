<?php
$pageTitle = 'Cook Profile';
include __DIR__ . '/includes/header.php';
$cookId = (int)($_GET['id'] ?? 0);
if (!$cookId) { header('Location: /meals.php'); exit; }
?>

<div class="container py-4" id="cook-page">
  <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
</div>

<?php
$extraJs = <<<'JS'
const cookId = {$cookId};

async function loadCook() {
  try {
    const c = await API.get('/api/cooks/' + cookId);
    document.title = c.name + ' — Homeplate';

    document.getElementById('cook-page').innerHTML = `
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card-hp p-4 text-center sticky-top" style="top:80px">
          <div style="width:90px;height:90px;background:var(--hp-orange);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;font-weight:800">
            ${c.name.charAt(0).toUpperCase()}
          </div>
          <h4 class="fw-bold mb-0">${c.name}</h4>
          <div class="verified-badge mt-2">✓ Verified Cook</div>
          <div class="stars-lg my-2">${renderStars(c.rating_avg, c.rating_count)}</div>
          ${c.specialty ? `<div class="text-muted mb-2"><i class="fas fa-star-of-life me-1 text-orange"></i>${c.specialty}</div>` : ''}
          ${c.bio ? `<p class="text-muted" style="font-size:.9rem">${c.bio}</p>` : ''}
          <a href="/messages.php?with=${c.id}" class="btn-hp btn w-100 mt-2">
            <i class="fas fa-comment-dots me-1"></i>Message
          </a>
        </div>
      </div>
      <div class="col-md-8">
        <h4 class="fw-bold mb-3">Meals by ${c.name}</h4>
        <div class="row g-3">
          ${c.meals.length
            ? c.meals.map(m => mealCard({...m, cook_id: c.id, cook_name: c.name})).join('')
            : '<div class="col-12 text-muted text-center py-4">No meals listed yet</div>'
          }
        </div>
      </div>
    </div>`;
  } catch (e) {
    document.getElementById('cook-page').innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`;
  }
}
loadCook();
JS;
include __DIR__ . '/includes/footer.php';
?>
