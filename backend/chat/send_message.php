<?php
require_once '../config/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = getUserID();

$chat_id = $data['chat_id'] ?? null;
$content = trim($data['content'] ?? '');

if (!$chat_id) {
    jsonResponse(['error' => 'Chat ID is required'], 400);
}

if (empty($content)) {
    jsonResponse(['error' => 'Message content is required'], 400);
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
    
    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO messages (chat_id, sender_id, content) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$chat_id, $user_id, $content]);
    $message_id = $conn->lastInsertId();
    
    // Get the inserted message with sender info
    $stmt = $conn->prepare("
        SELECT m.*, u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();
    
    jsonResponse([
        'success' => true, 
        'message' => 'Message sent',
        'message_data' => $message
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>