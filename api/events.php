<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Start session
Session::start();

// Check if user is logged in for protected actions
$protectedActions = ['create', 'join', 'leave', 'delete', 'update'];
if (in_array($_GET['action'] ?? '', $protectedActions) && !Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            // Get filter parameters
            $sportId = $_GET['sport_id'] ?? '';
            $date = $_GET['date'] ?? '';
            $search = $_GET['search'] ?? '';
            
            // Build query
            $query = "SELECT e.*, s.name as sport_name, u.username as creator_name,
                             (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as participant_count
                      FROM events e 
                      JOIN sports s ON e.sport_id = s.id 
                      JOIN users u ON e.creator_id = u.id 
                      WHERE e.event_date >= NOW()";
            $params = [];
            
            if ($sportId) {
                $query .= " AND e.sport_id = ?";
                $params[] = $sportId;
            }
            
            if ($date) {
                $query .= " AND DATE(e.event_date) = ?";
                $params[] = $date;
            }
            
            if ($search) {
                $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $query .= " ORDER BY e.event_date ASC";
            
            $events = $db->query($query, $params)->fetchAll();
            echo json_encode(['success' => true, 'events' => $events]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des événements']);
        }
        break;
        
    case 'create':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['title', 'description', 'sport_id', 'location', 'event_date', 'max_participants'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Le champ '$field' est requis");
                }
            }
            
            // Validate event date
            if (strtotime($data['event_date']) <= time()) {
                throw new Exception("La date de l'événement doit être dans le futur");
            }
            
            // Create event
            $eventId = $db->insert('events', [
                'title' => $data['title'],
                'description' => $data['description'],
                'sport_id' => $data['sport_id'],
                'creator_id' => Session::getUserId(),
                'location' => $data['location'],
                'event_date' => $data['event_date'],
                'max_participants' => $data['max_participants']
            ]);
            
            // Add creator as first participant
            $db->insert('event_participants', [
                'event_id' => $eventId,
                'user_id' => Session::getUserId(),
                'status' => 'joined'
            ]);
            
            echo json_encode(['success' => true, 'event_id' => $eventId]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'join':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $eventId = $data['event_id'] ?? 0;
            
            // Check if event exists and is not full
            $event = $db->query(
                "SELECT * FROM events WHERE id = ? AND event_date > NOW()",
                [$eventId]
            )->fetch();
            
            if (!$event) {
                throw new Exception('Événement non trouvé ou terminé');
            }
            
            // Check if user is already a participant
            $isParticipant = $db->query(
                "SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND user_id = ?",
                [$eventId, Session::getUserId()]
            )->fetchColumn();
            
            if ($isParticipant) {
                throw new Exception('Vous participez déjà à cet événement');
            }
            
            // Check if event is full
            $participantCount = $db->query(
                "SELECT COUNT(*) FROM event_participants WHERE event_id = ?",
                [$eventId]
            )->fetchColumn();
            
            if ($participantCount >= $event['max_participants']) {
                throw new Exception("L'événement est complet");
            }
            
            // Add participant
            $db->insert('event_participants', [
                'event_id' => $eventId,
                'user_id' => Session::getUserId(),
                'status' => 'joined'
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'leave':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $eventId = $data['event_id'] ?? 0;
            
            // Check if user is a participant
            $isParticipant = $db->query(
                "SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND user_id = ?",
                [$eventId, Session::getUserId()]
            )->fetchColumn();
            
            if (!$isParticipant) {
                throw new Exception('Vous ne participez pas à cet événement');
            }
            
            // Remove participant
            $db->query(
                "DELETE FROM event_participants WHERE event_id = ? AND user_id = ?",
                [$eventId, Session::getUserId()]
            );
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'delete':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $eventId = $data['event_id'] ?? 0;
            
            // Check if user is the creator
            $event = $db->query(
                "SELECT * FROM events WHERE id = ?",
                [$eventId]
            )->fetch();
            
            if (!$event || $event['creator_id'] !== Session::getUserId()) {
                throw new Exception('Non autorisé');
            }
            
            // Delete event and participants
            $db->query("DELETE FROM event_participants WHERE event_id = ?", [$eventId]);
            $db->query("DELETE FROM events WHERE id = ?", [$eventId]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'update':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $eventId = $data['event_id'] ?? 0;
            
            // Check if user is the creator
            $event = $db->query(
                "SELECT * FROM events WHERE id = ?",
                [$eventId]
            )->fetch();
            
            if (!$event || $event['creator_id'] !== Session::getUserId()) {
                throw new Exception('Non autorisé');
            }
            
            // Update allowed fields
            $allowedFields = ['title', 'description', 'location', 'event_date', 'max_participants'];
            $updates = array_intersect_key($data, array_flip($allowedFields));
            
            if (!empty($updates)) {
                $db->update('events', $updates, ['id' => $eventId]);
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non valide']);
} 