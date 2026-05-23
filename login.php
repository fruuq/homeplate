


<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

startSession();
if (currentUser()) {
    header('Location: /');
    exit;
}
$pageTitle = 'Log In';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="auth-card">
    <div class="card-hp">
      <div class="card-body">
        <div class="auth-logo"><i class="fas fa-utensils me-1"></i>Home<span>plate</span></div>
        <h5 class="text-center mb-4 fw-bold">Welcome back</h5>

        <div id="form-error" class="alert alert-danger d-none"></div>

        <form id="login-form" novalidate>
          <div class="mb-3">
            <label class="form-label">Email address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
              <input type="email" class="form-control" id="email" placeholder="you@example.com" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
              <input type="password" class="form-control" id="password" placeholder="Your password" required>
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd(this)"><i class="fas fa-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="btn-hp btn w-100 py-2" id="login-btn">
            <span id="btn-text">Log In</span>
            <span id="btn-spin" class="spinner-border spinner-border-sm d-none"></span>
          </button>
        </form>

        <div class="text-center mt-4" style="font-size:.9rem">
          Don't have an account? <a href="/register.php" class="text-orange fw-bold">Sign up</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
document.getElementById('login-form').addEventListener('submit', async e => {
  e.preventDefault();
  const err = document.getElementById('form-error');
  const btn = document.getElementById('login-btn');
  const spin = document.getElementById('btn-spin');
  const txt  = document.getElementById('btn-text');
  err.classList.add('d-none');
  btn.disabled = true; spin.classList.remove('d-none'); txt.textContent = 'Logging in…';

  try {
    const data = await API.post('/auth/login', {
      email:    document.getElementById('email').value.trim(),
      password: document.getElementById('password').value,
    });
    const dest = new URLSearchParams(location.search).get('next') || '/';
    window.location = dest;
  } catch (ex) {
    err.textContent = ex.message;
    err.classList.remove('d-none');
    btn.disabled = false; spin.classList.add('d-none'); txt.textContent = 'Log In';
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
