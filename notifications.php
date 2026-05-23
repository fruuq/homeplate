<?php
$pageTitle = 'Notifications';
include __DIR__ . '/includes/header.php';
if (!$currentUser) { header('Location: /login.php'); exit; }
?>

<div class="container py-4" style="max-width:700px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fas fa-bell me-2 text-orange"></i>Notifications</h2>
    <button class="btn-hp-outline btn btn-sm" onclick="markAllRead()">Mark All Read</button>
  </div>
  <div id="notif-list">
    <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
async function loadNotifications() {
  try {
    const data = await API.get('/api/notifications');
    const list = document.getElementById('notif-list');

    if (!data.data.length) {
      list.innerHTML = `<div class="card-hp p-5 text-center text-muted">
        <i class="fas fa-bell-slash fa-3x mb-3 d-block" style="opacity:.2"></i>No notifications yet</div>`;
      return;
    }

    list.innerHTML = data.data.map(n => `
      <div class="card-hp p-3 mb-2 d-flex align-items-start gap-3 ${n.is_read?'':'border-start border-orange border-3'}">
        <div style="width:40px;height:40px;background:${n.is_read?'#f0f0f0':'var(--hp-orange)'};border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fas fa-bell" style="color:${n.is_read?'#aaa':'#fff'};font-size:.85rem"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-semibold" style="font-size:.9rem">${n.title}</div>
          ${n.body ? `<div class="text-muted" style="font-size:.83rem">${n.body}</div>` : ''}
          <div class="text-muted" style="font-size:.75rem">${fmtDate(n.created_at)}</div>
        </div>
        ${n.link ? `<a href="${n.link}" class="btn btn-outline-secondary btn-sm">View</a>` : ''}
      </div>
    `).join('');
  } catch (e) {
    document.getElementById('notif-list').innerHTML = `<div class="text-danger">${e.message}</div>`;
  }
}

async function markAllRead() {
  await API.patch('/api/notifications/read');
  await loadNotifications();
  const dot = document.getElementById('notif-dot');
  if (dot) dot.style.display = 'none';
}

loadNotifications();
JS;
include __DIR__ . '/includes/footer.php';
?>
