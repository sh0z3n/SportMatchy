<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
header('Content-Type: application/json');
$db = Database::getInstance();
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $event = $db->query('SELECT * FROM events WHERE id = ?', [$id])->fetch();
    if ($event) {
        echo json_encode($event);
    } else {
        echo json_encode(['error' => 'Event not found']);
    }
    exit;
}
$events = $db->query('SELECT id, title, start_time as start FROM events')->fetchAll();
echo json_encode($events); 