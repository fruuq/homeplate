<?php
$pageTitle = 'Manage Meals';
include __DIR__ . '/../includes/header.php';
if (!$currentUser || $currentUser['role'] !== 'cook') {
    header('Location: /login.php'); exit;
}
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fas fa-burger me-2 text-orange"></i>My Meals</h2>
    <button class="btn-hp btn" data-bs-toggle="modal" data-bs-target="#mealModal" onclick="openAdd()">
      <i class="fas fa-plus me-1"></i>Add Meal
    </button>
  </div>

  <div id="meals-list">
    <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="mealModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0" style="border-radius:var(--radius)">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold" id="modal-title">Add New Meal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modal-err" class="alert alert-danger d-none"></div>
        <form id="meal-form" enctype="multipart/form-data">
          <input type="hidden" id="meal-id">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Meal Title *</label>
              <input type="text" class="form-control" id="m-title" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Category</label>
              <select class="form-select" id="m-category">
                <option value="">None</option>
                <option value="1">Breakfast</option><option value="2">Lunch</option>
                <option value="3">Dinner</option><option value="4">Snacks</option>
                <option value="5">Desserts</option><option value="6">Beverages</option>
                <option value="7">Healthy</option><option value="8">Vegetarian</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Price (JD) *</label>
              <input type="number" class="form-control" id="m-price" step="0.01" min="0.01" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="m-desc" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ingredients</label>
              <input type="text" class="form-control" id="m-ingr" placeholder="Chicken, rice, spices…">
            </div>
            <div class="col-md-6">
              <label class="form-label">Allergy Info</label>
              <input type="text" class="form-control" id="m-allergy" placeholder="Contains nuts, dairy…">
            </div>
            <div class="col-12">
              <label class="form-label">Meal Image</label>
              <input type="file" class="form-control" id="m-image" accept="image/*">
              <div id="img-preview" class="mt-2"></div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-hp btn px-4" id="save-btn" onclick="saveMeal()">
          <span id="save-txt">Save Meal</span>
          <span id="save-spin" class="spinner-border spinner-border-sm d-none"></span>
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
async function loadMeals() {
  const list = document.getElementById('meals-list');
  try {
    const data = await API.get('/api/meals?cook_id=<?= $currentUser['id'] ?>&sort=created_at&page=1');
    if (!data.data.length) {
      list.innerHTML = `<div class="card-hp p-5 text-center text-muted">
        <i class="fas fa-bowl-food fa-3x mb-3 d-block" style="opacity:.2"></i>
        No meals yet. Add your first meal!
      </div>`;
      return;
    }
    list.innerHTML = `
    <div class="table-responsive card-hp">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Meal</th><th>Price</th><th>Rating</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>${data.data.map(m => `
        <tr>
          <td class="d-flex align-items-center gap-3">
            ${m.image ? `<img src="/uploads/meals/${m.image}" width="50" height="50" class="rounded-2 object-fit-cover">` : `<div style="width:50px;height:50px;background:var(--hp-light);border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="fas fa-utensils text-orange"></i></div>`}
            <div>
              <div class="fw-semibold">${m.title}</div>
              <div class="text-muted" style="font-size:.8rem">${m.category || 'Uncategorized'}</div>
            </div>
          </td>
          <td class="fw-bold text-orange">JD ${parseFloat(m.price).toFixed(2)}</td>
          <td>${renderStars(m.rating_avg, m.rating_count)}</td>
          <td>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" ${m.is_available?'checked':''} 
                     onchange="toggleAvail(${m.id},this)" style="cursor:pointer">
              <label class="form-check-label" style="font-size:.83rem">${m.is_available?'Available':'Hidden'}</label>
            </div>
          </td>
          <td class="text-end">
            <button class="btn btn-outline-secondary btn-sm me-1" onclick="openEdit(${JSON.stringify(JSON.stringify(m))})">
              <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="deleteMeal(${m.id},'${m.title}')">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>`).join('')}
      </tbody>
    </table></div>`;
  } catch (e) { list.innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`; }
}

function openAdd() {
  document.getElementById('modal-title').textContent = 'Add New Meal';
  document.getElementById('meal-id').value = '';
  document.getElementById('meal-form').reset();
  document.getElementById('img-preview').innerHTML = '';
  document.getElementById('modal-err').classList.add('d-none');
  document.getElementById('save-txt').textContent = 'Save Meal';
}

function openEdit(mJson) {
  const m = JSON.parse(mJson);
  document.getElementById('modal-title').textContent = 'Edit Meal';
  document.getElementById('meal-id').value    = m.id;
  document.getElementById('m-title').value    = m.title;
  document.getElementById('m-price').value    = m.price;
  document.getElementById('m-category').value = m.category_id || '';
  document.getElementById('m-desc').value     = m.description || '';
  document.getElementById('m-ingr').value     = m.ingredients || '';
  document.getElementById('m-allergy').value  = m.allergy_info || '';
  document.getElementById('save-txt').textContent = 'Update Meal';
  if (m.image) document.getElementById('img-preview').innerHTML = `<img src="/uploads/meals/${m.image}" height="80" class="rounded-2">`;
  new bootstrap.Modal(document.getElementById('mealModal')).show();
}

async function saveMeal() {
  const id   = document.getElementById('meal-id').value;
  const err  = document.getElementById('modal-err');
  const spin = document.getElementById('save-spin');
  const txt  = document.getElementById('save-txt');
  err.classList.add('d-none');
  spin.classList.remove('d-none');
  txt.style.opacity = '.4';

  const fd = new FormData();
  fd.append('title',        document.getElementById('m-title').value.trim());
  fd.append('price',        document.getElementById('m-price').value);
  fd.append('category_id',  document.getElementById('m-category').value);
  fd.append('description',  document.getElementById('m-desc').value.trim());
  fd.append('ingredients',  document.getElementById('m-ingr').value.trim());
  fd.append('allergy_info', document.getElementById('m-allergy').value.trim());
  const imgFile = document.getElementById('m-image').files[0];
  if (imgFile) fd.append('image', imgFile);

  try {
    if (id) {
      await API.putForm('/api/meals/' + id, fd);
      Toast.success('Meal updated!');
    } else {
      await API.postForm('/api/meals', fd);
      Toast.success('Meal added!');
    }
    bootstrap.Modal.getInstance(document.getElementById('mealModal')).hide();
    loadMeals();
  } catch (ex) {
    err.textContent = ex.message;
    err.classList.remove('d-none');
  } finally {
    spin.classList.add('d-none');
    txt.style.opacity = '1';
  }
}

async function toggleAvail(id, chk) {
  try {
    const r = await API.patch('/api/meals/' + id + '/availability');
    chk.nextElementSibling.textContent = r.is_available ? 'Available' : 'Hidden';
  } catch (e) { Toast.error(e.message); chk.checked = !chk.checked; }
}

async function deleteMeal(id, title) {
  if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;
  try {
    await API.del('/api/meals/' + id);
    Toast.success('Meal deleted');
    loadMeals();
  } catch (e) { Toast.error(e.message); }
}

// Preview image
document.getElementById('m-image').addEventListener('change', function() {
  const preview = document.getElementById('img-preview');
  const file = this.files[0];
  if (file) {
    const url = URL.createObjectURL(file);
    preview.innerHTML = `<img src="${url}" height="80" class="rounded-2">`;
  }
});

loadMeals();
JS;
include __DIR__ . '/../includes/footer.php';
?>
