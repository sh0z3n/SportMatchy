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
$name = trim($data['name'] ?? '');
if (!$name) {
    echo json_encode(['success' => false, 'error' => 'Nom du groupe requis']);
    exit;
}
$db = Database::getInstance();
// Check if group already exists
$exists = $db->query('SELECT COUNT(*) FROM chat_groups WHERE name = ?', [$name])->fetchColumn();
if ($exists) {
    echo json_encode(['success' => false, 'error' => 'Ce nom de groupe existe déjà']);
    exit;
}
$db->query('INSERT INTO chat_groups (name) VALUES (?)', [$name]);
$groupId = $db->lastInsertId();
$db->query('INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)', [$groupId, Session::getUserId()]);
echo json_encode(['success' => true, 'group_id' => $groupId]); 