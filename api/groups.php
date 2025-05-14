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
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$groupId = $data['group_id'] ?? 0;
$userId = Session::getUserId();
$db = Database::getInstance();
if ($action === 'join' && $groupId) {
    $exists = $db->query('SELECT COUNT(*) FROM chat_group_members WHERE group_id = ? AND user_id = ?', [$groupId, $userId])->fetchColumn();
    if (!$exists) {
        $db->query('INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)', [$groupId, $userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}
if ($action === 'leave' && $groupId) {
    $db->query('DELETE FROM chat_group_members WHERE group_id = ? AND user_id = ?', [$groupId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'error' => 'Requête invalide']); 