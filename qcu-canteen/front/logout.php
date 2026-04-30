<?php
require_once '../config.php';

// Wipe session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
              $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

setFlash('success', 'You have been logged out successfully.');
redirect('index.php');
