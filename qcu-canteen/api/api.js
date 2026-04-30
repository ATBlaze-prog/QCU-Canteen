// ============================================================
// api.js — QCU Canteen (DATA ONLY VERSION)
// ============================================================

const API_BASE = (() => {
  const path = window.location.pathname;
  const frontPos = path.indexOf('/front/');
  if (frontPos !== -1) {
    const basePath = path.substring(0, frontPos) || '/';
    return `${window.location.origin}${basePath.replace(/\/\/$/, '')}/api`;
  }
  return `${window.location.origin}/api`;
})();

console.log('API_BASE:', API_BASE);

// ---- CART (KEEP THIS) ----
function getCart() {
  try { return JSON.parse(localStorage.getItem('qcu_cart')) || []; }
  catch { return []; }
}

function saveCart(c) {
  localStorage.setItem('qcu_cart', JSON.stringify(c));
}

function getCartCount() {
  return getCart().length;
}

// ---- DISHES API ----
async function apiGetDishes(store = '', day = '') {
  const p = new URLSearchParams();
  if (store) p.set('store', store);
  if (day)   p.set('day', day);

  const res = await fetch(`${API_BASE}/dishes.php${p.toString() ? '?' + p : ''}`);
  return res.json();
}

// ---- ORDERS API ----
async function apiGetOrders(filters = {}) {
  const p = new URLSearchParams(filters).toString();
  const res = await fetch(`${API_BASE}/orders.php${p ? '?' + p : ''}`);
  return res.json();
}

async function apiPlaceOrder(data) {
  const res = await fetch(`${API_BASE}/orders.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });

  const text = await res.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch (err) {
    throw new Error(`Invalid server response (${res.status}): ${text}`);
  }

  if (!res.ok) {
    throw new Error(json.error || `Server error: ${res.status}`);
  }

  return json;
}

async function apiUpdateOrderStatus(id, status) {
  const res = await fetch(`${API_BASE}/orders.php?id=${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ status }),
  });
  return res.json();
}

// ---- UI HELPERS ----
function updateNavCartBadge() {
  const b = document.getElementById('cart-badge');
  if (b) b.textContent = getCartCount();
}