<?php
/**
 * footer.php — Shared footer, cart offcanvas, toast, Bootstrap JS.
 * config.php must already be loaded (session started).
 */
$_fu = currentUser();
?>

<!-- ===== CART OFFCANVAS ===== -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" style="width:420px">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title fw-bold"><i class="bi bi-cart3 me-2"></i>Your Cart</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column p-0">
    <div class="flex-grow-1 p-3" id="cart-items-container"></div>
    <div class="p-3 border-top">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-bold fs-5">Total</span>
        <span class="fw-bold fs-5" id="cart-total-display">&#8369;0.00</span>
      </div>
      <button class="btn btn-dark-qcu w-100 py-2" onclick="doCheckout()">
        <i class="bi bi-bag-check me-2"></i>Checkout
      </button>
    </div>
  </div>
</div>

<!-- ===== TOAST ===== -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="liveToast" class="toast align-items-center text-white border-0 bg-dark" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-semibold"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="py-4 mt-auto">
  <div class="container text-center">
    <p class="mb-1">&copy; <?= date('Y') ?> QCU Canteen &mdash; Quezon City University</p>
    <div class="d-flex justify-content-center gap-3 mt-2">
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="canteen.php">Canteen</a>
      <a href="login.php">Login</a>
    </div>
  </div>
</footer>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../api/api.js?v=2"></script>
<script>
/* ---- Cart helpers ---- */
function getCart()      { try { return JSON.parse(sessionStorage.getItem('qcu_cart')) || []; } catch { return []; } }
function saveCart(c)    { sessionStorage.setItem('qcu_cart', JSON.stringify(c)); }
function getCartCount() { return getCart().length; }

function updateCartBadge() {
  const b = document.getElementById('cart-badge');
  if (b) b.textContent = getCartCount();
}

function showToast(msg, type = 'dark') {
  const el = document.getElementById('liveToast');
  if (!el) return;
  el.querySelector('.toast-body').textContent = msg;
  el.className = `toast align-items-center text-white border-0 bg-${type}`;
  new bootstrap.Toast(el, { delay: 2800 }).show();
}

/* ---- Render cart offcanvas ---- */
function renderCartOffcanvas() {
  const cart      = getCart();
  const container = document.getElementById('cart-items-container');
  const totalEl   = document.getElementById('cart-total-display');
  if (!container) return;

  if (!cart.length) {
    container.innerHTML = `<div class="text-center py-5 text-muted">
      <i class="bi bi-cart-x fs-1 d-block mb-3"></i>Your cart is empty.</div>`;
    if (totalEl) totalEl.textContent = '₱0.00';
    return;
  }

  let total = 0;
  container.innerHTML = cart.map((item, i) => {
    total += parseFloat(item.price);
    return `<div class="cart-item-row d-flex justify-content-between align-items-start">
      <div>
        <div class="fw-semibold">${item.name}</div>
        <div class="text-muted small">${item.storeName}</div>
        <span class="badge bg-secondary" style="font-size:.65rem">${item.category}</span>
      </div>
      <div class="d-flex align-items-center gap-2 ms-3">
        <span class="fw-bold text-nowrap">₱${parseFloat(item.price).toFixed(2)}</span>
        <button class="btn btn-sm btn-dark-qcu" onclick="removeCartItem(${i})">
          <i class="bi bi-trash3"></i>
        </button>
      </div>
    </div>`;
  }).join('');
  if (totalEl) totalEl.textContent = `₱${total.toFixed(2)}`;
}

function removeCartItem(idx) {
  const cart = getCart();
  cart.splice(idx, 1);
  saveCart(cart);
  updateCartBadge();
  renderCartOffcanvas();
}

/* ---- Checkout ---- */
async function doCheckout() {
  const cart = getCart();
  if (!cart.length) { showToast('Your cart is empty!', 'danger'); return; }

  <?php if (!$_fu): ?>
    showToast('Please log in to place an order.', 'warning');
    setTimeout(() => window.location.href = 'login.php', 1000);
    return;
  <?php else: ?>
    const total        = cart.reduce((s, i) => s + parseFloat(i.price), 0);
    const primaryStore = cart[0].storeName;

    try {
      const result = await apiPlaceOrder({
        userId:    <?= (int)$_fu['id'] ?>,
        customer:  <?= json_encode($_fu['full_name']) ?>,
        role:      <?= json_encode($_fu['user_type'] ?? 'Student') ?>,
        studentId: <?= json_encode($_fu['student_id'] ?? '') ?>,
        store:     primaryStore,
        items:     cart.map(i => ({ name: i.name, category: i.category, price: i.price })),
        total,
      });

      if (result.error) { showToast(result.error, 'danger'); return; }

      saveCart([]);
      updateCartBadge();
      renderCartOffcanvas();

      const oc = bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas'));
      if (oc) oc.hide();

      showToast(`Order placed! Code: ${result.orderCode} &#127881;`, 'success');
      setTimeout(() => window.location.href = 'profile.php', 2000);
    } catch(e) {
      console.error('Checkout error:', e);
      showToast('Could not place order. Check server connection.', 'danger');
    }
  <?php endif; ?>
}

/* ---- Init on every page ---- */
updateCartBadge();
renderCartOffcanvas();
</script>
