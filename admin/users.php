<?php
$pageTitle = 'User Management';
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
        <a href="/admin/users.php"     class="sidebar-link active"><i class="fas fa-users"></i> Users</a>
        <a href="/admin/reports.php"   class="sidebar-link"><i class="fas fa-flag"></i> Reports</a>
        <div class="divider"></div>
        <a href="/" class="sidebar-link"><i class="fas fa-arrow-left"></i> Back to Site</a>
      </div>
    </div>

    <div class="col py-0 px-4">
      <h2 class="fw-bold mb-4"><i class="fas fa-users me-2 text-orange"></i>User Management</h2>

      <!-- Filters -->
      <div class="card-hp p-3 mb-4">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <input type="text" class="form-control" id="search-q" placeholder="Search name or email…">
          </div>
          <div class="col-md-2">
            <select class="form-select" id="filter-role">
              <option value="">All Roles</option>
              <option value="customer">Customer</option>
              <option value="cook">Cook</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select" id="filter-status">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="banned">Banned</option>
            </select>
          </div>
          <div class="col-auto">
            <button class="btn-hp btn" onclick="loadUsers()">Search</button>
          </div>
        </div>
      </div>

      <div id="users-list">
        <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
async function loadUsers() {
  const list = document.getElementById('users-list');
  list.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-orange"></div></div>';

  const params = new URLSearchParams();
  const q      = document.getElementById('search-q').value.trim();
  const role   = document.getElementById('filter-role').value;
  const status = document.getElementById('filter-status').value;
  if (q)      params.set('q', q);
  if (role)   params.set('role', role);
  if (status) params.set('status', status);

  try {
    const data = await API.get('/api/admin/users?' + params);
    if (!data.data.length) {
      list.innerHTML = '<div class="card-hp p-5 text-center text-muted">No users found</div>';
      return;
    }
    list.innerHTML = `
    <div class="table-responsive card-hp">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>${data.data.map(u => `
        <tr>
          <td>${u.id}</td>
          <td class="fw-semibold">${u.name}</td>
          <td class="text-muted">${u.email}</td>
          <td><span class="badge bg-light text-dark border">${u.role}</span></td>
          <td><span class="${u.status==='active'?'verified-badge':u.status==='banned'?'status-badge status-cancelled':'pending-badge'}">${u.status}</span></td>
          <td class="text-muted" style="font-size:.82rem">${fmtDate(u.created_at)}</td>
          <td>
            <select class="form-select form-select-sm" style="width:130px" onchange="setUserStatus(${u.id},this.value,this)" ${u.role==='admin'?'disabled':''}>
              <option value="active"   ${u.status==='active'  ?'selected':''}>Active</option>
              <option value="inactive" ${u.status==='inactive'?'selected':''}>Inactive</option>
              <option value="banned"   ${u.status==='banned'  ?'selected':''}>Banned</option>
            </select>
          </td>
        </tr>`).join('')}
      </tbody>
    </table></div>`;
  } catch (e) { list.innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`; }
}

async function setUserStatus(id, status, sel) {
  const orig = sel.dataset.orig || sel.value;
  sel.dataset.orig = orig;
  try {
    await API.patch('/api/admin/users/' + id, { status });
    Toast.success('User status updated');
    sel.dataset.orig = status;
  } catch (e) {
    Toast.error(e.message);
    sel.value = orig;
  }
}

document.getElementById('search-q').addEventListener('keydown', e => { if (e.key==='Enter') loadUsers(); });
loadUsers();
JS;
include __DIR__ . '/../includes/footer.php';
?>
