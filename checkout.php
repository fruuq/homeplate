<?php
$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
if (!$currentUser) { header('Location: /login.php?next=/checkout.php'); exit; }
?>

<div class="container py-4" style="max-width:800px">
  <h2 class="fw-bold mb-4"><i class="fas fa-shopping-cart me-2 text-orange"></i>Your Cart</h2>

  <!-- Empty state -->
  <div id="empty-cart" class="text-center py-5 d-none">
    <i class="fas fa-cart-xmark fa-3x mb-3 d-block" style="color:#ddd"></i>
    <h5 class="text-muted">Your cart is empty</h5>
    <a href="/meals.php" class="btn-hp btn mt-3 px-4">Browse Meals</a>
  </div>

  <!-- Cart content -->
  <div id="cart-content">
    <!-- Cart items -->
    <div class="card-hp p-0 mb-4 overflow-hidden">
      <div class="p-3 border-bottom" style="background:var(--hp-light)">
        <strong><i class="fas fa-basket-shopping me-2"></i>Order Items</strong>
      </div>
      <div id="cart-items" class="p-3"></div>
    </div>

    <div class="row g-4">
      <!-- Delivery details -->
      <div class="col-lg-7">
        <div class="card-hp p-4">
          <h6 class="fw-bold mb-3"><i class="fas fa-location-dot me-2 text-orange"></i>Delivery Details</h6>
          <div class="mb-3">
            <label class="form-label">Delivery Address</label>
            <textarea class="form-control" id="delivery-address" rows="2" placeholder="Street, building, floor…" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Special Notes <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" class="form-control" id="notes" placeholder="No onions, extra spicy…">
          </div>

          <h6 class="fw-bold mb-3 mt-3"><i class="fas fa-credit-card me-2 text-orange"></i>Payment Method</h6>
          <div class="d-flex gap-3">
            <label class="border rounded p-3 pointer flex-fill text-center" style="cursor:pointer">
              <input type="radio" name="payment" value="stripe" class="d-none" checked>
              <i class="fab fa-stripe fa-2x d-block mb-1" style="color:#635BFF"></i>
              <small>Stripe</small>
            </label>
            <label class="border rounded p-3 pointer flex-fill text-center" style="cursor:pointer">
              <input type="radio" name="payment" value="paypal" class="d-none">
              <i class="fab fa-paypal fa-2x d-block mb-1" style="color:#003087"></i>
              <small>PayPal</small>
            </label>
            <label class="border rounded p-3 pointer flex-fill text-center" style="cursor:pointer">
              <input type="radio" name="payment" value="cash" class="d-none">
              <i class="fas fa-money-bill-wave fa-2x d-block mb-1" style="color:#2ECC71"></i>
              <small>Cash</small>
            </label>
          </div>
          <script>
            document.querySelectorAll('input[name="payment"]').forEach(r => {
              r.addEventListener('change', () => {
                document.querySelectorAll('label.border').forEach(l => l.style.borderColor = '');
                r.closest('label').style.borderColor = 'var(--hp-orange)';
              });
            });
            document.querySelector('input[value="stripe"]').closest('label').style.borderColor = 'var(--hp-orange)';
          </script>
        </div>
      </div>

      <!-- Order summary -->
      <div class="col-lg-5">
        <div class="card-hp p-4">
          <h6 class="fw-bold mb-3"><i class="fas fa-receipt me-2 text-orange"></i>Order Summary</h6>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span id="summary-sub">JD 0.00</span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Delivery Fee</span>
            <span>JD 1.50</span>
          </div>
          <div class="divider"></div>
          <div class="d-flex justify-content-between mb-3">
            <strong>Total</strong>
            <strong class="text-orange" id="summary-total" style="font-size:1.2rem">JD 0.00</strong>
          </div>
          <button class="btn-hp btn w-100 py-2" id="place-btn" onclick="placeOrder()">
            <i class="fas fa-check-circle me-2"></i>Place Order
          </button>
          <div class="text-center mt-2" style="font-size:.78rem;color:var(--hp-gray)">
            <i class="fas fa-lock me-1"></i>Secured by SSL encryption
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
function renderCart() {
  const items = Cart.get();
  const empty = document.getElementById('empty-cart');
  const content = document.getElementById('cart-content');

  if (!items.length) {
    empty.classList.remove('d-none');
    content.classList.add('d-none');
    return;
  }
  empty.classList.add('d-none');
  content.classList.remove('d-none');

  const sub   = Cart.total();
  const total = sub + 1.50;

  document.getElementById('cart-items').innerHTML = items.map(item => `
    <div class="d-flex align-items-center gap-3 py-3 border-bottom" id="item-${item.id}">
      ${item.image
        ? `<img src="/uploads/meals/${item.image}" width="64" height="64" class="rounded-2 object-fit-cover">`
        : `<div style="width:64px;height:64px;background:var(--hp-light);border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="fas fa-utensils text-orange"></i></div>`
      }
      <div class="flex-grow-1">
        <div class="fw-semibold">${item.title}</div>
        <div class="text-muted" style="font-size:.85rem">JD ${parseFloat(item.price).toFixed(2)} each</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm rounded-circle" onclick="adjustQty(${item.id},-1)">−</button>
        <span class="fw-bold" style="min-width:20px;text-align:center">${item.quantity}</span>
        <button class="btn btn-outline-secondary btn-sm rounded-circle" onclick="adjustQty(${item.id},1)">+</button>
      </div>
      <div class="fw-bold" style="min-width:70px;text-align:right">JD ${(item.price * item.quantity).toFixed(2)}</div>
      <button class="btn btn-link text-danger p-0" onclick="removeItem(${item.id})"><i class="fas fa-trash"></i></button>
    </div>
  `).join('');

  document.getElementById('summary-sub').textContent   = 'JD ' + sub.toFixed(2);
  document.getElementById('summary-total').textContent = 'JD ' + total.toFixed(2);
}

function adjustQty(id, d) {
  const items = Cart.get();
  const item  = items.find(i => i.id === id);
  if (!item) return;
  if (item.quantity + d < 1) { removeItem(id); return; }
  Cart.updateQty(id, item.quantity + d);
  renderCart();
}
function removeItem(id) { Cart.remove(id); renderCart(); }

async function placeOrder() {
  const items   = Cart.get();
  const address = document.getElementById('delivery-address').value.trim();
  const notes   = document.getElementById('notes').value.trim();

  if (!address) { Toast.error('Please enter a delivery address'); return; }
  if (!items.length) { Toast.error('Cart is empty'); return; }

  const btn = document.getElementById('place-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Placing order…';

  try {
    const data = await API.post('/api/orders', {
      delivery_address: address,
      notes,
      items: items.map(i => ({ meal_id: i.id, quantity: i.quantity })),
    });
    Cart.clear();
    window.location = `/order.php?id=${data.order_id}`;
  } catch (e) {
    Toast.error(e.message);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
  }
}

renderCart();
JS;
include __DIR__ . '/includes/footer.php';
?>
