<?php
/**
 * navbar.php — Shared navigation bar.
 * Requires $currentPage to be set before including.
 * Requires config.php already loaded (session started).
 */
$_nav     = currentUser();
$_navPage = $currentPage ?? '';
?>
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="kyusilogo.png" alt="QCU Logo" style="height: 50px; width:auto; margin-right:8px;">
      QCU Canteen
    </a>
    <button class="navbar-toggler border-secondary" type="button"
            data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

        <li class="nav-item">
          <a class="nav-link <?= $_navPage === 'home'    ? 'active' : '' ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_navPage === 'about'   ? 'active' : '' ?>" href="about.php">About Us</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $_navPage === 'canteen' ? 'active' : '' ?>" href="canteen.php">Canteen</a>
        </li>

        <?php if ($_nav): ?>
          <!-- Logged-in links -->
          <li class="nav-item">
            <a class="nav-link <?= $_navPage === 'profile' ? 'active' : '' ?>" href="profile.php">
              <i class="bi bi-person-circle me-1"></i>Profile
            </a>
          </li>
          <?php if (($_nav['role'] ?? '') === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link <?= $_navPage === 'admin' ? 'active' : '' ?>" href="admin.php">
              <i class="bi bi-shield-lock me-1"></i>Admin
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <span class="nav-link text-white fw-semibold" style="font-size:.85rem">
              <i class="bi bi-person-fill me-1" style="color:var(--qcu-red)"></i>
              <?= e($_nav['full_name']) ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">
              <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
          </li>
        <?php else: ?>
          <!-- Guest links -->
          <li class="nav-item">
            <a class="nav-link <?= $_navPage === 'login'  ? 'active' : '' ?>" href="login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link nav-signup <?= $_navPage === 'signup' ? 'active' : '' ?>" href="signup.php">Sign Up</a>
          </li>
        <?php endif; ?>

        <!-- Cart icon -->
        <li class="nav-item">
          <a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
            <i class="bi bi-cart3 fs-5"></i>
            <span class="badge bg-danger ms-1" id="cart-badge">0</span>
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>
