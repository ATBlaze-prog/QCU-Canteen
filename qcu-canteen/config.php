<?php
// ============================================================
// config.php — Root config: DB connection + session + helpers
// Include from front/ pages with: require_once '../config.php'
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default = empty
define('DB_NAME', 'qcu_canteen');

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * PDO connection (singleton).
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('
            <div style="font-family:sans-serif;padding:2rem;color:#721c24;
                        background:#f8d7da;border:1px solid #f5c6cb;margin:2rem;border-radius:4px">
                <strong>&#9888; Database Connection Error</strong><br><br>
                Make sure <strong>XAMPP</strong> (Apache + MySQL) is running and the database
                <code>' . DB_NAME . '</code> exists.<br><br>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }
    return $pdo;
}

/** Currently logged-in user array, or null. */
function currentUser(): ?array {
    return (isset($_SESSION['user']) && is_array($_SESSION['user']))
        ? $_SESSION['user']
        : null;
}

/** Redirect helper — relative to front/ folder. */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit();
}

/** Require login — redirect to login.php if not authenticated. */
function requireLogin(): void {
    if (!currentUser()) {
        $_SESSION['flash_error'] = 'Please log in to access that page.';
        redirect('login.php');
    }
}

/** Require admin role. */
function requireAdmin(): void {
    requireLogin();
    if ((currentUser()['role'] ?? '') !== 'admin') {
        redirect('index.php');
    }
}

/** Store a one-time flash message. */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash_' . $type] = $msg;
}

/** Retrieve and clear a flash message. */
function getFlash(string $type): string {
    $msg = $_SESSION['flash_' . $type] ?? '';
    unset($_SESSION['flash_' . $type]);
    return $msg;
}

/** Safe HTML output. */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
