<?php
$pageTitle = 'My Favorites';
include __DIR__ . '/includes/header.php';
if (!$currentUser) { header('Location: /login.php'); exit; }
?>

<div class="container py-4">
  <h2 class="fw-bold mb-4"><i class="fas fa-heart me-2 text-orange"></i>My Favorites</h2>
  <div class="row g-3" id="favs-grid">
    <div class="col-12 text-center py-5"><div class="spinner-border text-orange"></div></div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
async function loadFavorites() {
  const grid = document.getElementById('favs-grid');
  try {
    const data = await API.get('/api/favorites');
    if (!data.data.length) {
      grid.innerHTML = `<div class="col-12 text-center py-5 text-muted">
        <i class="fas fa-heart-crack fa-3x mb-3 d-block" style="opacity:.2"></i>
        No favorites yet. <a href="/meals.php" class="text-orange">Browse meals</a> and heart the ones you love!
      </div>`;
      return;
    }
    grid.innerHTML = data.data.map(m => mealCard(m)).join('');
  } catch (e) {
    grid.innerHTML = `<div class="col-12 text-danger text-center">${e.message}</div>`;
  }
}
loadFavorites();
JS;
include __DIR__ . '/includes/footer.php';
?>
