/* ── Homeplate App.js ──────────────────────────────────── */

/* ── API Helper ─────────────────────────────────────────── */
const API = {
  async _req(method, url, data = null, isForm = false) {
    const opts = {
      method,
      credentials: 'same-origin',
      headers: isForm ? {} : { 'Content-Type': 'application/json' },
    };
    if (data) opts.body = isForm ? data : JSON.stringify(data);

    const res = await fetch(url, opts);
    const json = await res.json().catch(() => ({}));

    if (!res.ok) {
      const msg = json.error || json.errors
        ? (json.error || Object.values(json.errors).flat().join(', '))
        : 'Something went wrong';
      throw new Error(msg);
    }
    return json;
  },
  get:    (url)          => API._req('GET',    url),
  post:   (url, data)    => API._req('POST',   url, data),
  postForm:(url, fd)     => API._req('POST',   url, fd, true),
  put:    (url, data)    => API._req('PUT',    url, data),
  putForm:(url, fd)      => API._req('PUT',    url, fd, true),
  patch:  (url, data)    => API._req('PATCH',  url, data),
  del:    (url, data)    => API._req('DELETE', url, data),
};

/* ── Toast ──────────────────────────────────────────────── */
const Toast = {
  show(msg, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    const t = document.createElement('div');
    t.className = `hp-toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    const color = type === 'success' ? '#2ECC71' : '#E74C3C';
    t.innerHTML = `<i class="fas ${icon}" style="color:${color};font-size:1.2rem"></i><span>${msg}</span>`;
    container.appendChild(t);
    setTimeout(() => t.remove(), 4000);
  },
  success: msg => Toast.show(msg, 'success'),
  error:   msg => Toast.show(msg, 'error'),
};

/* ── Cart ───────────────────────────────────────────────── */
const Cart = {
  _key: 'hp_cart',

  get() {
    try { return JSON.parse(localStorage.getItem(this._key)) || []; }
    catch { return []; }
  },

  save(items) {
    localStorage.setItem(this._key, JSON.stringify(items));
    this._updateBadge();
  },

  add(meal) {
    const items = this.get();
    // All items must be from same cook
    if (items.length && items[0].cook_id !== meal.cook_id) {
      if (!confirm('Your cart has items from a different cook. Clear cart and add this item?')) return false;
      this.clear();
    }
    const existing = items.find(i => i.id === meal.id);
    if (existing) {
      existing.quantity++;
    } else {
      items.push({ ...meal, quantity: 1 });
    }
    this.save(items);
    return true;
  },

  remove(mealId) {
    this.save(this.get().filter(i => i.id !== mealId));
  },

  updateQty(mealId, qty) {
    const items = this.get();
    const item  = items.find(i => i.id === mealId);
    if (item) { item.quantity = Math.max(1, qty); this.save(items); }
  },

  clear() { localStorage.removeItem(this._key); this._updateBadge(); },

  total() { return this.get().reduce((s, i) => s + i.price * i.quantity, 0); },

  count() { return this.get().reduce((s, i) => s + i.quantity, 0); },

  _updateBadge() {
    const badge = document.getElementById('cart-badge');
    if (badge) {
      const c = this.count();
      badge.textContent = c;
      badge.style.display = c ? 'inline' : 'none';
    }
  },
};

/* ── Stars helper ───────────────────────────────────────── */
function renderStars(avg, count = null) {
  const full  = Math.round(avg);
  let html = '';
  for (let i = 1; i <= 5; i++) html += `<i class="fas fa-star${i <= full ? '' : '-o'}" style="color:#FFC107;font-size:.85rem"></i>`;
  if (count !== null) html += ` <small class="text-muted">(${count})</small>`;
  return html;
}

/* ── Status badge ───────────────────────────────────────── */
function statusBadge(status) {
  const labels = {
    pending: 'Pending', accepted: 'Accepted', preparing: 'Preparing',
    ready: 'Ready', out_for_delivery: 'On the way', delivered: 'Delivered', cancelled: 'Cancelled'
  };
  return `<span class="status-badge status-${status}">${labels[status] || status}</span>`;
}

/* ── Format date ────────────────────────────────────────── */
function fmtDate(d) {
  return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
}

/* ── Loader ─────────────────────────────────────────────── */
function showLoader() { const l = document.getElementById('page-loader'); if (l) l.style.display = 'flex'; }
function hideLoader() { const l = document.getElementById('page-loader'); if (l) l.style.display = 'none'; }

/* ── Notifications badge ────────────────────────────────── */
async function loadNotifBadge() {
  try {
    const data = await API.get('/api/notifications');
    const dot  = document.getElementById('notif-dot');
    if (dot) dot.style.display = data.unread > 0 ? 'block' : 'none';
  } catch {}
}

/* ── Favorites toggle ───────────────────────────────────── */
async function toggleFav(btn, mealId, mealData) {
  const active = btn.classList.contains('fav-active');
  try {
    if (active) {
      await API.del('/api/favorites', { meal_id: mealId });
      btn.classList.remove('fav-active');
      btn.style.color = '';
    } else {
      await API.post('/api/favorites', { meal_id: mealId });
      btn.classList.add('fav-active');
      btn.style.color = 'var(--hp-orange)';
    }
  } catch (e) { Toast.error(e.message); }
}

/* ── Meal card HTML ─────────────────────────────────────── */
function mealCard(m) {
  const img = m.image
    ? `<img src="/uploads/meals/${m.image}" alt="${m.title}">`
    : `<div class="img-placeholder"><i class="fas fa-utensils"></i></div>`;

  return `
  <div class="col-sm-6 col-lg-4">
    <div class="card-hp meal-card h-100">
      <a href="/meal.php?id=${m.id}">${img}</a>
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <a href="/meal.php?id=${m.id}" class="fw-bold text-dark" style="font-size:.95rem">${m.title}</a>
          <button onclick="toggleFav(this,${m.id})" class="btn p-0 ms-1 fav-btn" style="color:#ccc;background:none;border:none">
            <i class="fas fa-heart"></i>
          </button>
        </div>
        <div class="stars mb-1">${renderStars(m.rating_avg, m.rating_count)}</div>
        <p class="cook-name mb-2"><i class="fas fa-user-circle me-1"></i><a href="/cook.php?id=${m.cook_id}" class="text-muted">${m.cook_name}</a></p>
        <div class="mt-auto d-flex justify-content-between align-items-center">
          <span class="price">JD ${parseFloat(m.price).toFixed(2)}</span>
          <button class="btn-hp btn" onclick="addToCart(${JSON.stringify(JSON.stringify(m))})">
            <i class="fas fa-cart-plus me-1"></i>Add
          </button>
        </div>
      </div>
    </div>
  </div>`;
}

function addToCart(mealJson) {
  const meal = JSON.parse(mealJson);
  if (Cart.add(meal)) Toast.success(`${meal.title} added to cart!`);
}

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  Cart._updateBadge();
  loadNotifBadge();
});
