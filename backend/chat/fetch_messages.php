<?php
require_once '../config/db.php';
requireLogin();

$chat_id = $_GET['chat_id'] ?? null;
$user_id = getUserID();

if (!$chat_id) {
    jsonResponse(['error' => 'Chat ID is required'], 400);
}

try {
    $conn = Database::getInstance();
    
    // Verify user has access to this chat
    $stmt = $conn->prepare("
        SELECT id FROM chats 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$chat_id, $user_id, $user_id]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    // Fetch messages
    $stmt = $conn->prepare("
        SELECT m.*, u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.chat_id = ?
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$chat_id]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = TRUE 
        WHERE chat_id = ? AND sender_id != ? AND is_read = FALSE
    ");
    $stmt->execute([$chat_id, $user_id]);
    
    jsonResponse(['success' => true, 'messages' => $messages]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>