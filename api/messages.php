<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

Session::start();
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$groupId = filter_input(INPUT_GET, 'groupId', FILTER_VALIDATE_INT);
if (!$groupId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid group ID']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT 1 FROM chat_group_members 
         WHERE group_id = ? AND user_id = ?"
    );
    $stmt->execute([$groupId, Session::getUserId()]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not a member of this group']);
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT m.*, u.username, u.profile_image 
         FROM chat_messages m 
         JOIN users u ON m.user_id = u.id 
         WHERE m.group_id = ? 
         ORDER BY m.created_at DESC 
         LIMIT 50"
    );
    $stmt->execute([$groupId]);
    $messages = $stmt->fetchAll();

    $formattedMessages = array_map(function($message) {
        return [
            'id' => $message['id'],
            'user_id' => $message['user_id'],
            'username' => $message['username'],
            'profile_image' => $message['profile_image'],
            'message' => $message['message'],
            'created_at' => $message['created_at']
        ];
    }, $messages);

    echo json_encode($formattedMessages);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log($e->getMessage());
} 