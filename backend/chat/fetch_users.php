<?php
require_once '../config/db.php';
requireLogin();

$user_id = getUserID();

try {
    $conn = Database::getInstance();
    
    // Fetch all users except current user
    $stmt = $conn->prepare("
        SELECT id, username, email, profile_info, created_at 
        FROM users 
        WHERE id != ? 
        ORDER BY username ASC
    ");
    $stmt->execute([$user_id]);
    $users = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'users' => $users]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>