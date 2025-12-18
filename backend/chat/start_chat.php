<?php
require_once '../config/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = getUserID();
$other_user_id = $data['user_id'] ?? null;

if (!$other_user_id) {
    jsonResponse(['error' => 'User ID is required'], 400);
}

try {
    $conn = Database::getInstance();
    
    // Check if other user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$other_user_id]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    // Check if chat already exists
    $stmt = $conn->prepare("
        SELECT id FROM chats 
        WHERE (user1_id = ? AND user2_id = ?) 
           OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $existing_chat = $stmt->fetch();
    
    if ($existing_chat) {
        $chat_id = $existing_chat['id'];
    } else {
        // Create new chat (always store smaller id as user1_id for consistency)
        $user1_id = min($user_id, $other_user_id);
        $user2_id = max($user_id, $other_user_id);
        
        $stmt = $conn->prepare("INSERT INTO chats (user1_id, user2_id) VALUES (?, ?)");
        $stmt->execute([$user1_id, $user2_id]);
        $chat_id = $conn->lastInsertId();
    }
    
    // Get chat details with user info
    $stmt = $conn->prepare("
        SELECT c.*, 
               u1.username as user1_name, 
               u2.username as user2_name
        FROM chats c
        JOIN users u1 ON c.user1_id = u1.id
        JOIN users u2 ON c.user2_id = u2.id
        WHERE c.id = ?
    ");
    $stmt->execute([$chat_id]);
    $chat = $stmt->fetch();
    
    jsonResponse(['success' => true, 'chat' => $chat]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>