<?php
session_start();
require_once '../includes/config.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['event_id']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$eventId = (int)$data['event_id'];
$message = trim($data['message']);

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Le message ne peut pas être vide']);
    exit;
}

// Vérification que l'utilisateur est participant à l'événement
$db = getDBConnection();
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM event_participants
    WHERE event_id = ? AND user_id = ? AND status = 'joined'
");
$stmt->execute([$eventId, $_SESSION['user_id']]);
if ($stmt->fetchColumn() === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Vous devez être participant pour envoyer des messages']);
    exit;
}

// Insertion du message
$stmt = $db->prepare("
    INSERT INTO messages (event_id, sender_id, content, created_at)
    VALUES (?, ?, ?, NOW())
");

if ($stmt->execute([$eventId, $_SESSION['user_id'], $message])) {
    // Récupération des informations du message
    $messageId = $db->lastInsertId();
    $stmt = $db->prepare("
        SELECT m.*, u.username, u.profile_picture
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$messageId]);
    $messageData = $stmt->fetch();

    // Envoi du message via WebSocket
    $wsMessage = [
        'type' => 'new_message',
        'event_id' => $eventId,
        'message' => [
            'id' => $messageData['id'],
            'content' => $messageData['content'],
            'username' => $messageData['username'],
            'profile_picture' => $messageData['profile_picture'],
            'created_at' => $messageData['created_at']
        ]
    ];

    // Connexion au serveur WebSocket
    $client = new WebSocket\Client("ws://" . WS_HOST . ":" . WS_PORT);
    $client->send(json_encode($wsMessage));
    $client->close();

    echo json_encode(['success' => true, 'message' => $messageData]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de l\'envoi du message']);
} 