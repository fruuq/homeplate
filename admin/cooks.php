<?php
$pageTitle = 'Cook Approvals';
include __DIR__ . '/../includes/header.php';
if (!$currentUser || $currentUser['role'] !== 'admin') { http_response_code(403); die('Access denied'); }
?>

<div class="container-fluid py-4">
  <div class="row g-0">
    <div class="col-auto">
      <div class="sidebar">
        <div class="fw-bold text-muted mb-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em">Admin Panel</div>
        <a href="/admin/dashboard.php" class="sidebar-link"><i class="fas fa-home"></i> Dashboard</a>
        <a href="/admin/cooks.php"     class="sidebar-link active"><i class="fas fa-user-check"></i> Cook Approvals</a>
        <a href="/admin/users.php"     class="sidebar-link"><i class="fas fa-users"></i> Users</a>
        <a href="/admin/reports.php"   class="sidebar-link"><i class="fas fa-flag"></i> Reports</a>
        <div class="divider"></div>
        <a href="/" class="sidebar-link"><i class="fas fa-arrow-left"></i> Back to Site</a>
      </div>
    </div>

    <div class="col py-0 px-4">
      <h2 class="fw-bold mb-4"><i class="fas fa-user-check me-2 text-orange"></i>Cook Applications</h2>

      <div class="d-flex gap-2 mb-3">
        <button class="btn-hp btn btn-sm" onclick="loadCooks('pending',this)">Pending</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="loadCooks('approved',this)">Approved</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="loadCooks('rejected',this)">Rejected</button>
      </div>

      <div id="cooks-list">
        <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
async function loadCooks(status, btn) {
  document.querySelectorAll('.d-flex.gap-2 button').forEach(b => b.className = 'btn btn-outline-secondary btn-sm');
  btn.className = 'btn-hp btn btn-sm';

  const list = document.getElementById('cooks-list');
  list.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-orange"></div></div>';

  try {
    // Get all users with role=cook or pending cook applications
    // We'll fetch from users filtered by pending cook profiles
    const data = await API.get('/api/admin/users?role=cook');

    // For display, show all cook-role users with verification status
    if (!data.data.length) {
      list.innerHTML = `<div class="card-hp p-5 text-center text-muted">No ${status} applications</div>`;
      return;
    }

    list.innerHTML = `
    <div class="table-responsive card-hp">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>${data.data.map(u => `
        <tr>
          <td class="fw-semibold">${u.name}</td>
          <td class="text-muted">${u.email}</td>
          <td class="text-muted" style="font-size:.83rem">${fmtDate(u.created_at)}</td>
          <td><span class="${u.status==='active'?'verified-badge':'pending-badge'}">${u.status}</span></td>
          <td class="d-flex gap-2">
            <button class="btn btn-sm btn-success" onclick="verifyDecision(${u.id},'approved')">
              <i class="fas fa-check me-1"></i>Approve
            </button>
            <button class="btn btn-sm btn-danger" onclick="verifyDecision(${u.id},'rejected')">
              <i class="fas fa-times me-1"></i>Reject
            </button>
          </td>
        </tr>`).join('')}
      </tbody>
    </table></div>`;
  } catch (e) { list.innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`; }
}

async function verifyDecision(userId, decision) {
  const note = decision === 'rejected' ? (prompt('Rejection reason (sent to cook):') || '') : '';
  try {
    await API.patch('/api/cooks/' + userId + '/verify', { decision, note });
    Toast.success('Cook ' + decision);
    loadCooks(decision === 'approved' ? 'pending' : 'rejected', document.querySelector('.btn-hp'));
  } catch (e) { Toast.error(e.message); }
}

loadCooks('pending', document.querySelector('.btn-hp.btn-sm'));
JS;
include __DIR__ . '/../includes/footer.php';
?>
