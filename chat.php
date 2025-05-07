<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'send':
        $message = $_POST['message'] ?? '';
        $groupId = $_POST['group_id'] ?? 1; // Default to group 1 if not specified
        
        if (!empty($message)) {
            $db = Database::getInstance();
            $userId = Session::getUserId();
            $username = Session::getUsername();
            
            $stmt = $db->query(
                "INSERT INTO chat_messages (user_id, group_id, message, created_at) VALUES (?, ?, ?, NOW())",
                [$userId, $groupId, $message]
            );
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $db->lastInsertId(),
                    'user_id' => $userId,
                    'username' => $username,
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        }
        break;
        
    case 'get_messages':
        $groupId = $_GET['group_id'] ?? 1;
        $lastId = $_GET['last_id'] ?? 0;
        
        $db = Database::getInstance();
        $messages = $db->query(
            "SELECT m.*, u.username 
             FROM chat_messages m 
             JOIN users u ON m.user_id = u.id 
             WHERE m.group_id = ? AND m.id > ? 
             ORDER BY m.created_at DESC 
             LIMIT 50",
            [$groupId, $lastId]
        );
        
        echo json_encode([
            'success' => true,
            'messages' => array_reverse($messages)
        ]);
        break;
        
    case 'get_groups':
        $db = Database::getInstance();
        $groups = $db->query("SELECT * FROM chat_groups ORDER BY name");
        
        echo json_encode([
            'success' => true,
            'groups' => $groups
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
} 