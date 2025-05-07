<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
if ($action === 'get_groups') {
    $stmt = $pdo->prepare("SELECT g.*, (SELECT message FROM chat_messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) as last_message FROM chat_groups g JOIN chat_group_members m ON g.id = m.group_id WHERE m.user_id = ? ORDER BY g.updated_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'groups' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if ($action === 'create_group') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $members = $data['members'] ?? [];
    if (!$name) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nom du groupe requis']);
        exit;
    }
    if (!in_array($user_id, $members)) $members[] = $user_id;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO chat_groups (name, created_by) VALUES (?, ?)");
        $stmt->execute([$name, $user_id]);
        $group_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)");
        foreach ($members as $mid) $stmt->execute([$group_id, $mid]);
        $pdo->commit();
        echo json_encode(['success' => true, 'group_id' => $group_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur création groupe']);
    }
    exit;
}
if ($action === 'get_messages') {
    $group_id = $_GET['group_id'] ?? 0;
    $last_id = $_GET['last_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_picture FROM chat_messages m JOIN users u ON m.user_id = u.id WHERE m.group_id = ? AND m.id > ? ORDER BY m.created_at ASC");
    $stmt->execute([$group_id, $last_id]);
    echo json_encode(['success' => true, 'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
if ($action === 'send_message') {
    $data = json_decode(file_get_contents('php://input'), true);
    $group_id = $data['group_id'] ?? 0;
    $message = $data['message'] ?? '';
    $type = $data['type'] ?? 'text';
    if (!$group_id || !$message) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message ou groupe manquant']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO chat_messages (group_id, user_id, message, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$group_id, $user_id, $message, $type]);
    $msg_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_picture FROM chat_messages m JOIN users u ON m.user_id = u.id WHERE m.id = ?");
    $stmt->execute([$msg_id]);
    echo json_encode(['success' => true, 'message' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    exit;
}
if ($action === 'dm') {
    $data = json_decode(file_get_contents('php://input'), true);
    $other_id = $data['user_id'] ?? 0;
    if (!$other_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Utilisateur requis']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT g.id FROM chat_groups g JOIN chat_group_members m1 ON g.id = m1.group_id JOIN chat_group_members m2 ON g.id = m2.group_id WHERE g.name = '' AND m1.user_id = ? AND m2.user_id = ? GROUP BY g.id HAVING COUNT(*) = 2");
    $stmt->execute([$user_id, $other_id]);
    $dm = $stmt->fetch();
    if ($dm) {
        echo json_encode(['success' => true, 'group_id' => $dm['id']]);
        exit;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO chat_groups (name, created_by) VALUES ('', ?)");
    $stmt->execute([$user_id]);
    $group_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)");
    $stmt->execute([$group_id, $user_id]);
    $stmt->execute([$group_id, $other_id]);
    $pdo->commit();
    echo json_encode(['success' => true, 'group_id' => $group_id]);
    exit;
}
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action non valide']); 