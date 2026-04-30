<?php
require_once '../config.php';
$currentPage = 'profile';
requireLogin();

$user    = currentUser();
$error   = '';
$success = '';

// ---- Handle profile update POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim(htmlspecialchars($_POST['fullname'] ?? '', ENT_QUOTES, 'UTF-8'));
    $idnum    = trim(htmlspecialchars($_POST['idnum']    ?? '', ENT_QUOTES, 'UTF-8'));

    if (!$fullname) {
        $error = 'Full name cannot be empty.';
    } else {
        try {
            $db  = getDB();
            $pic = $user['profile_pic'] ?? null;

            // Handle profile picture upload
            if (!empty($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $ext     = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];

                if (!in_array($ext, $allowed)) {
                    $error = 'Invalid image type. Use jpg, png, gif, or webp.';
                } elseif ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
                    $error = 'Image must be under 2 MB.';
                } else {
                    $fname = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $fname)) {
                        $pic = 'uploads/' . $fname;
                    }
                }
            }

            if (!$error) {
                $stmt = $db->prepare(
                    'UPDATE users SET full_name=?, student_id=?, profile_picture=? WHERE id=?'
                );
                $stmt->execute([$fullname, $idnum, $pic, $user['id']]);

                // Refresh session
                $_SESSION['user']['full_name']   = $fullname;
                $_SESSION['user']['student_id']  = $idnum;
                $_SESSION['user']['profile_pic'] = $pic;
                $user    = currentUser();
                $success = 'Profile updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Could not update profile. Please try again.';
        }
    }
}

// ---- Fetch fresh user data from DB ----
try {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();
} catch (PDOException $e) {
    $dbUser = null;
}

// ---- Fetch user orders ----
$statusFilter = $_GET['status'] ?? '';
try {
    $db = getDB();
    if ($statusFilter) {
        $stmt = $db->prepare('SELECT * FROM orders WHERE user_id=? AND status=? ORDER BY created_at DESC');
        $stmt->execute([$user['id'], $statusFilter]);
    } else {
        $stmt = $db->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC');
        $stmt->execute([$user['id']]);
    }
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $init  = '';
    foreach ($parts as $p) { if ($p) $init .= strtoupper($p[0]); }
    return substr($init, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QCU Canteen &mdash; My Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- BANNER -->
<section class="page-banner">
  <div class="container">
    <h1>My Profile</h1>
    <p class="text-white-50 mt-1 mb-0">Manage your account and view your order history</p>
  </div>
</section>

<div class="container py-5">
  <div class="row g-4">

    <!-- LEFT: PROFILE CARD -->
    <div class="col-lg-4">
      <div class="card border rounded-0 shadow-sm p-4 text-center">

        <!-- Avatar -->
        <div class="profile-avatar-wrap mx-auto mb-3">
          <label for="profile_picture_input" class="d-inline-block position-relative" style="cursor:pointer;">
            
            <?php if (!empty($dbUser['profile_picture'])): ?>
              <img src="<?= e($dbUser['profile_picture']) ?>"
                   class="rounded-circle object-fit-cover"
                   style="width:110px;height:110px;" alt="Profile photo"/>
            <?php else: ?>
              <div class="profile-avatar-large mx-auto">
                <?= e(initials($dbUser['full_name'] ?? $user['full_name'])) ?>
              </div>
            <?php endif; ?>

            <!-- Hover Overlay -->
            <div class="avatar-overlay rounded-circle position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white text-center">
              <small style="font-size:11px; line-height:1.3; padding: 0 8px;">Upload Photo</small>
            </div>

          </label>
        </div>

        <h4 class="fw-bold mb-0"><?= e($dbUser['full_name'] ?? $user['full_name']) ?></h4>
        <p class="text-muted small mb-3"><?= e($dbUser['email'] ?? $user['email']) ?></p>

        <div class="d-flex justify-content-center gap-2 mb-3">
          <span class="badge bg-secondary px-3 py-2">
            <?= e($dbUser['user_type'] ?? $user['user_type'] ?? '') ?>
          </span>
          <span class="badge bg-dark px-3 py-2">
            <?= e($dbUser['student_id'] ?? $user['student_id'] ?? '') ?>
          </span>
        </div>

        <?php if (!empty($dbUser['created_at'])): ?>
          <p class="text-muted" style="font-size:.78rem">
            Member since <?= date('F j, Y', strtotime($dbUser['created_at'])) ?>
          </p>
        <?php endif; ?>

        <hr/>
        <h6 class="fw-bold text-start mb-3">Edit Profile</h6>

        <?php if ($error): ?>
          <div class="alert alert-danger rounded-0 text-start mb-3">
            <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
          </div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success rounded-0 text-start mb-3">
            <i class="bi bi-check-circle me-2"></i><?= e($success) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="profile.php" enctype="multipart/form-data">
          <div class="mb-3 text-start">
            <label class="form-label fw-semibold small">Full Name</label>
            <input type="text" class="form-control form-control-sm rounded-0"
                   name="fullname"
                   value="<?= e($dbUser['full_name'] ?? $user['full_name']) ?>" required/>
          </div>
          <div class="mb-3 text-start">
            <label class="form-label fw-semibold small">Student / Employee ID</label>
            <input type="text" class="form-control form-control-sm rounded-0"
                   name="idnum"
                   value="<?= e($dbUser['student_id'] ?? $user['student_id'] ?? '') ?>"/>
          </div>
          <input type="file" id="profile_picture_input" name="profile_picture"
                 accept="image/*" class="d-none" onchange="previewPic(this)"/>
          <div class="mb-3 text-start d-none">
            <img id="pic-preview" src="" alt="preview"
                 class="img-fluid rounded-0 border mt-2 d-none"
                 style="max-height:100px;object-fit:cover"/>
          </div>
          <button type="submit" class="btn btn-dark-qcu w-100">
            <i class="bi bi-floppy me-2"></i>Save Changes
          </button>
        </form>

      </div>
    </div>

    <!-- RIGHT: ORDER HISTORY -->
    <div class="col-lg-8">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">My Orders</h4>
        <a href="profile.php" class="btn btn-outline-dark-qcu btn-sm">
          <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </a>
      </div>

      <!-- Status filter -->
      <form method="GET" action="profile.php" class="mb-3">
        <select class="form-select form-select-sm rounded-0 w-auto" name="status"
                onchange="this.form.submit()">
          <option value="" <?= !$statusFilter ? 'selected' : '' ?>>All Statuses</option>
          <?php foreach (['Pending','Preparing','Ready','Completed'] as $s): ?>
            <option <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </form>

      <?php if (empty($orders)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-bag-x fs-1 d-block mb-3"></i>
          <p class="mb-2 fw-semibold">No orders yet</p>
          <p class="small">Visit the <a href="canteen.php">Canteen</a> to place your first order!</p>
        </div>
      <?php else: ?>
        <?php foreach ($orders as $order):
          $items       = json_decode($order['items'], true) ?? [];
          $statusClass = strtolower($order['status']);
        ?>
        <div class="order-history-card card mb-3 p-3">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
              <div class="fw-bold"><code><?= e($order['order_code']) ?></code></div>
              <div class="text-muted small">
                <?= date('F j, Y', strtotime($order['created_at'])) ?>
                &mdash;
                <?= date('g:i A', strtotime($order['created_at'])) ?>
              </div>
              <div class="text-muted small"><?= e($order['store']) ?></div>
            </div>
            <span class="badge badge-<?= $statusClass ?> px-3 py-2 align-self-start">
              <?= strtoupper($order['status']) ?>
            </span>
          </div>
          <hr class="my-2"/>
          <div class="mb-2">
            <?php foreach ($items as $item): ?>
              <span class="item-tag"><?= e($item['name'] ?? '') ?></span>
            <?php endforeach; ?>
          </div>
          <div class="text-end fw-bold">
            Total: &#8369;<?= number_format((float)$order['total'], 2) ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
  function previewPic(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('pic-preview');
      img.src = e.target.result;
      img.classList.remove('d-none');
    };
    reader.readAsDataURL(input.files[0]);
  }
</script>
</body>
</html>
