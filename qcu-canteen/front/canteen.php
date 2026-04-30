<?php
require_once '../config.php';
$currentPage  = 'canteen';
$user         = currentUser();
$flashSuccess = getFlash('success');

// Default day = today (Mon–Fri), fallback to Monday on weekends
$dayMap     = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday'];
$defaultDay = $dayMap[(int)date('N')] ?? 'Monday';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QCU Canteen &mdash; Order</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<?php if ($flashSuccess): ?>
<div class="container mt-3">
  <div class="alert alert-success alert-dismissible fade show rounded-0">
    <i class="bi bi-check-circle me-2"></i><?= e($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<!-- PAGE BANNER -->
<section class="page-banner text-center">
  <div class="container">
    <h1>Order from Our Canteen</h1>
    <p class="text-white-50 mt-2 mb-0">Pick your day, choose your store, and enjoy!</p>
  </div>
</section>

<!-- DAY SELECTOR -->
<div class="bg-white border-bottom py-3">
  <div class="container d-flex align-items-center gap-3 justify-content-center flex-wrap">
    <label class="fw-semibold mb-0">Select Day:</label>
    <select class="form-select form-select-sm rounded-0 w-auto" id="day-filter" onchange="loadMenu()">
      <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day): ?>
        <option <?= $day === $defaultDay ? 'selected' : '' ?>><?= $day ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- STORE TABS -->
<div class="bg-white border-bottom">
  <div class="container">
    <ul class="nav store-tabs pt-3" id="store-tabs"></ul>
  </div>
</div>

<!-- MENU GRID -->
<div class="container py-4">
  <div class="row g-4" id="menu-grid">
    <div class="col-12 text-center py-5">
      <div class="spinner-border" role="status"></div>
      <p class="mt-3 text-muted">Loading menu...</p>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
  let activeStore   = 1;
  let currentDishes = [];

  const STORES = [
    { id:1, name:"Aling Maria's Kitchen",     storeId:"Store 1" },
    { id:2, name:"Mang Jose's Tindahan",      storeId:"Store 2" },
    { id:3, name:"Ate Susan's Carinderia",    storeId:"Store 3" },
    { id:4, name:"Kuya Ramon's Street Food",  storeId:"Store 4" },
    { id:5, name:"Tita Linda's Sweet Corner", storeId:"Store 5" },
  ];

  renderStoreTabs();
  loadMenu();

  function renderStoreTabs() {
    document.getElementById('store-tabs').innerHTML = STORES.map(s => `
      <li class="nav-item">
        <a class="nav-link ${s.id === activeStore ? 'active' : ''}" href="#"
           onclick="setStore(${s.id}); return false;">
          <span class="badge bg-secondary me-1">${s.id}</span>${s.name}
        </a>
      </li>`).join('');
  }

  function setStore(id) {
    activeStore = id;
    renderStoreTabs();
    loadMenu();
  }

  async function loadMenu() {
    const day   = document.getElementById('day-filter').value;
    const store = STORES.find(s => s.id === activeStore);
    const grid  = document.getElementById('menu-grid');

    grid.innerHTML = `<div class="col-12 text-center py-5">
      <div class="spinner-border" role="status"></div>
      <p class="mt-3 text-muted">Loading menu...</p>
    </div>`;

    try {
      const res   = await fetch(`../api/dishes.php?store=${encodeURIComponent(store.storeId)}&day=${encodeURIComponent(day)}`);
      const data  = await res.json();
      const items = data.dishes || [];
      currentDishes = items;

      if (!items.length) {
        grid.innerHTML = `<div class="col-12">
          <div class="alert alert-secondary rounded-0 text-center py-5">
            <i class="bi bi-info-circle fs-3 d-block mb-3"></i>
            <strong>No dishes available</strong> for <strong>${store.name}</strong> on <strong>${day}</strong>.<br>
            <span class="text-muted small">Check back later or try a different store / day.</span>
          </div>
        </div>`;
        return;
      }

      grid.innerHTML = items.map(d => `
        <div class="col-sm-6 col-lg-4">
          <div class="dish-card card">
            <div class="dish-image-wrap">
              ${d.image
                ? `<img src="../front/${d.image}" alt="${d.name}" loading="lazy" />`
                : `<div class="dish-image-placeholder">🍽️</div>`
              }
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title fw-bold mb-0">${d.name}</h5>
                <span class="badge bg-dark ms-2" style="font-size:.68rem;white-space:nowrap">${d.category}</span>
              </div>
              <p class="card-text text-muted small mb-3">${d.desc || '&mdash;'}</p>
              <div class="dish-price mb-3">&#8369;${parseFloat(d.price).toFixed(2)}</div>
              <button class="btn btn-dark-qcu w-100 btn-cart" onclick="addToCart(${d.id})">
                <i class="bi bi-cart-plus me-2"></i>Add to Cart
              </button>
            </div>
          </div>
        </div>`).join('');

    } catch(e) {
      grid.innerHTML = `<div class="col-12">
        <div class="alert alert-danger rounded-0">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Could not load menu. Make sure XAMPP (Apache + MySQL) is running.
        </div>
      </div>`;
    }
  }

  function addToCart(dishId) {
    const dish  = currentDishes.find(d => d.id === dishId);
    if (!dish) return;
    const store = STORES.find(s => s.id === activeStore);
    const cart  = getCart();
    cart.push({
      id:        dish.id,
      name:      dish.name,
      price:     dish.price,
      category:  dish.category,
      storeName: store.name,
    });
    saveCart(cart);
    updateCartBadge();
    renderCartOffcanvas();
    showToast(`${dish.name} added to cart ✓`, 'success');
  }
</script>
</body>
</html>
