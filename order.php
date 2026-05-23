<?php
$pageTitle = 'Order Detail';
include __DIR__ . '/includes/header.php';
if (!$currentUser) { header('Location: /login.php'); exit; }
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { header('Location: /orders.php'); exit; }
?>

<div class="container py-4" style="max-width:820px">
  <a href="/orders.php" class="btn-hp-outline btn btn-sm mb-4">
    <i class="fas fa-arrow-left me-1"></i>My Orders
  </a>
  <div id="order-detail">
    <div class="text-center py-5"><div class="spinner-border text-orange"></div></div>
  </div>
</div>

<?php
$extraJs = <<<JS
const orderId = {$orderId};

const statusSteps = ['pending','accepted','preparing','ready','out_for_delivery','delivered'];
const stepLabels  = ['Placed','Accepted','Preparing','Ready','On the Way','Delivered'];
const stepIcons   = ['fa-circle-check','fa-handshake','fa-fire','fa-bag-shopping','fa-truck','fa-house-circle-check'];

async function loadOrder() {
  try {
    const o = await API.get('/api/orders/' + orderId);

    const stepIdx = statusSteps.indexOf(o.status);
    const isCancelled = o.status === 'cancelled';

    const progressHtml = isCancelled
      ? `<div class="alert alert-danger"><i class="fas fa-ban me-2"></i>Order Cancelled${o.cancel_reason ? ': ' + o.cancel_reason : ''}</div>`
      : `<div class="d-flex justify-content-between mb-4 position-relative">
          <div style="position:absolute;top:22px;left:0;right:0;height:3px;background:#eee;z-index:0"></div>
          <div style="position:absolute;top:22px;left:0;width:${(stepIdx/(statusSteps.length-1))*100}%;height:3px;background:var(--hp-orange);z-index:0;transition:width .5s"></div>
          ${statusSteps.map((s,i) => `
            <div class="text-center position-relative" style="z-index:1">
              <div style="width:44px;height:44px;border-radius:50%;margin:0 auto 6px;
                background:${i<=stepIdx?'var(--hp-orange)':'#eee'};
                display:flex;align-items:center;justify-content:center;
                transition:background .3s">
                <i class="fas ${stepIcons[i]}" style="color:${i<=stepIdx?'#fff':'#aaa'};font-size:.9rem"></i>
              </div>
              <div style="font-size:.72rem;font-weight:${i===stepIdx?700:400};color:${i<=stepIdx?'var(--hp-orange)':'#aaa'}">${stepLabels[i]}</div>
            </div>
          `).join('')}
        </div>`;

    document.getElementById('order-detail').innerHTML = `
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
      <div>
        <h3 class="fw-bold mb-0">Order #${o.id}</h3>
        <div class="text-muted" style="font-size:.9rem">${fmtDate(o.created_at)}</div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        ${statusBadge(o.status)}
        ${o.status === 'pending' ? `<button class="btn btn-outline-danger btn-sm" onclick="cancelOrder()">Cancel</button>` : ''}
      </div>
    </div>

    <!-- Progress -->
    <div class="card-hp p-4 mb-3">
      <h6 class="fw-bold mb-3"><i class="fas fa-route me-2 text-orange"></i>Order Progress</h6>
      ${progressHtml}
    </div>

    <div class="row g-3">
      <!-- Items -->
      <div class="col-md-7">
        <div class="card-hp p-4">
          <h6 class="fw-bold mb-3"><i class="fas fa-burger me-2 text-orange"></i>Items</h6>
          ${o.items.map(i => `
            <div class="d-flex align-items-center gap-3 py-2 border-bottom">
              ${i.image ? `<img src="/uploads/meals/${i.image}" width="52" height="52" class="rounded-2 object-fit-cover">` : '<div style="width:52px;height:52px;background:var(--hp-light);border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="fas fa-utensils text-orange"></i></div>'}
              <div class="flex-grow-1">
                <div class="fw-semibold">${i.title}</div>
                <div class="text-muted" style="font-size:.82rem">×${i.quantity} · JD ${parseFloat(i.unit_price).toFixed(2)}</div>
              </div>
              <div class="fw-bold">JD ${(i.unit_price*i.quantity).toFixed(2)}</div>
            </div>
          `).join('')}
          <div class="divider"></div>
          <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span>JD ${parseFloat(o.subtotal).toFixed(2)}</span></div>
          <div class="d-flex justify-content-between"><span class="text-muted">Delivery</span><span>JD ${parseFloat(o.delivery_fee).toFixed(2)}</span></div>
          <div class="d-flex justify-content-between mt-1"><strong>Total</strong><strong class="text-orange">JD ${parseFloat(o.total).toFixed(2)}</strong></div>
        </div>
      </div>

      <!-- Info -->
      <div class="col-md-5">
        <div class="card-hp p-4 mb-3">
          <h6 class="fw-bold mb-2"><i class="fas fa-location-dot me-2 text-orange"></i>Delivery Address</h6>
          <p class="text-muted mb-0">${o.delivery_address}</p>
          ${o.notes ? `<hr class="my-2"><small class="text-muted"><i class="fas fa-note-sticky me-1"></i>${o.notes}</small>` : ''}
        </div>
        <div class="card-hp p-4 mb-3">
          <h6 class="fw-bold mb-2"><i class="fas fa-credit-card me-2 text-orange"></i>Payment</h6>
          <div class="text-muted">${o.payment?.gateway?.toUpperCase() || 'N/A'} — <span class="status-badge status-${o.payment?.status || 'pending'}">${o.payment?.status || 'pending'}</span></div>
        </div>
        <div class="card-hp p-4">
          <h6 class="fw-bold mb-2"><i class="fas fa-user-circle me-2 text-orange"></i>Cook</h6>
          <div class="fw-semibold">${o.cook_name}</div>
          <a href="/messages.php?with=${o.cook_id}" class="btn-hp-outline btn btn-sm mt-2">
            <i class="fas fa-comment-dots me-1"></i>Message Cook
          </a>
        </div>
      </div>
    </div>

    <!-- Review section -->
    ${o.status === 'delivered' ? `
    <div class="card-hp p-4 mt-3" id="review">
      <h6 class="fw-bold mb-3"><i class="fas fa-star me-2 text-orange"></i>Leave a Review</h6>
      <div id="review-form">
        <div class="d-flex gap-2 mb-3" id="star-row">
          ${[1,2,3,4,5].map(n => `<i class="fas fa-star fa-2x pointer" data-star="${n}" style="color:#ddd;transition:color .1s" onclick="setRating(${n})" onmouseover="hoverRating(${n})" onmouseout="resetHover()"></i>`).join('')}
        </div>
        <textarea class="form-control mb-3" id="review-comment" rows="3" placeholder="Tell us about your experience…"></textarea>
        <button class="btn-hp btn" onclick="submitReview(${o.id})">Submit Review</button>
      </div>
    </div>` : ''}
    `;

  } catch (e) {
    document.getElementById('order-detail').innerHTML = `<div class="text-danger text-center py-4">${e.message}</div>`;
  }
}

let selectedRating = 0;
function setRating(n) { selectedRating = n; hoverRating(n); }
function hoverRating(n) {
  document.querySelectorAll('#star-row i').forEach((s,i) => {
    s.style.color = i < n ? '#FFC107' : '#ddd';
  });
}
function resetHover() {
  if (selectedRating) hoverRating(selectedRating);
  else document.querySelectorAll('#star-row i').forEach(s => s.style.color = '#ddd');
}

async function submitReview(orderId) {
  if (!selectedRating) { Toast.error('Please select a rating'); return; }
  try {
    await API.post('/api/reviews', {
      order_id: orderId,
      rating:   selectedRating,
      comment:  document.getElementById('review-comment').value.trim(),
    });
    Toast.success('Review submitted! Thank you.');
    document.getElementById('review-form').innerHTML = '<div class="alert alert-success">Thanks for your review!</div>';
  } catch (e) { Toast.error(e.message); }
}

async function cancelOrder() {
  if (!confirm('Cancel this order? This is only possible within 5 minutes of placing.')) return;
  const reason = prompt('Reason (optional):') || '';
  try {
    await API.del('/api/orders/' + orderId, { reason });
    Toast.success('Order cancelled');
    loadOrder();
  } catch (e) { Toast.error(e.message); }
}

loadOrder();
JS;
include __DIR__ . '/includes/footer.php';
?>
