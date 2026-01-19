<?php
use Firebase\JWT\JWT;

// Enable debugging for this endpoint
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show in HTML, but log to PHP error log
ini_set('log_errors', 1);

// Set JSON header early
header('Content-Type: application/json');

try {
    // Step 1: Load dependencies
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Autoloader not found',
            'path' => $autoloadPath
        ]);
        exit();
    }
    require_once $autoloadPath;

    // Step 2: Load database helpers
    $dbPath = __DIR__ . '/../config/db.php';
    if (!file_exists($dbPath)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Database config not found',
            'path' => $dbPath
        ]);
        exit();
    }
    require_once $dbPath;

    // Step 3: Check if Firebase\JWT\JWT is available
    if (!class_exists('Firebase\JWT\JWT')) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Firebase JWT library not found. Run: composer require firebase/php-jwt'
        ]);
        exit();
    }

    // Step 4: Verify user is logged in
    requireLogin();

    // Step 5: Get user ID
    $userId = getUserID();
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'User ID not found in session']);
        exit();
    }

    // Step 6: Create JWT token with same secret as websocket-server/server.js
    // IMPORTANT: Must be 32+ chars for HS256 algorithm
    $JWT_SECRET = 'MiniChatApp_SecretKey_2025_v1_xyz';

    $payload = [
        'id' => $userId,
        'exp' => time() + 3600
    ];

    $token = JWT::encode($payload, $JWT_SECRET, 'HS256');

    // Debug: Log token generation
    error_log('[WS-TOKEN] Generated token for user ' . $userId . ', expires: ' . $payload['exp'] . ', token length: ' . strlen($token));

    // Step 7: Return token in JSON
    http_response_code(200);
    echo json_encode(['token' => $token]);
    exit();

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Token generation failed',
        'detail' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
}