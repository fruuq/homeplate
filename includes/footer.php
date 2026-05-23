<?php // includes/footer.php ?>

<footer style="background:var(--hp-dark);color:#aaa;margin-top:4rem;padding:3rem 0 1.5rem">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="navbar-brand mb-2" style="color:#fff;font-size:1.3rem">
          <i class="fas fa-utensils me-1" style="color:var(--hp-orange)"></i>Home<span style="color:var(--hp-orange)">plate</span>
        </div>
        <p style="font-size:.9rem">Connecting home cooks with people who love homemade food. Fresh, local, and made with love.</p>
      </div>
      <div class="col-md-2">
        <h6 style="color:#fff;font-weight:700">Platform</h6>
        <ul class="list-unstyled" style="font-size:.9rem">
          <li><a href="/meals.php" style="color:#aaa">Browse Meals</a></li>
          <li><a href="/cook/apply.php" style="color:#aaa">Become a Cook</a></li>
          <li><a href="/register.php" style="color:#aaa">Sign Up</a></li>
        </ul>
      </div>
      <div class="col-md-2">
        <h6 style="color:#fff;font-weight:700">Account</h6>
        <ul class="list-unstyled" style="font-size:.9rem">
          <li><a href="/orders.php" style="color:#aaa">My Orders</a></li>
          <li><a href="/favorites.php" style="color:#aaa">Favorites</a></li>
          <li><a href="/profile.php" style="color:#aaa">Profile</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 style="color:#fff;font-weight:700">Contact</h6>
        <p style="font-size:.9rem"><i class="fas fa-envelope me-2" style="color:var(--hp-orange)"></i>support@homeplate.com</p>
        <p style="font-size:.9rem"><i class="fas fa-phone me-2" style="color:var(--hp-orange)"></i>+962 7X XXX XXXX</p>
      </div>
    </div>
    <div class="divider" style="border-top:1px solid rgba(255,255,255,.1);margin-top:2rem"></div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size:.85rem">
      <span>&copy; 2025 Homeplate. Software Engineering Project.</span>
      <span>Supervised by Prof. Amjad Hudaib</span>
    </div>
  </div>
</footer>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
async function logout() {
  try {
    await API.post('/auth/logout');
    window.location = '/login.php';
  } catch { window.location = '/login.php'; }
}
</script>
<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
</body>
</html>
