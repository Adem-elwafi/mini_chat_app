<?php
require_once '../config/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = getUserID();

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$profile_info = trim($data['profile_info'] ?? '');

if (empty($username) || empty($email)) {
    jsonResponse(['error' => 'Username and email are required'], 400);
}

try {
    $conn = Database::getInstance();
    
    // Check if email is taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email already registered by another user'], 400);
    }
    
    // Check if username is taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Username already taken'], 400);
    }
    
    // Update profile
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, profile_info = ? WHERE id = ?");
    $stmt->execute([$username, $email, $profile_info, $user_id]);
    
    // Update session
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    
    jsonResponse([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'id' => $user_id,
            'username' => $username,
            'email' => $email,
            'profile_info' => $profile_info
        ]
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>