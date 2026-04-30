<?php
require_once '../config.php';
$currentPage = 'login';

// Already logged in → redirect
$u = currentUser();
if ($u) redirect($u['role'] === 'admin' ? 'admin.php' : 'canteen.php');

$error   = getFlash('error');
$success = getFlash('success');

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $row  = $stmt->fetch();

            if (!$row || !password_verify($pass, $row['password'])) {
                $error = 'Invalid email or password.';
            } else {
                // Build session — never store the password hash
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'          => (int)$row['id'],
                    'full_name'   => $row['full_name'],
                    'email'       => $row['email'],
                    'student_id'  => $row['student_id'],
                    'user_type'   => $row['user_type'],
                    'role'        => $row['role'],
                    'profile_pic' => $row['profile_picture'] ?? null,
                ];
                setFlash('success', 'Welcome back, ' . $row['full_name'] . '!');
                redirect($row['role'] === 'admin' ? 'admin.php' : 'canteen.php');
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QCU Canteen &mdash; Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="flex-grow-1 d-flex align-items-center py-5" style="background:var(--qcu-cream)">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-sm-10 col-md-7 col-lg-5">

        <div class="auth-card card p-4 p-md-5 shadow-sm">
          <h2 class="card-title text-center mb-0">Login to QCU Canteen</h2>
          <div class="title-bar mx-auto"></div>

          <?php if ($error): ?>
            <div class="alert alert-danger rounded-0 mb-3">
              <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
            </div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert alert-success rounded-0 mb-3">
              <i class="bi bi-check-circle me-2"></i><?= e($success) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="login.php" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">Email</label>
              <input type="email" class="form-control rounded-0" id="email" name="email"
                     placeholder="Enter your email"
                     value="<?= e($_POST['email'] ?? '') ?>" required/>
            </div>
            <div class="mb-4">
              <label for="password" class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <input type="password" class="form-control rounded-0" id="password"
                       name="password" placeholder="Enter your password" required/>
                <button class="btn btn-outline-secondary rounded-0" type="button" id="toggle-pass">
                  <i class="bi bi-eye" id="eye-icon"></i>
                </button>
              </div>
            </div>
            <button type="submit" class="btn btn-dark-qcu w-100 py-2 mb-3">
              <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
          </form>

          <p class="text-center text-muted small mb-2">
            Don't have an account?
            <a href="signup.php" class="text-dark fw-semibold">Sign Up</a>
          </p>
          <p class="text-center text-muted" style="font-size:.78rem">
            <i class="bi bi-info-circle me-1"></i>Admin: admin@qcu.edu.ph / admin123
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
  document.getElementById('toggle-pass').addEventListener('click', () => {
    const inp = document.getElementById('password');
    const ico = document.getElementById('eye-icon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  });
</script>
</body>
</html>
