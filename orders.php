<?php
$pageTitle = 'My Orders';
include __DIR__ . '/includes/header.php';
if (!$currentUser) { header('Location: /login.php?next=/orders.php'); exit; }
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fas fa-receipt me-2 text-orange"></i>My Orders</h2>
    <a href="/meals.php" class="btn-hp btn"><i class="fas fa-plus me-1"></i>New Order</a>
  </div>

  <!-- Status filter tabs -->
  <div class="d-flex gap-2 flex-wrap mb-4">
    <button class="btn-hp btn btn-sm" onclick="filterStatus('',this)">All</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('pending',this)">Pending</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('preparing',this)">Preparing</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('delivered',this)">Delivered</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('cancelled',this)">Cancelled</button>
  </div>

  <div id="orders-list">
    <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
  </div>

  <div class="d-flex justify-content-center mt-4" id="pagination"></div>
</div>

<?php
$extraJs = <<<JS
let currentStatus = '';
let currentPage   = 1;

function filterStatus(status, btn) {
  currentStatus = status;
  currentPage   = 1;
  document.querySelectorAll('.d-flex.gap-2 button').forEach(b => {
    b.className = 'btn btn-outline-secondary btn-sm';
  });
  btn.className = 'btn-hp btn btn-sm';
  loadOrders();
}

async function loadOrders() {
  const list = document.getElementById('orders-list');
  list.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-orange"></div></div>';

  const params = new URLSearchParams({ page: currentPage });
  if (currentStatus) params.set('status', currentStatus);

  try {
    const data = await API.get('/api/orders?' + params);
    if (!data.data.length) {
      list.innerHTML = `<div class="card-hp p-5 text-center text-muted">
        <i class="fas fa-receipt fa-3x mb-3 d-block" style="opacity:.2"></i>
        No orders found.
        <br><a href="/meals.php" class="btn-hp btn mt-3">Browse Meals</a>
      </div>`;
      return;
    }

    list.innerHTML = data.data.map(o => `
      <div class="card-hp p-4 mb-3 d-flex flex-wrap align-items-center gap-3">
        <div>
          <div class="fw-bold">#${o.id} — ${o.cook_name}</div>
          <div class="text-muted" style="font-size:.85rem">${fmtDate(o.created_at)}</div>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3 flex-wrap">
          ${statusBadge(o.status)}
          <strong class="text-orange">JD ${parseFloat(o.total).toFixed(2)}</strong>
          <a href="/order.php?id=${o.id}" class="btn-hp-outline btn btn-sm">View</a>
          ${o.status === 'pending' ? `<button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(${o.id})">Cancel</button>` : ''}
          ${o.status === 'delivered' ? `<a href="/order.php?id=${o.id}#review" class="btn btn-outline-warning btn-sm"><i class="fas fa-star me-1"></i>Review</a>` : ''}
        </div>
      </div>
    `).join('');

    buildPagination(Math.ceil(data.total / 20));
  } catch (e) {
    list.innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`;
  }
}

async function cancelOrder(id) {
  if (!confirm('Cancel this order? This can only be done within 5 minutes of placing.')) return;
  const reason = prompt('Reason (optional):') || '';
  try {
    await API.del('/api/orders/' + id, { reason });
    Toast.success('Order cancelled');
    loadOrders();
  } catch (e) { Toast.error(e.message); }
}

function buildPagination(pages) {
  const pg = document.getElementById('pagination');
  if (pages <= 1) { pg.innerHTML = ''; return; }
  let html = '<ul class="pagination">';
  for (let i = 1; i <= pages; i++)
    html += `<li class="page-item ${i===currentPage?'active':''}"><a class="page-link pointer" onclick="gotoPage(${i})">${i}</a></li>`;
  pg.innerHTML = html + '</ul>';
}
function gotoPage(p) { currentPage = p; loadOrders(); window.scrollTo(0,0); }

loadOrders();
JS;
include __DIR__ . '/includes/footer.php';
?>
