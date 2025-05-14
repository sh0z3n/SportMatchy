<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';
header('Content-Type: application/json');
Session::start();
if (!Session::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}
$db = Database::getInstance();
$userId = Session::getUserId();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $groupId = $_GET['group_id'] ?? 0;
    if (!$groupId) {
        echo json_encode(['success' => false, 'error' => 'Groupe manquant']);
        exit;
    }
    $messages = $db->query('SELECT m.*, u.username FROM chat_messages m JOIN users u ON m.user_id = u.id WHERE m.group_id = ? ORDER BY m.created_at ASC LIMIT 50', [$groupId])->fetchAll();
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $groupId = $data['group_id'] ?? 0;
    $message = trim($data['message'] ?? '');
    if (!$groupId || !$message) {
        echo json_encode(['success' => false, 'error' => 'Message ou groupe manquant']);
        exit;
    }
    // Check if user is a member
    $isMember = $db->query('SELECT COUNT(*) FROM chat_group_members WHERE group_id = ? AND user_id = ?', [$groupId, $userId])->fetchColumn();
    if (!$isMember) {
        echo json_encode(['success' => false, 'error' => 'Vous devez rejoindre le groupe']);
        exit;
    }
    $db->query('INSERT INTO chat_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())', [$groupId, $userId, $message]);
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'error' => 'Méthode non supportée']); 