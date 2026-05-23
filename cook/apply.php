<?php
$pageTitle = 'Become a Cook';
include __DIR__ . '/../includes/header.php';
if (!$currentUser) { header('Location: /login.php?next=/cook/apply.php'); exit; }
if ($currentUser['role'] === 'cook') { header('Location: /cook/dashboard.php'); exit; }
?>

<div class="container py-5" style="max-width:640px">
  <!-- Hero text -->
  <div class="text-center mb-5">
    <div style="width:80px;height:80px;background:var(--hp-orange);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center">
      <i class="fas fa-hat-chef" style="color:#fff;font-size:2rem"></i>
    </div>
    <h2 class="fw-bold">Become a Homeplate Cook</h2>
    <p class="text-muted">Turn your cooking skills into income. Fill out the form below and our team will verify your application within 24–48 hours.</p>
  </div>

  <!-- Benefits -->
  <div class="row g-3 mb-4">
    <?php
    $benefits = [
      ['fa-coins','Earn Money','Set your own prices and earn from every order.'],
      ['fa-clock','Flexible Hours','Cook on your own schedule.'],
      ['fa-shield-check','Verified Badge','Build trust with a verified cook badge.'],
    ];
    foreach ($benefits as $b): ?>
    <div class="col-md-4 text-center">
      <div class="p-3">
        <i class="fas <?= $b[0] ?> fa-2x mb-2 text-orange"></i>
        <div class="fw-bold"><?= $b[1] ?></div>
        <div class="text-muted" style="font-size:.85rem"><?= $b[2] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Application form -->
  <div class="card-hp p-4">
    <h5 class="fw-bold mb-3">Cook Application</h5>
    <div id="form-msg" class="alert d-none"></div>

    <form id="apply-form" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">About You / Bio *</label>
        <textarea class="form-control" id="bio" rows="3"
          placeholder="Tell us about your cooking experience, what you love to cook, and why you want to join Homeplate…" required></textarea>
        <div class="form-text">Minimum 20 characters. This will appear on your public profile.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Specialty</label>
        <input type="text" class="form-control" id="specialty" placeholder="e.g. Traditional Jordanian cuisine, Pastries, BBQ">
      </div>
      <div class="mb-4">
        <label class="form-label">ID Document <span class="text-muted fw-normal">(optional but speeds up verification)</span></label>
        <input type="file" class="form-control" id="id-doc" accept="image/*,.pdf">
        <div class="form-text">Accepted: JPG, PNG, PDF. Max 5MB. Kept strictly confidential.</div>
      </div>
      <button type="submit" class="btn-hp btn w-100 py-2" id="apply-btn">
        <span id="apply-txt">Submit Application</span>
        <span id="apply-spin" class="spinner-border spinner-border-sm d-none"></span>
      </button>
    </form>
  </div>
</div>

<?php
$extraJs = <<<'JS'
document.getElementById('apply-form').addEventListener('submit', async e => {
  e.preventDefault();
  const msg  = document.getElementById('form-msg');
  const btn  = document.getElementById('apply-btn');
  const spin = document.getElementById('apply-spin');
  const txt  = document.getElementById('apply-txt');
  msg.className = 'alert d-none';
  btn.disabled = true; spin.classList.remove('d-none'); txt.style.opacity = '.4';

  const fd = new FormData();
  fd.append('bio',       document.getElementById('bio').value.trim());
  fd.append('specialty', document.getElementById('specialty').value.trim());
  const idDoc = document.getElementById('id-doc').files[0];
  if (idDoc) fd.append('id_document', idDoc);

  try {
    await API.postForm('/api/cooks/apply', fd);
    msg.textContent = '✓ Application submitted successfully! We will review it and notify you within 24–48 hours.';
    msg.className   = 'alert alert-success';
    document.getElementById('apply-form').remove();
  } catch (ex) {
    msg.textContent = ex.message;
    msg.className   = 'alert alert-danger';
    btn.disabled = false; spin.classList.add('d-none'); txt.style.opacity = '1';
  }
});
JS;
include __DIR__ . '/../includes/footer.php';
?>
