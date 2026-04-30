<?php
require_once '../config.php';
$currentPage = 'signup';

// Already logged in → redirect
$u = currentUser();
if ($u) redirect('canteen.php');

$error = '';
$old   = [];

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname    = trim(htmlspecialchars($_POST['fullname']    ?? '', ENT_QUOTES, 'UTF-8'));
    $email       = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $idnum       = trim(htmlspecialchars($_POST['idnum']       ?? '', ENT_QUOTES, 'UTF-8'));
    $usertype    = $_POST['usertype']    ?? 'student';
    $pass        = $_POST['password']    ?? '';
    $confirmpass = $_POST['confirmpass'] ?? '';

    $old = compact('fullname', 'email', 'idnum', 'usertype');

    // Validation
    if (!$fullname || !$email || !$idnum || !$pass || !$confirmpass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirmpass) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($usertype, ['student', 'staff', 'faculty'])) {
        $error = 'Invalid user type.';
    } else {
        try {
            $db = getDB();

            // Check duplicate email
            $chk = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'That email is already registered. Please log in.';
            } else {
                $hash   = password_hash($pass, PASSWORD_BCRYPT);
                $dbType = ucfirst($usertype); // student → Student
                $ins    = $db->prepare(
                    'INSERT INTO users (full_name, email, student_id, user_type, password, role)
                     VALUES (?, ?, ?, ?, ?, "user")'
                );
                $ins->execute([$fullname, $email, $idnum, $dbType, $hash]);

                setFlash('success', 'Account created successfully! Please log in.');
                redirect('login.php');
            }
        } catch (PDOException $e) {
            $error = 'Could not create account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QCU Canteen &mdash; Sign Up</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="style.css" rel="stylesheet"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="flex-grow-1 d-flex align-items-center py-5" style="background:var(--qcu-cream)">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-sm-10 col-md-8 col-lg-6">

        <div class="auth-card card p-4 p-md-5 shadow-sm">
          <h2 class="card-title text-center mb-0">Create Your Account</h2>
          <div class="title-bar mx-auto"></div>

          <?php if ($error): ?>
            <div class="alert alert-danger rounded-0 mb-3">
              <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="signup.php" novalidate>

            <div class="mb-3">
              <label for="fullname" class="form-label fw-semibold">Full Name</label>
              <input type="text" class="form-control rounded-0" id="fullname" name="fullname"
                     placeholder="Juan Dela Cruz"
                     value="<?= e($old['fullname'] ?? '') ?>" required/>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">Email</label>
              <input type="email" class="form-control rounded-0" id="email" name="email"
                     placeholder="juan@qcu.edu.ph"
                     value="<?= e($old['email'] ?? '') ?>" required/>
            </div>

            <div class="mb-3">
              <label for="idnum" class="form-label fw-semibold">Student / Employee ID</label>
              <input type="text" class="form-control rounded-0" id="idnum" name="idnum"
                     placeholder="25-****"
                     value="<?= e($old['idnum'] ?? '') ?>" required/>
            </div>

            <div class="mb-3">
              <label for="usertype" class="form-label fw-semibold">User Type</label>
              <select class="form-select rounded-0" id="usertype" name="usertype">
                <option value="student" <?= ($old['usertype'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="faculty" <?= ($old['usertype'] ?? '') === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                <option value="staff"   <?= ($old['usertype'] ?? '') === 'staff'   ? 'selected' : '' ?>>Staff</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <input type="password" class="form-control rounded-0" id="password"
                       name="password" placeholder="Min. 6 characters" required/>
                <button class="btn btn-outline-secondary rounded-0" type="button"
                        onclick="togglePass('password','eye1')">
                  <i class="bi bi-eye" id="eye1"></i>
                </button>
              </div>
            </div>

            <div class="mb-4">
              <label for="confirmpass" class="form-label fw-semibold">Confirm Password</label>
              <div class="input-group">
                <input type="password" class="form-control rounded-0" id="confirmpass"
                       name="confirmpass" placeholder="Repeat password" required/>
                <button class="btn btn-outline-secondary rounded-0" type="button"
                        onclick="togglePass('confirmpass','eye2')">
                  <i class="bi bi-eye" id="eye2"></i>
                </button>
              </div>
              <div id="pass-match" class="form-text mt-1"></div>
            </div>

            <button type="submit" class="btn btn-dark-qcu w-100 py-2 mb-3">
              <i class="bi bi-person-plus me-2"></i>Sign Up
            </button>
          </form>

          <p class="text-center text-muted small mb-0">
            Already have an account?
            <a href="login.php" class="text-dark fw-semibold">Login</a>
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
  function togglePass(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  }

  document.getElementById('confirmpass').addEventListener('input', () => {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('confirmpass').value;
    const el = document.getElementById('pass-match');
    if (!p2) { el.textContent = ''; return; }
    el.innerHTML = p1 === p2
      ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</span>'
      : '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Passwords do not match</span>';
  });
</script>
</body>
</html>
