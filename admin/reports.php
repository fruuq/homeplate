<?php
$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
if (!$currentUser || $currentUser['role'] !== 'admin') { http_response_code(403); die('Access denied'); }
?>

<div class="container-fluid py-4">
  <div class="row g-0">
    <div class="col-auto">
      <div class="sidebar">
        <div class="fw-bold text-muted mb-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em">Admin Panel</div>
        <a href="/admin/dashboard.php" class="sidebar-link"><i class="fas fa-home"></i> Dashboard</a>
        <a href="/admin/cooks.php"     class="sidebar-link"><i class="fas fa-user-check"></i> Cook Approvals</a>
        <a href="/admin/users.php"     class="sidebar-link"><i class="fas fa-users"></i> Users</a>
        <a href="/admin/reports.php"   class="sidebar-link active"><i class="fas fa-flag"></i> Reports</a>
        <div class="divider"></div>
        <a href="/" class="sidebar-link"><i class="fas fa-arrow-left"></i> Back to Site</a>
      </div>
    </div>

    <div class="col py-0 px-4">
      <h2 class="fw-bold mb-4"><i class="fas fa-flag me-2 text-orange"></i>Abuse Reports</h2>

      <div class="d-flex gap-2 mb-3">
        <button class="btn-hp btn btn-sm" onclick="loadReports('open',this)">Open</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="loadReports('resolved',this)">Resolved</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="loadReports('dismissed',this)">Dismissed</button>
      </div>

      <div id="reports-list">
        <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
async function loadReports(status, btn) {
  document.querySelectorAll('.d-flex.gap-2 button').forEach(b => b.className = 'btn btn-outline-secondary btn-sm');
  btn.className = 'btn-hp btn btn-sm';

  const list = document.getElementById('reports-list');
  list.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-orange"></div></div>';

  try {
    const data = await API.get('/api/reports?status=' + status);
    if (!data.data.length) {
      list.innerHTML = `<div class="card-hp p-5 text-center text-muted">No ${status} reports</div>`;
      return;
    }
    list.innerHTML = data.data.map(r => `
      <div class="card-hp p-4 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
          <div>
            <strong>${r.reporter_name}</strong> reported a <span class="badge bg-light text-dark border">${r.target_type}</span> (ID: ${r.target_id})
          </div>
          <small class="text-muted">${fmtDate(r.created_at)}</small>
        </div>
        <div class="mb-2"><strong>Reason:</strong> ${r.reason}</div>
        ${r.details ? `<div class="text-muted mb-2">${r.details}</div>` : ''}
        ${r.status === 'open' ? `
        <div class="d-flex gap-2 mt-2">
          <button class="btn btn-success btn-sm" onclick="resolveReport(${r.id},'resolved',this)">
            <i class="fas fa-check me-1"></i>Mark Resolved
          </button>
          <button class="btn btn-outline-secondary btn-sm" onclick="resolveReport(${r.id},'dismissed',this)">
            Dismiss
          </button>
        </div>` : `<span class="text-muted" style="font-size:.85rem">${r.status}</span>`}
      </div>
    `).join('');
  } catch (e) { list.innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`; }
}

async function resolveReport(id, action, btn) {
  btn.disabled = true;
  try {
    await API.patch('/api/reports/' + id, { action });
    Toast.success('Report ' + action);
    loadReports('open', document.querySelector('.btn-hp.btn-sm'));
  } catch (e) { Toast.error(e.message); btn.disabled = false; }
}

loadReports('open', document.querySelector('.btn-hp.btn-sm'));
JS;
include __DIR__ . '/../includes/footer.php';
?>
