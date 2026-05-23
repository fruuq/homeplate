<?php
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
if (!$currentUser || $currentUser['role'] !== 'admin') {
    http_response_code(403); die('Access denied');
}
?>

<div class="container-fluid py-4">
  <div class="row g-0">

    <!-- Admin Sidebar -->
    <div class="col-auto">
      <div class="sidebar">
        <div class="fw-bold text-muted mb-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em">Admin Panel</div>
        <a href="/admin/dashboard.php" class="sidebar-link active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="/admin/cooks.php"     class="sidebar-link"><i class="fas fa-user-check"></i> Cook Approvals</a>
        <a href="/admin/users.php"     class="sidebar-link"><i class="fas fa-users"></i> Users</a>
        <a href="/admin/reports.php"   class="sidebar-link"><i class="fas fa-flag"></i> Reports</a>
        <div class="divider"></div>
        <a href="/" class="sidebar-link"><i class="fas fa-arrow-left"></i> Back to Site</a>
      </div>
    </div>

    <!-- Main content -->
    <div class="col py-0 px-4">
      <h2 class="fw-bold mb-4"><i class="fas fa-chart-bar me-2 text-orange"></i>Platform Overview</h2>

      <!-- Stats -->
      <div class="row g-3 mb-4" id="stats-row">
        <?php foreach (['Total Users','Home Cooks','Total Orders','Revenue','Pending Cooks','Open Reports'] as $s): ?>
        <div class="col-sm-6 col-xl-2">
          <div class="stat-card">
            <div class="lbl"><?= $s ?></div>
            <div class="num">…</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent orders table -->
      <div class="card-hp p-4">
        <h5 class="fw-bold mb-3"><i class="fas fa-clock me-2 text-orange"></i>Recent Orders</h5>
        <div id="recent-orders">
          <div class="text-center py-4"><div class="spinner-border text-orange"></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
async function loadDashboard() {
  try {
    const d = await API.get('/api/admin/dashboard');

    const nums = [
      d.users.total,
      d.users.cooks,
      d.orders.total,
      'JD ' + parseFloat(d.orders.revenue||0).toFixed(2),
      d.pending_cooks,
      d.open_reports,
    ];
    const colors = ['var(--hp-orange)','#3498DB','#9B59B6','#2ECC71','#F39C12','#E74C3C'];

    document.getElementById('stats-row').querySelectorAll('.stat-card').forEach((c, i) => {
      c.style.borderLeftColor = colors[i];
      c.querySelector('.num').textContent = nums[i];
      c.querySelector('.num').style.color = colors[i];
    });

    document.getElementById('recent-orders').innerHTML = `
    <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light"><tr><th>#</th><th>Customer</th><th>Cook</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>${d.recent_orders.map(o => `
        <tr>
          <td>#${o.id}</td>
          <td>${o.customer}</td>
          <td>${o.cook_name||''}</td>
          <td class="text-orange fw-bold">JD ${parseFloat(o.total).toFixed(2)}</td>
          <td>${statusBadge(o.status)}</td>
          <td class="text-muted" style="font-size:.82rem">${fmtDate(o.created_at)}</td>
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
