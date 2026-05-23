<?php
$pageTitle = 'Cook Dashboard';
include __DIR__ . '/../includes/header.php';
if (!$currentUser || $currentUser['role'] !== 'cook') {
    header('Location: /cook/apply.php'); exit;
}
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-orange"></i>Cook Dashboard</h2>
    <a href="/cook/meals.php" class="btn-hp btn"><i class="fas fa-plus me-1"></i>Add Meal</a>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4" id="stats-row">
    <?php foreach (['Meals','Available','Total Orders','Revenue'] as $s): ?>
    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="lbl"><?= $s ?></div>
        <div class="num">…</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Recent orders -->
  <div class="card-hp p-4">
    <div class="d-flex justify-content-between mb-3">
      <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-orange"></i>Recent Orders</h5>
      <a href="/cook/orders.php" class="text-orange" style="font-size:.9rem">View all →</a>
    </div>
    <div id="recent-orders">
      <div class="text-center py-4"><div class="spinner-border text-orange"></div></div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
async function loadDashboard() {
  try {
    const d = await API.get('/api/cooks/dashboard');

    const totalOrders  = d.order_stats.reduce((s,o) => s + parseInt(o.cnt), 0);
    const totalRevenue = d.order_stats.filter(o => o.status==='delivered').reduce((s,o) => s + parseFloat(o.revenue||0), 0);

    const nums = [d.meal_stats.total_meals, d.meal_stats.available_meals, totalOrders, 'JD '+totalRevenue.toFixed(2)];
    const icons = ['fa-burger','fa-circle-check','fa-receipt','fa-coins'];
    const colors = ['var(--hp-orange)','#2ECC71','#3498DB','#9B59B6'];

    document.getElementById('stats-row').querySelectorAll('.stat-card').forEach((c, i) => {
      c.style.borderLeftColor = colors[i];
      c.querySelector('.num').textContent = nums[i];
      c.querySelector('.num').style.color = colors[i];
    });

    // Load recent orders
    const orders = await API.get('/api/orders?page=1');
    const tbody  = document.getElementById('recent-orders');

    if (!orders.data.length) {
      tbody.innerHTML = '<div class="text-center py-4 text-muted">No orders yet</div>';
      return;
    }

    tbody.innerHTML = `
    <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>${orders.data.slice(0,8).map(o => `
        <tr>
          <td>#${o.id}</td>
          <td>${o.customer_name}</td>
          <td class="text-orange fw-bold">JD ${parseFloat(o.total).toFixed(2)}</td>
          <td>${statusBadge(o.status)}</td>
          <td class="text-muted" style="font-size:.83rem">${fmtDate(o.created_at)}</td>
          <td><a href="/order.php?id=${o.id}" class="btn btn-outline-secondary btn-sm">View</a></td>
        </tr>`).join('')}
      </tbody>
    </table></div>`;
  } catch (e) { Toast.error(e.message); }
}
loadDashboard();
JS;
include __DIR__ . '/../includes/footer.php';
?>
