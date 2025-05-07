<?php
// Vérification de l'ID de l'événement
if (!isset($_GET['id'])) {
    header('Location: /?page=events');
    exit;
}

$eventId = (int)$_GET['id'];
$db = getDBConnection();

// Récupération des détails de l'événement
$stmt = $db->prepare("
    SELECT e.*, s.name as sport_name, s.icon as sport_icon,
           u.username as creator_name, u.profile_picture as creator_picture,
           COUNT(ep.user_id) as participant_count
    FROM events e
    JOIN sports s ON e.sport_id = s.id
    JOIN users u ON e.creator_id = u.id
    LEFT JOIN event_participants ep ON e.id = ep.event_id AND ep.status = 'joined'
    WHERE e.id = ?
    GROUP BY e.id
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['flash_message'] = 'Événement non trouvé';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /?page=events');
    exit;
}

// Récupération des participants
$stmt = $db->prepare("
    SELECT u.id, u.username, u.profile_picture, ep.joined_at
    FROM event_participants ep
    JOIN users u ON ep.user_id = u.id
    WHERE ep.event_id = ? AND ep.status = 'joined'
    ORDER BY ep.joined_at ASC
");
$stmt->execute([$eventId]);
$participants = $stmt->fetchAll();

// Récupération des messages
$stmt = $db->prepare("
    SELECT m.*, u.username, u.profile_picture
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.event_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$eventId]);
$messages = $stmt->fetchAll();

// Vérification si l'utilisateur est participant
$isParticipant = false;
$userStatus = null;
if ($isLoggedIn) {
    $stmt = $db->prepare("
        SELECT status
        FROM event_participants
        WHERE event_id = ? AND user_id = ?
    ");
    $stmt->execute([$eventId, $currentUser['id']]);
    $userStatus = $stmt->fetchColumn();
    $isParticipant = $userStatus === 'joined';
}

// Traitement de l'inscription/désinscription
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['join'])) {
        if ($event['participant_count'] < $event['max_participants']) {
            $stmt = $db->prepare("
                INSERT INTO event_participants (event_id, user_id, status, joined_at)
                VALUES (?, ?, 'joined', NOW())
                ON DUPLICATE KEY UPDATE status = 'joined', joined_at = NOW()
            ");
            if ($stmt->execute([$eventId, $currentUser['id']])) {
                $_SESSION['flash_message'] = 'Vous avez rejoint l\'événement !';
                $_SESSION['flash_type'] = 'success';
                header('Location: /?page=event-details&id=' . $eventId);
                exit;
            }
        } else {
            $_SESSION['flash_message'] = 'L\'événement est complet';
            $_SESSION['flash_type'] = 'warning';
        }
    } elseif (isset($_POST['leave'])) {
        $stmt = $db->prepare("
            UPDATE event_participants
            SET status = 'declined'
            WHERE event_id = ? AND user_id = ?
        ");
        if ($stmt->execute([$eventId, $currentUser['id']])) {
            $_SESSION['flash_message'] = 'Vous avez quitté l\'événement';
            $_SESSION['flash_type'] = 'info';
            header('Location: /?page=event-details&id=' . $eventId);
            exit;
        }
    }
}

// Traitement des messages
if ($isLoggedIn && $isParticipant && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $db->prepare("
            INSERT INTO messages (event_id, sender_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        if ($stmt->execute([$eventId, $currentUser['id'], $message])) {
            // Le message sera affiché via AJAX
        }
    }
}
?>

<div class="row">
    <!-- Détails de l'événement -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                
                <div class="mb-3">
                    <span class="badge bg-primary">
                        <i class="fas fa-<?php echo $event['sport_icon'] ?? 'running'; ?>"></i>
                        <?php echo htmlspecialchars($event['sport_name']); ?>
                    </span>
                    <span class="badge bg-<?php echo $event['status'] === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($event['status']); ?>
                    </span>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p>
                            <i class="fas fa-calendar"></i>
                            <strong>Date :</strong><br>
                            <?php echo date('d/m/Y H:i', strtotime($event['start_time'])); ?> -
                            <?php echo date('H:i', strtotime($event['end_time'])); ?>
                        </p>
                        <p>
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Lieu :</strong><br>
                            <?php echo htmlspecialchars($event['location']); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <i class="fas fa-users"></i>
                            <strong>Participants :</strong><br>
                            <?php echo $event['participant_count']; ?>/<?php echo $event['max_participants']; ?>
                        </p>
                        <p>
                            <i class="fas fa-user"></i>
                            <strong>Organisateur :</strong><br>
                            <a href="/?page=profile&id=<?php echo $event['creator_id']; ?>">
                                <?php echo htmlspecialchars($event['creator_name']); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="mb-4">
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>

                <div class="event-actions">
                    <?php if (Session::isLoggedIn()): ?>
                        <?php if ($event['creator_id'] === Session::getUserId()): ?>
                            <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <button onclick="deleteEvent(<?php echo $event['id']; ?>)" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        <?php else: ?>
                            <?php if ($isParticipant): ?>
                                <button onclick="leaveEvent(<?php echo $event['id']; ?>)" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt"></i> Quitter
                                </button>
                            <?php else: ?>
                                <button onclick="joinEvent(<?php echo $event['id']; ?>)" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Participer
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Connectez-vous pour participer</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Participants -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Participants</h5>
                <div class="row g-3">
                    <?php foreach ($participants as $participant): ?>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $participant['profile_picture'] ?? '/assets/images/default-avatar.png'; ?>"
                                     class="rounded-circle me-2" width="40" height="40" alt="Profile">
                                <div>
                                    <a href="/?page=profile&id=<?php echo $participant['id']; ?>">
                                        <?php echo htmlspecialchars($participant['username']); ?>
                                    </a>
                                    <small class="d-block text-muted">
                                        Inscrit le <?php echo date('d/m/Y', strtotime($participant['joined_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Discussion</h5>
                
                <?php if ($isLoggedIn && $isParticipant): ?>
                    <div id="chat-messages" class="mb-3" style="height: 400px; overflow-y: auto;">
                        <?php foreach ($messages as $message): ?>
                            <div class="message mb-2">
                                <div class="d-flex align-items-center mb-1">
                                    <img src="<?php echo $message['profile_picture'] ?? '/assets/images/default-avatar.png'; ?>"
                                         class="rounded-circle me-2" width="30" height="30" alt="Profile">
                                    <strong><?php echo htmlspecialchars($message['username']); ?></strong>
                                    <small class="text-muted ms-2">
                                        <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($message['content']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form id="chat-form" class="mt-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="message" name="message"
                                   placeholder="Votre message..." required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4">
                        <?php if (!$isLoggedIn): ?>
                            <p>Connectez-vous pour participer à la discussion</p>
                            <a href="/?page=login" class="btn btn-primary">Se connecter</a>
                        <?php else: ?>
                            <p>Rejoignez l'événement pour participer à la discussion</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isLoggedIn && $isParticipant): ?>
<script>
// Gestion du chat en temps réel
const chatMessages = document.getElementById('chat-messages');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message');

// Scroll vers le bas des messages
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Ajout d'un nouveau message
function addMessage(message) {
    const div = document.createElement('div');
    div.className = 'message mb-2';
    div.innerHTML = `
        <div class="d-flex align-items-center mb-1">
            <img src="${message.profile_picture || '/assets/images/default-avatar.png'}"
                 class="rounded-circle me-2" width="30" height="30" alt="Profile">
            <strong>${message.username}</strong>
            <small class="text-muted ms-2">${message.time}</small>
        </div>
        <p class="mb-0">${message.content}</p>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
}

// Envoi d'un message
chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (message) {
        fetch('/api/messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                event_id: <?php echo $eventId; ?>,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                addMessage({
                    username: '<?php echo $currentUser['username']; ?>',
                    profile_picture: '<?php echo $currentUser['profile_picture'] ?? ''; ?>',
                    content: message,
                    time: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
                });
            }
        });
    }
});

// Initial scroll
scrollToBottom();

function joinEvent(eventId) {
    fetch('api/events.php?action=join', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erreur lors de la participation');
        }
    });
}

function leaveEvent(eventId) {
    if (!confirm('Êtes-vous sûr de vouloir quitter cet événement ?')) return;
    
    fetch('api/events.php?action=leave', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erreur lors du départ');
        }
    });
}

function deleteEvent(eventId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) return;
    
    fetch('api/events.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'events.php';
        } else {
            alert(data.message || 'Erreur lors de la suppression');
        }
    });
}
</script>
<?php endif; ?> 