<?php
// backend/auth/logout.php
session_start();

// Détruire toutes les données de session
$_SESSION = array();

// Si vous voulez détruire complètement la session, effacez aussi le cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Répondre avec JSON pour le JavaScript
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
exit();