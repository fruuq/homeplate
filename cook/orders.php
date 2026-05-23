<?php
$pageTitle = 'Received Orders';
include __DIR__ . '/../includes/header.php';
if (!$currentUser || $currentUser['role'] !== 'cook') { header('Location: /login.php'); exit; }
?>

<div class="container py-4">
  <h2 class="fw-bold mb-4"><i class="fas fa-list me-2 text-orange"></i>Received Orders</h2>

  <div class="d-flex gap-2 mb-4 flex-wrap">
    <button class="btn-hp btn btn-sm" onclick="filterStatus('',this)">All</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('pending',this)">Pending</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('accepted',this)">Accepted</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('preparing',this)">Preparing</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('ready',this)">Ready</button>
    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('delivered',this)">Delivered</button>
  </div>

  <div id="orders-list">
    <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
let currentStatus = '';

function filterStatus(s, btn) {
  currentStatus = s;
  document.querySelectorAll('.d-flex.gap-2 button').forEach(b => b.className = 'btn btn-outline-secondary btn-sm');
  btn.className = 'btn-hp btn btn-sm';
  loadOrders();
}

const nextStatus = { pending:'accepted', accepted:'preparing', preparing:'ready', ready:'out_for_delivery', out_for_delivery:'delivered' };
const nextLabel  = { pending:'Accept', accepted:'Mark Preparing', preparing:'Mark Ready', ready:'Out for Delivery', out_for_delivery:'Mark Delivered' };

async function loadOrders() {
  const list = document.getElementById('orders-list');
  list.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-orange"></div></div>';
  const params = new URLSearchParams({ page:1 });
  if (currentStatus) params.set('status', currentStatus);
  try {
    const data = await API.get('/api/orders?' + params);
    if (!data.data.length) {
      list.innerHTML = `<div class="card-hp p-5 text-center text-muted">
        <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity:.2"></i>No orders found</div>`;
      return;
    }
    list.innerHTML = data.data.map(o => {
      const canAdvance = nextStatus[o.status];
      return `
      <div class="card-hp p-4 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <span class="fw-bold">Order #${o.id}</span> — ${o.customer_name}
            <div class="text-muted" style="font-size:.83rem">${fmtDate(o.created_at)}</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            ${statusBadge(o.status)}
            <strong class="text-orange">JD ${parseFloat(o.total).toFixed(2)}</strong>
          </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="/order.php?id=${o.id}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eye me-1"></i>Details</a>
          ${canAdvance ? `<button class="btn-hp btn btn-sm" onclick="advanceOrder(${o.id},'${nextStatus[o.status]}',this)">${nextLabel[o.status]}</button>` : ''}
        </div>
      </div>`;
    }).join('');
  } catch (e) { list.innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`; }
}

async function advanceOrder(id, newStatus, btn) {
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
  try {
    await API.patch('/api/orders/' + id + '/status', { status: newStatus });
    Toast.success('Order updated to: ' + newStatus.replace('_',' '));
    loadOrders();
  } catch (e) { Toast.error(e.message); btn.disabled = false; }
}

loadOrders();
setInterval(loadOrders, 30000); // Auto-refresh every 30s
JS;
include __DIR__ . '/../includes/footer.php';
?>
