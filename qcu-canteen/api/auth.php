<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ================= LOGIN =================
    case 'login':
        $body = getBody();

        $email = trim($body['email'] ?? '');
        $pass  = $body['password'] ?? '';

        if (!$email || !$pass) {
            respond(['error' => 'Email and password required'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($pass, $user['password'])) {
            respond(['error' => 'Invalid email or password'], 401);
        }

        // ✅ FIXED: use full_name
        $_SESSION['user'] = [
            'id'   => $user['id'],
            'name' => $user['full_name'],
            'role' => $user['role']
        ];

        respond([
            'success' => true,
            'user' => [
                'id'    => $user['id'],
                'name'  => $user['full_name'],
                'role'  => $user['role']
            ]
        ]);
        break;

    // ================= SIGNUP =================
    case 'signup':
        $body = getBody();

        $name  = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        $sid   = trim($body['studentId'] ?? '');
        $pass  = $body['password'] ?? '';

        if (!$name || !$email || !$sid || !$pass) {
            respond(['error' => 'All fields required'], 400);
        }

        $db = getDB();

        // Check duplicate email
        $stmt = $db->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            respond(['error' => 'Email already exists'], 409);
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);

        // ✅ FIXED: full_name column
        $stmt = $db->prepare("INSERT INTO users (full_name, email, student_id, password, role)
                              VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("ssss", $name, $email, $sid, $hash);

        if ($stmt->execute()) {
            respond(['success' => true]);
        } else {
            respond(['error' => 'Signup failed'], 500);
        }
        break;

    // ================= GET PROFILE =================
    case 'get_profile':

        if (!isLoggedIn()) {
            respond(['error' => 'Not logged in'], 401);
        }

        $userId = $_SESSION['user']['id'];

        $db = getDB();
        $stmt = $db->prepare("SELECT id, full_name, email, student_id, role FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        respond([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'student_id' => $user['student_id'],
                'role' => $user['role']
            ]
        ]);
        break;

    // ================= UPDATE PROFILE =================
    case 'update_profile':

        if (!isLoggedIn()) {
            respond(['error' => 'Not logged in'], 401);
        }

        $body = getBody();

        $userId = $_SESSION['user']['id'];
        $name   = trim($body['name'] ?? '');
        $sid    = trim($body['student_id'] ?? '');

        if (!$name) {
            respond(['error' => 'Name required'], 400);
        }

        $db = getDB();

        // ✅ FIXED: full_name column
        $stmt = $db->prepare("UPDATE users SET full_name=?, student_id=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $sid, $userId);

        if ($stmt->execute()) {
            $_SESSION['user']['name'] = $name;

            respond(['success' => true]);
        } else {
            respond(['error' => 'Update failed'], 500);
        }
        break;

    // ================= LOGOUT =================
    case 'logout':
        session_destroy();
        respond(['success' => true]);
        break;

    default:
        respond(['error' => 'Invalid action'], 400);
}