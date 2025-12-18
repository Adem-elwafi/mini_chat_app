<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Not logged in'], 400);
}

// Clear session
$_SESSION = array();

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
?>