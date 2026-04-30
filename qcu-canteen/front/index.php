<?php
require_once '../config.php';
$currentPage = 'home';
$user        = currentUser();
$flashError  = getFlash('error');
$flashSuccess = getFlash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QCU Canteen &mdash; Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<?php if ($flashError): ?>
<div class="container mt-3">
  <div class="alert alert-danger alert-dismissible fade show rounded-0">
    <i class="bi bi-exclamation-circle me-2"></i><?= e($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<?php if ($flashSuccess): ?>
<div class="container mt-3">
  <div class="alert alert-success alert-dismissible fade show rounded-0">
    <i class="bi bi-check-circle me-2"></i><?= e($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<!-- ===== HERO ===== -->
<section class="hero-section text-white">
  <div class="container hero-inner py-5 text-center">
    <h1 class="display-3 fw-black mb-3" style="font-weight:900;letter-spacing:2px">
      WELCOME TO<br>QCU CANTEEN
    </h1>
    <p class="fs-5 mb-4 fw-semibold" style="letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,0.85)">
      &mdash; your campus food hub &mdash;
    </p>
    <?php if ($user): ?>
      <p class="mb-4 fs-5 fw-semibold" style="color:#fff;background:rgba(255,255,255,0.12);display:inline-block;padding:.4rem 1.2rem;border-radius:2rem;border:1px solid rgba(255,255,255,0.25)">
        Welcome back, <strong style="color:#ffcdd2"><?= e($user['full_name']) ?></strong>! &#128075;
      </p>
    <?php endif; ?>
    <a href="canteen.php" class="btn btn-dark-qcu btn-lg px-5 py-3">
      <i class="bi bi-bag-check me-2"></i>Order Now
    </a>
  </div>
</section>

<!-- ===== WHY CHOOSE US ===== -->
<section class="py-5">
  <div class="container py-3">
    <h2 class="text-center section-heading">Why Choose Us?</h2>
    <div class="title-divider mx-auto"></div>
    <div class="row g-4">
      <?php
      $features = [
        ['&#127978;', '5 Different Stores',  'Variety of cuisines from multiple canteen vendors to satisfy every craving.'],
        ['&#127857;', 'Daily Fresh Menu',    'New dishes available each day of the week, always freshly prepared.'],
        ['&#128241;', 'Easy Ordering',       'Browse, select, and checkout your meal in just a few clicks.'],
        ['&#128176;', 'Student Friendly',    'Affordable prices designed for students, faculty, and staff alike.'],
      ];
      foreach ($features as [$icon, $title, $desc]):
      ?>
      <div class="col-sm-6 col-lg-3">
        <div class="feature-card card h-100 p-4 text-center">
          <div class="fs-1 mb-3"><?= $icon ?></div>
          <h5 class="fw-bold"><?= e($title) ?></h5>
          <p class="text-muted small"><?= e($desc) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== OUR STORES ===== -->
<section class="py-5 bg-white">
  <div class="container py-3">
    <h2 class="text-center section-heading">Our Food Stalls</h2>
    <div class="title-divider mx-auto"></div>
    <div class="row g-3 justify-content-center">
      <?php
      $stores = [
        "Aling Maria's Kitchen",
        "Mang Jose's Tindahan",
        "Ate Susan's Carinderia",
        "Kuya Ramon's Street Food",
        "Tita Linda's Sweet Corner",
      ];
      foreach ($stores as $i => $sname):
      ?>
      <div class="col-md-4 col-lg-2 text-center">
        <div class="p-3 border h-100">
          <div class="store-num-circle mx-auto mb-2"><?= $i + 1 ?></div>
          <div class="fw-semibold small"><?= e($sname) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="about.php" class="btn btn-outline-dark-qcu px-4">Learn More</a>
    </div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="py-5" style="background:var(--qcu-dark)">
  <div class="container text-center py-3">
    <h3 class="text-white fw-bold mb-3">Hungry? Order now from any of our 5 stalls.</h3>
    <p class="text-white-50 mb-4">Fresh Filipino meals available Monday to Friday.</p>
    <?php if (!$user): ?>
      <a href="signup.php" class="btn btn-light px-5 py-2 fw-bold me-2">
        <i class="bi bi-person-plus me-2"></i>Sign Up Free
      </a>
    <?php endif; ?>
    <a href="canteen.php" class="btn btn-outline-light px-5 py-2 fw-bold">
      <i class="bi bi-bag3 me-2"></i>Go to Canteen
    </a>
  </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>
