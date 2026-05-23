<?php
$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
if (!$currentUser) { header('Location: /login.php'); exit; }
?>

<div class="container py-4" style="max-width:700px">
  <h2 class="fw-bold mb-4"><i class="fas fa-user-circle me-2 text-orange"></i>My Profile</h2>

  <div class="card-hp p-4 mb-4">
    <div class="d-flex align-items-center gap-4 mb-4">
      <div id="avatar-display" style="width:80px;height:80px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--hp-orange);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;font-weight:800">
        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
      </div>
      <div>
        <h5 class="mb-0 fw-bold" id="disp-name"><?= htmlspecialchars($currentUser['name']) ?></h5>
        <div class="text-muted"><?= $currentUser['email'] ?></div>
        <span class="badge bg-light text-dark border mt-1"><?= ucfirst($currentUser['role']) ?></span>
      </div>
    </div>

    <div id="form-msg" class="alert d-none"></div>

    <form id="profile-form" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" id="p-name" value="<?= htmlspecialchars($currentUser['name']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone</label>
          <input type="tel" class="form-control" id="p-phone">
        </div>
        <div class="col-12">
          <label class="form-label">Profile Photo</label>
          <input type="file" class="form-control" id="p-avatar" accept="image/*">
        </div>
        <div class="col-12">
          <button type="submit" class="btn-hp btn">Save Changes</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Password change -->
  <div class="card-hp p-4 mb-4">
    <h6 class="fw-bold mb-3"><i class="fas fa-lock me-2 text-orange"></i>Change Password</h6>
    <div id="pwd-msg" class="alert d-none"></div>
    <form id="pwd-form">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Current Password</label>
          <input type="password" class="form-control" id="pwd-current" placeholder="Your current password">
        </div>
        <div class="col-12">
          <label class="form-label">New Password</label>
          <input type="password" class="form-control" id="pwd-new" placeholder="Min. 8 characters">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-outline-dark">Update Password</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Danger zone -->
  <div class="card-hp p-4 border-danger" style="border:2px solid #f8d7da!important">
    <h6 class="fw-bold mb-3 text-danger"><i class="fas fa-triangle-exclamation me-2"></i>Danger Zone</h6>
    <div class="d-flex gap-3 flex-wrap">
      <button class="btn btn-outline-warning btn-sm" onclick="deactivateAccount()">Deactivate Account</button>
      <button class="btn btn-outline-danger btn-sm" onclick="deleteAccount()">Delete Account Permanently</button>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
// Load full profile
API.get('/api/profile').then(p => {
  if (p.phone) document.getElementById('p-phone').value = p.phone;
  if (p.avatar) {
    document.getElementById('avatar-display').innerHTML = `<img src="/uploads/avatars/${p.avatar}" style="width:100%;height:100%;object-fit:cover">`;
  }
});

document.getElementById('profile-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData();
  fd.append('name',  document.getElementById('p-name').value.trim());
  fd.append('phone', document.getElementById('p-phone').value.trim());
  const avatarFile = document.getElementById('p-avatar').files[0];
  if (avatarFile) fd.append('avatar', avatarFile);

  const msg = document.getElementById('form-msg');
  msg.className = 'alert d-none';
  try {
    await API.putForm('/api/profile', fd);
    msg.textContent = 'Profile updated successfully!';
    msg.className   = 'alert alert-success';
  } catch (ex) {
    msg.textContent = ex.message;
    msg.className   = 'alert alert-danger';
  }
});

document.getElementById('pwd-form').addEventListener('submit', async e => {
  e.preventDefault();
  const msg = document.getElementById('pwd-msg');
  msg.className = 'alert d-none';
  try {
    await API.put('/api/profile/password', {
      current_password: document.getElementById('pwd-current').value,
      new_password:     document.getElementById('pwd-new').value,
    });
    msg.textContent = 'Password updated. Please log in again.';
    msg.className   = 'alert alert-success';
    setTimeout(() => window.location = '/login.php', 2000);
  } catch (ex) {
    msg.textContent = ex.message;
    msg.className   = 'alert alert-danger';
  }
});

async function deactivateAccount() {
  if (!confirm('Deactivate your account? You can reactivate later.')) return;
  try { await API.del('/api/profile', { action: 'deactivate' }); window.location = '/login.php'; }
  catch (e) { Toast.error(e.message); }
}
async function deleteAccount() {
  if (!confirm('Permanently delete your account? This cannot be undone.')) return;
  const confirm2 = prompt('Type "DELETE" to confirm:');
  if (confirm2 !== 'DELETE') { Toast.error('Cancelled'); return; }
  try { await API.del('/api/profile', { action: 'delete' }); window.location = '/'; }
  catch (e) { Toast.error(e.message); }
}
JS;
include __DIR__ . '/includes/footer.php';
?>
