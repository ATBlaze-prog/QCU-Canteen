<?php
// ============================================================
// api/config.php
// ============================================================

// ---- SESSION FIRST — before any headers ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- DATABASE CONSTANTS ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qcu_canteen');

// ---- CORS + JSON HEADERS ----
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

function getDB(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        respond(['error' => 'Database connection failed: ' . $conn->connect_error], 500);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ============================================================
// HELPERS
// ============================================================

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function getBody(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function isLoggedIn(): bool {
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function getCurrentUser(): ?array {
    return (isset($_SESSION['user']) && is_array($_SESSION['user']))
        ? $_SESSION['user'] : null;
}

// Matches session set by front/login.php → config.php → full_name + role
function isAdmin(): bool {
    $u = $_SESSION['user'] ?? null;
    return is_array($u) && ($u['role'] ?? '') === 'admin';
}
