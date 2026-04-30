<?php
require_once '../config.php';
$currentPage = 'admin';
requireAdmin();
$_adm = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QCU Canteen &mdash; Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
</head>
<body style="background:#f0ece8">

<nav class="navbar sticky-top">
  <div class="container-fluid px-4">
    <span class="navbar-brand">QCU Canteen &mdash; <span style="color:rgba(255,255,255,.5)">Admin</span></span>
    <div class="d-flex gap-3 align-items-center">
      <span class="text-white-50 small"><i class="bi bi-person-fill me-1"></i><?= e($_adm['full_name']) ?></span>
      <a class="text-white-50 text-decoration-none small" href="index.php"><i class="bi bi-house me-1"></i>Home</a>
      <a class="text-white-50 text-decoration-none small" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4">

  <!-- STATS -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card"><div class="stat-num" id="stat-total">&#8212;</div><div class="stat-label">Total Orders</div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card"><div class="stat-num" id="stat-pending">&#8212;</div><div class="stat-label">Pending</div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card"><div class="stat-num" id="stat-preparing">&#8212;</div><div class="stat-label">Preparing</div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card"><div class="stat-num" id="stat-ready">&#8212;</div><div class="stat-label">Ready for Pickup</div></div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card"><div class="stat-num" id="stat-revenue">&#8212;</div><div class="stat-label">Today&#39;s Revenue</div></div>
    </div>
  </div>

  <!-- TABS -->
  <ul class="nav admin-tabs mb-4 border-bottom">
    <li class="nav-item">
      <a class="nav-link active" href="#" onclick="switchTab('orders');return false;">
        <i class="bi bi-receipt me-1"></i>Orders
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#" onclick="switchTab('dishes');return false;">
        <i class="bi bi-egg-fried me-1"></i>Manage Dishes
      </a>
    </li>
  </ul>

  <!-- ORDERS PANEL -->
  <div id="panel-orders">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h4 class="fw-bold mb-0">Customer Orders</h4>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-dark-qcu btn-sm" onclick="loadOrders()">
          <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
        <button class="btn btn-sm btn-outline-danger rounded-0" onclick="clearAllOrders()">
          <i class="bi bi-trash3 me-1"></i>Clear All
        </button>
      </div>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-sm-6 col-md-3">
        <label class="form-label fw-semibold small mb-1">Store</label>
        <select class="form-select form-select-sm rounded-0" id="filter-store" onchange="loadOrders()">
          <option value="">All Stores</option>
          <option>Aling Maria&#39;s Kitchen</option>
          <option>Mang Jose&#39;s Tindahan</option>
          <option>Ate Susan&#39;s Carinderia</option>
          <option>Kuya Ramon&#39;s Street Food</option>
          <option>Tita Linda&#39;s Sweet Corner</option>
        </select>
      </div>
      <div class="col-sm-6 col-md-3">
        <label class="form-label fw-semibold small mb-1">Status</label>
        <select class="form-select form-select-sm rounded-0" id="filter-status" onchange="loadOrders()">
          <option value="">All Statuses</option>
          <option>Pending</option><option>Preparing</option>
          <option>Ready</option><option>Completed</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold small mb-1">Search</label>
        <input type="text" class="form-control form-control-sm rounded-0" id="filter-search"
               placeholder="Name, order code, student ID..." oninput="loadOrders()"/>
      </div>
    </div>
    <div class="table-responsive bg-white border">
      <table class="table qcu-table mb-0">
        <thead>
          <tr>
            <th>Order Code</th><th>Date &amp; Time</th><th>Customer</th>
            <th>ID No.</th><th>Store</th><th>Items</th>
            <th>Total</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="orders-tbody">
          <tr><td colspan="9" class="text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm me-2"></div>Loading...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- DISHES PANEL -->
  <div id="panel-dishes" style="display:none">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h4 class="fw-bold mb-0">Manage Dishes</h4>
      <button class="btn btn-outline-dark-qcu btn-sm" onclick="openDishModal()">
        <i class="bi bi-plus-lg me-1"></i>Add New Dish
      </button>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-sm-6 col-md-3">
        <label class="form-label fw-semibold small mb-1">Store</label>
        <select class="form-select form-select-sm rounded-0" id="dish-filter-store" onchange="loadDishes()">
          <option value="">All Stores</option>
          <option>Store 1</option><option>Store 2</option><option>Store 3</option>
          <option>Store 4</option><option>Store 5</option>
        </select>
      </div>
      <div class="col-sm-6 col-md-3">
        <label class="form-label fw-semibold small mb-1">Day</label>
        <select class="form-select form-select-sm rounded-0" id="dish-filter-day" onchange="loadDishes()">
          <option value="">All Days</option>
          <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
          <option>Thursday</option><option>Friday</option>
        </select>
      </div>
      <div class="col-sm-6 col-md-3">
        <label class="form-label fw-semibold small mb-1">Category</label>
        <select class="form-select form-select-sm rounded-0" id="dish-filter-cat" onchange="loadDishes()">
          <option value="">All Categories</option>
        </select>
      </div>
    </div>
    <div class="table-responsive bg-white border">
      <table class="table qcu-table mb-0">
        <thead>
          <tr>
            <th>ID</th><th>Store</th><th>Photo</th><th>Dish Name</th><th>Category</th>
            <th>Price</th><th>Day</th><th>Description</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="dishes-tbody">
          <tr><td colspan="9" class="text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm me-2"></div>Loading...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /container-fluid -->

<!-- DISH MODAL -->
<div class="modal fade" id="dishModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-0">
      <div class="modal-header" style="background:var(--qcu-dark);color:#fff">
        <h5 class="modal-title fw-bold" id="dish-modal-title">Add New Dish</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-danger d-none rounded-0 mb-3" id="dish-modal-error"></div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Dish Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control rounded-0" id="m-name" placeholder="e.g. Adobong Manok"/>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
          <select class="form-select rounded-0" id="m-category">
            <option value="">&#8212; Select a category &#8212;</option>
            <option>Meal</option><option>Soup</option><option>Rice Meal</option>
            <option>Silog</option><option>Snack</option><option>Street Food</option>
            <option>Dessert</option><option>Beverage</option>
            <option>Pasta/Noodles</option><option>Others</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Store <span class="text-danger">*</span></label>
          <select class="form-select rounded-0" id="m-store">
            <option value="Store 1">Store 1 &#8212; Aling Maria&#39;s Kitchen</option>
            <option value="Store 2">Store 2 &#8212; Mang Jose&#39;s Tindahan</option>
            <option value="Store 3">Store 3 &#8212; Ate Susan&#39;s Carinderia</option>
            <option value="Store 4">Store 4 &#8212; Kuya Ramon&#39;s Street Food</option>
            <option value="Store 5">Store 5 &#8212; Tita Linda&#39;s Sweet Corner</option>
          </select>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Price (&#8369;) <span class="text-danger">*</span></label>
            <input type="number" class="form-control rounded-0" id="m-price" min="1" step="0.01" placeholder="0.00"/>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Day <span class="text-danger">*</span></label>
            <select class="form-select rounded-0" id="m-day">
              <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
              <option>Thursday</option><option>Friday</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Dish Photo <span class="text-muted fw-normal">(optional)</span></label>
          <input type="file" class="form-control form-control-sm rounded-0" id="m-image" accept="image/*" onchange="previewDishImage(this)" />
          <img id="m-image-preview" src="" alt="Dish preview" class="dish-image-preview rounded-0 border d-none" />
        </div>
        <div class="mb-1">
          <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
          <textarea class="form-control rounded-0" id="m-desc" rows="2" placeholder="Short description..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-dark-qcu" id="save-dish-btn" onclick="saveDish()">
          <i class="bi bi-floppy me-1"></i>Save Dish
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ORDER VIEW MODAL -->
<div class="modal fade" id="orderModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-0">
      <div class="modal-header" style="background:var(--qcu-dark);color:#fff">
        <h5 class="modal-title fw-bold">Order Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="order-modal-body"></div>
      <div class="modal-footer">
        <button class="btn btn-dark-qcu" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="liveToast" class="toast align-items-center text-white border-0 bg-dark" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-semibold"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../api/api.js"></script>
<script>
  const STORES = [
    { id:1, name:"Aling Maria's Kitchen",     storeId:"Store 1" },
    { id:2, name:"Mang Jose's Tindahan",      storeId:"Store 2" },
    { id:3, name:"Ate Susan's Carinderia",    storeId:"Store 3" },
    { id:4, name:"Kuya Ramon's Street Food",  storeId:"Store 4" },
    { id:5, name:"Tita Linda's Sweet Corner", storeId:"Store 5" },
  ];
  const DISH_CATEGORIES = [
    'Meal','Soup','Rice Meal','Silog','Snack',
    'Street Food','Dessert','Beverage','Pasta/Noodles','Others'
  ];

  let editingDishId  = null;
  let allDishesCache = [];

  // Populate category filter
  const catFilter = document.getElementById('dish-filter-cat');
  DISH_CATEGORIES.forEach(c => {
    const o = document.createElement('option');
    o.value = c; o.textContent = c;
    catFilter.appendChild(o);
  });

  function showToast(msg, type = 'dark') {
    const el = document.getElementById('liveToast');
    if (!el) return;
    el.querySelector('.toast-body').textContent = msg;
    el.className = `toast align-items-center text-white border-0 bg-${type}`;
    new bootstrap.Toast(el, { delay: 2800 }).show();
  }

  loadOrders();
  loadStats();

  function switchTab(tab) {
    document.getElementById('panel-orders').style.display = tab === 'orders' ? '' : 'none';
    document.getElementById('panel-dishes').style.display = tab === 'dishes' ? '' : 'none';
    document.querySelectorAll('.admin-tabs .nav-link').forEach((el, i) => {
      el.classList.toggle('active', (i === 0) === (tab === 'orders'));
    });
    if (tab === 'dishes') loadDishes();
  }

  function formatOrderDate(createdAt) {
    const date = new Date(createdAt);
    return {
      date: date.toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' }),
      time: date.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit', hour12: true }),
      dayLabel: date.toLocaleDateString('en-US', { weekday:'long' }),
    };
  }

  /* ---- Stats ---- */
  async function loadStats() {
    try {
      const data = await apiGetOrders();
      const all  = (data.orders || []).map(o => ({
        ...o,
        ...formatOrderDate(o.createdAt),
      }));
      const today = new Date().toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
      document.getElementById('stat-total').textContent     = all.length;
      document.getElementById('stat-pending').textContent   = all.filter(o => o.status === 'Pending').length;
      document.getElementById('stat-preparing').textContent = all.filter(o => o.status === 'Preparing').length;
      document.getElementById('stat-ready').textContent     = all.filter(o => o.status === 'Ready').length;
      document.getElementById('stat-revenue').textContent   =
        '\u20B1' + all.filter(o => o.status === 'Completed' && o.date === today)
          .reduce((s,o) => s + parseFloat(o.total), 0).toFixed(2);
    } catch(e) { console.error(e); }
  }

  /* ---- Orders ---- */
  async function loadOrders() {
    const filters = {};
    const store  = document.getElementById('filter-store').value;
    const status = document.getElementById('filter-status').value;
    const search = document.getElementById('filter-search').value.trim();
    if (store)  filters.store  = store;
    if (status) filters.status = status;
    if (search) filters.search = search;

    const tbody = document.getElementById('orders-tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

    try {
      const data   = await apiGetOrders(filters);
      const orders = (data.orders || []).map(o => ({
        ...o,
        ...formatOrderDate(o.createdAt),
      }));
      if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5">No orders found.</td></tr>';
        return;
      }
      tbody.innerHTML = orders.map(o => `
        <tr>
          <td><code class="small">${o.orderCode}</code></td>
          <td>
            <div class="small">${o.date}</div>
            <div class="text-muted" style="font-size:.75rem">${o.time}</div>
            <span class="badge bg-secondary mt-1" style="font-size:.68rem">${o.dayLabel}</span>
          </td>
          <td><div class="fw-semibold">${o.customer}</div><div class="text-muted small">${o.role}</div></td>
          <td class="small">${o.studentId}</td>
          <td class="small">${o.store}</td>
          <td>${(o.items||[]).map(i=>'<span class="item-tag">'+i.name+'</span>').join('')}</td>
          <td><strong>\u20B1${parseFloat(o.total).toFixed(2)}</strong></td>
          <td><span class="badge badge-${o.status.toLowerCase()} px-2 py-1">${o.status.toUpperCase()}</span></td>
          <td>
            <button class="btn btn-sm btn-outline-secondary rounded-0 me-1 mb-1" onclick="viewOrder(${o.id})">
              <i class="bi bi-eye"></i>
            </button>
            <select class="form-select form-select-sm rounded-0 d-inline-block w-auto" onchange="updateStatus(${o.id}, this.value)">
              <option${o.status==='Pending'   ?' selected':''}>Pending</option>
              <option${o.status==='Preparing' ?' selected':''}>Preparing</option>
              <option${o.status==='Ready'     ?' selected':''}>Ready</option>
              <option${o.status==='Completed' ?' selected':''}>Completed</option>
            </select>
          </td>
        </tr>`).join('');
      window._orders = orders;
      loadStats();
    } catch(e) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle me-2"></i>Could not load orders. Is XAMPP running?</td></tr>';
    }
  }

  async function updateStatus(id, status) {
    try {
      const r = await apiUpdateOrderStatus(id, status);
      if (r.error) { showToast(r.error, 'danger'); return; }
      showToast('Status updated to ' + status, 'success');
      loadStats();
    } catch(e) { showToast('Failed to update', 'danger'); }
  }

  async function clearAllOrders() {
    if (!confirm('Delete ALL orders from the database? This cannot be undone.')) return;
    try {
      const r = await apiClearOrders();
      if (r.error) { showToast(r.error, 'danger'); return; }
      showToast('All orders cleared', 'success');
      loadOrders();
    } catch(e) { showToast('Failed', 'danger'); }
  }

  function viewOrder(id) {
    const o = (window._orders || []).find(o => o.id === id);
    if (!o) return;
    document.getElementById('order-modal-body').innerHTML = `
      <table class="table table-borderless table-sm mb-0">
        <tr><th style="width:40%">Order Code</th><td><code>${o.orderCode}</code></td></tr>
        <tr><th>Customer</th><td>${o.customer} <span class="badge bg-secondary">${o.role}</span></td></tr>
        <tr><th>Student / ID No.</th><td>${o.studentId}</td></tr>
        <tr><th>Store</th><td>${o.store}</td></tr>
        <tr><th>Date &amp; Time</th><td>${o.date} &mdash; ${o.time}</td></tr>
        <tr><th>Items</th><td>${(o.items||[]).map(i=>'<span class="item-tag">'+i.name+'</span>').join(' ')}</td></tr>
        <tr><th>Total</th><td><strong>\u20B1${parseFloat(o.total).toFixed(2)}</strong></td></tr>
        <tr><th>Status</th><td><span class="badge badge-${o.status.toLowerCase()} px-2 py-1">${o.status.toUpperCase()}</span></td></tr>
      </table>`;
    new bootstrap.Modal(document.getElementById('orderModal')).show();
  }

  /* ---- Dishes ---- */
  async function loadDishes() {
    const store = document.getElementById('dish-filter-store').value;
    const day   = document.getElementById('dish-filter-day').value;
    const cat   = document.getElementById('dish-filter-cat').value;
    const tbody = document.getElementById('dishes-tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>';

    try {
      const data = await apiGetDishes(store, day);
      let dishes = data.dishes || [];
      if (cat) dishes = dishes.filter(d => d.category === cat);

      if (!dishes.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5">No dishes yet. Click <strong>+ Add New Dish</strong> to get started.</td></tr>';
        return;
      }
      tbody.innerHTML = dishes.map(d => `
        <tr>
          <td>${d.id}</td>
          <td>${d.store}</td>
          <td>
            ${d.image
              ? `<img src="../front/${d.image}" class="dish-thumb" alt="${d.name}" />`
              : `<div class="dish-thumb-placeholder">🍽️</div>`
            }
          </td>
          <td class="fw-semibold">${d.name}</td>
          <td><span class="badge bg-secondary">${d.category}</span></td>
          <td>₱${parseFloat(d.price).toFixed(2)}</td>
          <td>${d.day}</td>
          <td class="text-muted small" style="max-width:180px">${d.desc || ''}</td>
          <td>
            <button class="btn btn-sm btn-outline-secondary rounded-0 me-1" onclick="editDish(${d.id})">
              <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="btn btn-sm btn-dark-qcu" onclick="deleteDish(${d.id})">
              <i class="bi bi-trash3"></i> Delete
            </button>
          </td>
        </tr>`).join('');
      allDishesCache = dishes;
    } catch(e) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle me-2"></i>Could not load dishes.</td></tr>';
    }
  }

  function openDishModal(id = null) {
    editingDishId = id;
    document.getElementById('dish-modal-error').classList.add('d-none');
    document.getElementById('dish-modal-title').textContent = id ? 'Edit Dish' : 'Add New Dish';
    const imageInput = document.getElementById('m-image');
    const imagePreview = document.getElementById('m-image-preview');

    if (id) {
      const d = allDishesCache.find(d => d.id === id);
      if (!d) return;
      document.getElementById('m-name').value     = d.name;
      document.getElementById('m-category').value = d.category;
      document.getElementById('m-store').value    = d.store;
      document.getElementById('m-price').value    = d.price;
      document.getElementById('m-day').value      = d.day;
      document.getElementById('m-desc').value     = d.desc || '';
      imageInput.value = '';
      if (d.image) {
        imagePreview.src = d.image;
        imagePreview.classList.remove('d-none');
      } else {
        imagePreview.src = '';
        imagePreview.classList.add('d-none');
      }
    } else {
      document.getElementById('m-name').value     = '';
      document.getElementById('m-category').value = '';
      document.getElementById('m-store').value    = 'Store 1';
      document.getElementById('m-price').value    = '';
      document.getElementById('m-day').value      = 'Monday';
      document.getElementById('m-desc').value     = '';
      imageInput.value = '';
      imagePreview.src = '';
      imagePreview.classList.add('d-none');
    }
    new bootstrap.Modal(document.getElementById('dishModal')).show();
  }

  function editDish(id) { openDishModal(id); }

  async function saveDish() {
    const name       = document.getElementById('m-name').value.trim();
    const category   = document.getElementById('m-category').value;
    const store      = document.getElementById('m-store').value;
    const price      = parseFloat(document.getElementById('m-price').value);
    const day        = document.getElementById('m-day').value;
    const desc       = document.getElementById('m-desc').value.trim();
    const imageInput = document.getElementById('m-image');
    const errEl      = document.getElementById('dish-modal-error');
    const btn        = document.getElementById('save-dish-btn');

    errEl.classList.add('d-none');
    if (!name)                { errEl.textContent = 'Dish Name is required.';        errEl.classList.remove('d-none'); return; }
    if (!category)            { errEl.textContent = 'Please select a Category.';     errEl.classList.remove('d-none'); return; }
    if (!price || price <= 0) { errEl.textContent = 'Please enter a valid Price.';   errEl.classList.remove('d-none'); return; }

    const storeObj  = STORES.find(s => s.storeId === store);
    const storeName = storeObj ? storeObj.name : store;
    const formData  = new FormData();

    formData.append('store', store);
    formData.append('storeName', storeName);
    formData.append('name', name);
    formData.append('category', category);
    formData.append('price', price);
    formData.append('day', day);
    formData.append('desc', desc);
    if (imageInput.files[0]) {
      formData.append('image', imageInput.files[0]);
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    try {
      const result = editingDishId
        ? await apiUpdateDish(editingDishId, formData)
        : await apiAddDish(formData);

      if (result.error) {
        errEl.textContent = result.error;
        errEl.classList.remove('d-none');
      } else {
        bootstrap.Modal.getInstance(document.getElementById('dishModal')).hide();
        showToast(editingDishId ? 'Dish updated!' : 'Dish added successfully!', 'success');
        loadDishes();
      }
    } catch(e) {
      errEl.textContent = 'Could not save. Is XAMPP running?';
      errEl.classList.remove('d-none');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-floppy me-1"></i>Save Dish';
  }

  function previewDishImage(input) {
    const preview = document.getElementById('m-image-preview');
    if (!input.files || !input.files[0]) {
      preview.src = '';
      preview.classList.add('d-none');
      return;
    }
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.classList.remove('d-none');
    };
    reader.readAsDataURL(input.files[0]);
  }

  async function deleteDish(id) {
    if (!confirm('Delete this dish? Students will no longer see it.')) return;
    try {
      const r = await apiDeleteDish(id);
      if (r.error) { showToast(r.error, 'danger'); return; }
      showToast('Dish deleted', 'success');
      loadDishes();
    } catch(e) { showToast('Failed to delete', 'danger'); }
  }

  async function apiGetOrders(filters = {}) {
    const p = new URLSearchParams(filters).toString();
    const res = await fetch(`../api/orders.php${p ? '?' + p : ''}`);
    return res.json();
  }
  async function apiUpdateOrderStatus(id, status) {
    const res = await fetch(`../api/orders.php?id=${id}`, {
      method: 'PUT', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status }),
    });
    return res.json();
  }
  async function apiClearOrders() {
    const res = await fetch('../api/orders.php', { method: 'DELETE' });
    return res.json();
  }
  async function apiGetDishes(store = '', day = '') {
    const p = new URLSearchParams();
    if (store) p.set('store', store);
    if (day)   p.set('day', day);
    const res = await fetch(`../api/dishes.php${p.toString() ? '?' + p : ''}`);
    return res.json();
  }
  async function apiAddDish(data) {
    const options = { method: 'POST', body: data };
    if (!(data instanceof FormData)) {
      options.headers = { 'Content-Type': 'application/json' };
      options.body = JSON.stringify(data);
    }
    const res = await fetch('../api/dishes.php', options);
    return res.json();
  }
  async function apiUpdateDish(id, data) {
    const isForm = data instanceof FormData;
    const options = {
      method: isForm ? 'POST' : 'PUT',
      body: isForm ? data : JSON.stringify(data),
    };
    if (!isForm) {
      options.headers = { 'Content-Type': 'application/json' };
    }
    if (isForm) {
      data.append('_method', 'PUT');
    }
    const res = await fetch(`../api/dishes.php?id=${id}`, options);
    return res.json();
  }
  async function apiDeleteDish(id) {
    const res = await fetch(`../api/dishes.php?id=${id}`, { method: 'DELETE' });
    return res.json();
  }
</script>
</body>
</html>
