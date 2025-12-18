<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$profile_info = trim($data['profile_info'] ?? '');

// Validation
$errors = [];
if (empty($username)) $errors[] = 'Username is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($password)) $errors[] = 'Password is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';

if (!empty($errors)) {
    jsonResponse(['errors' => $errors], 400);
}

try {
    $conn = Database::getInstance();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email already registered'], 400);
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Username already taken'], 400);
    }
    
    // Create user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, profile_info) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash, $profile_info]);
    
    $user_id = $conn->lastInsertId();
    
    // Auto login after registration
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    
    jsonResponse([
        'success' => true,
        'message' => 'Registration successful',
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