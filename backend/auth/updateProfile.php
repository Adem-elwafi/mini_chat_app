<?php
// backend/auth/updateProfile.php
session_start();
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validation des données
$errors = [];
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$profile_info = trim($input['profile_info'] ?? '');

if (empty($username)) {
    $errors[] = 'Username is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Connexion à la base de données via le singleton existant
require_once '../config/db.php';

try {
    $pdo = Database::getInstance();
    
    // Vérifier si l'email existe déjà pour un autre utilisateur
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    // Mettre à jour le profil
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_info = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$username, $email, $profile_info, $_SESSION['user_id']]);
    
    // Récupérer les données mises à jour
    $stmt = $pdo->prepare("SELECT id, username, email, profile_info FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Mettre à jour la session
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $user
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}