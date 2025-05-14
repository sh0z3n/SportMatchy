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
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
    $eventId = $data['event_id'] ?? 0;
    $csrf = $data['csrf_token'] ?? '';
} else {
    $eventId = $_POST['event_id'] ?? 0;
    $csrf = $_POST['csrf_token'] ?? '';
}

if (!$eventId) {
    echo json_encode(['success' => false, 'error' => 'ID événement manquant']);
    exit;
}
if ($csrf && !Session::validateCSRFToken($csrf)) {
    echo json_encode(['success' => false, 'error' => 'CSRF token invalide']);
    exit;
}

$userId = Session::getUserId();
$db = Database::getInstance();
$exists = $db->query('SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND user_id = ?', [$eventId, $userId])->fetchColumn();
if ($exists) {
    echo json_encode(['success' => false, 'error' => 'Déjà participant']);
    exit;
}
$db->query('INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, ?)', [$eventId, $userId, 'confirmed']);
echo json_encode(['success' => true]); 