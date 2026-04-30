<?php
require_once '../config.php';
$currentPage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QCU Canteen &mdash; About Us</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- PAGE BANNER -->
<section class="page-banner text-center">
  <div class="container">
    <h1>About Us</h1>
    <p class="text-white-50 mt-2 mb-0">
      Serving the QCU community with delicious, affordable Filipino food.
    </p>
  </div>
</section>

<!-- MISSION & STORY -->
<section class="py-5 bg-white">
  <div class="container py-3">
    <div class="row g-5">
      <div class="col-md-6">
        <h2 class="fw-bold mb-3">Our Mission</h2>
        <p class="text-muted lh-lg">
          The QCU Canteen aims to provide a convenient and affordable dining experience
          for all students, faculty, and staff of Quezon City University. We believe
          that a good meal fuels great minds.
        </p>
        <p class="text-muted lh-lg">
          Our canteen hosts five different food stalls, each offering a unique selection
          of Filipino home-cooked dishes that rotate daily, ensuring variety and freshness
          throughout the week.
        </p>
      </div>
      <div class="col-md-6">
        <h2 class="fw-bold mb-3">Our Story</h2>
        <p class="text-muted lh-lg">
          From humble beginnings as a small cafeteria, the QCU Canteen has grown into
          a beloved institution on campus. Today, we serve hundreds of meals daily to
          our QCU community.
        </p>
        <p class="text-muted lh-lg">
          We are proud to support local food entrepreneurs by giving them a space to
          share their culinary traditions with the university community.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="py-5" style="background:var(--qcu-dark)">
  <div class="container">
    <div class="row g-4 text-white text-center">
      <div class="col-6 col-md-3">
        <div class="fs-1 fw-bold">5</div>
        <div class="text-white-50 small text-uppercase">Food Stalls</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="fs-1 fw-bold">500+</div>
        <div class="text-white-50 small text-uppercase">Daily Meals</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="fs-1 fw-bold">5</div>
        <div class="text-white-50 small text-uppercase">Days a Week</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="fs-1 fw-bold">100%</div>
        <div class="text-white-50 small text-uppercase">Filipino Food</div>
      </div>
    </div>
  </div>
</section>

<!-- FOOD STALLS -->
<section class="py-5">
  <div class="container py-3">
    <h2 class="text-center section-heading">Our Food Stalls</h2>
    <div class="title-divider mx-auto"></div>
    <div class="row g-3">
      <?php
      $stalls = [
        [1, "Aling Maria's Kitchen",    "Classic Filipino home-cooked meals — adobo, sinigang, kare-kare and more."],
        [2, "Mang Jose's Tindahan",     "Quick bites, pancit, beverages and everyday comfort food."],
        [3, "Ate Susan's Carinderia",   "Budget-friendly viands, rice meals, and silog combinations."],
        [4, "Kuya Ramon's Street Food", "Kwek-kwek, fishball, banana cue, and your favourite campus snacks."],
        [5, "Tita Linda's Sweet Corner","Desserts, halo-halo, leche flan, and refreshing cold drinks."],
      ];
      foreach ($stalls as [$num, $sname, $desc]):
      ?>
      <div class="col-md-6">
        <div class="store-item d-flex align-items-center gap-3">
          <div class="store-num-circle"><?= $num ?></div>
          <div>
            <div class="fw-bold"><?= e($sname) ?></div>
            <div class="text-muted small"><?= e($desc) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-5 bg-white border-top">
  <div class="container text-center py-2">
    <h3 class="fw-bold mb-3">Ready to order?</h3>
    <a href="canteen.php" class="btn btn-dark-qcu btn-lg px-5">
      <i class="bi bi-bag3 me-2"></i>Visit Our Canteen
    </a>
  </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>
