<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

startSession();
if (currentUser()) { header('Location: /'); exit; }
$pageTitle = 'Create Account';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="auth-card" style="max-width:500px">
    <div class="card-hp">
      <div class="card-body">
        <div class="auth-logo"><i class="fas fa-utensils me-1"></i>Home<span>plate</span></div>
        <h5 class="text-center mb-4 fw-bold">Create your account</h5>

        <div id="form-error" class="alert alert-danger d-none"></div>

        <form id="reg-form" novalidate>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Full Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                <input type="text" class="form-control" id="name" placeholder="Ahmad Al-Khalidi" required>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" class="form-control" id="email" placeholder="you@example.com" required>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Phone Number <span class="text-muted fw-normal">(optional)</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                <input type="tel" class="form-control" id="phone" placeholder="07X XXX XXXX">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" class="form-control" id="password" placeholder="Min. 8 characters" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd(this)"><i class="fas fa-eye"></i></button>
              </div>
              <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn-hp btn w-100 py-2" id="reg-btn">
                <span id="btn-text">Create Account</span>
                <span id="btn-spin" class="spinner-border spinner-border-sm d-none"></span>
              </button>
            </div>
          </div>
        </form>

        <div class="text-center mt-3" style="font-size:.9rem">
          Already have an account? <a href="/login.php" class="text-orange fw-bold">Log in</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
document.getElementById('reg-form').addEventListener('submit', async e => {
  e.preventDefault();
  const err  = document.getElementById('form-error');
  const btn  = document.getElementById('reg-btn');
  const spin = document.getElementById('btn-spin');
  const txt  = document.getElementById('btn-text');
  err.classList.add('d-none');
  btn.disabled = true; spin.classList.remove('d-none'); txt.textContent = 'Creating…';

  try {
    await API.post('/auth/register', {
      name:     document.getElementById('name').value.trim(),
      email:    document.getElementById('email').value.trim(),
      phone:    document.getElementById('phone').value.trim(),
      password: document.getElementById('password').value,
    });
    window.location = '/';
  } catch (ex) {
    err.textContent = ex.message;
    err.classList.remove('d-none');
    btn.disabled = false; spin.classList.add('d-none'); txt.textContent = 'Create Account';
  }
});
function togglePwd(btn) {
  const inp = btn.previousElementSibling;
  const icon = btn.querySelector('i');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  icon.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
JS;
include __DIR__ . '/includes/footer.php';
?>
