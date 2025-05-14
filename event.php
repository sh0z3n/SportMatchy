<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="container"><div class="alert alert-error">Événement non trouvé.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
$eventId = (int)$_GET['id'];
$db = Database::getInstance();
$event = $db->query('SELECT e.*, s.name as sport_name, s.icon as sport_icon, u.username as creator_name FROM events e LEFT JOIN sports s ON e.sport_id = s.id LEFT JOIN users u ON e.creator_id = u.id WHERE e.id = ?', [$eventId])->fetch();
if (!$event) {
    echo '<div class="container"><div class="alert alert-error">Événement non trouvé.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<main class="container">
    <div class="event-details-section">
        <div class="event-details-header">
            <h1><?= htmlspecialchars($event['title']) ?></h1>
            <div class="event-meta">
                <span class="sport-badge"><i class="fas fa-<?= htmlspecialchars($event['sport_icon'] ?? 'running') ?>"></i> <?= htmlspecialchars($event['sport_name']) ?></span>
                <span class="event-date"><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($event['start_time'])) ?> - <?= date('H:i', strtotime($event['end_time'])) ?></span>
                <span class="event-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></span>
                <span class="event-organizer"><i class="fas fa-user"></i> Organisateur : <?= htmlspecialchars($event['creator_name']) ?></span>
                <span class="event-status"><i class="fas fa-info-circle"></i> <?= ucfirst($event['status']) ?></span>
            </div>
        </div>

        <div class="event-details-content">
            <div class="event-details-info">
                <div class="event-details-description">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>

                <?php if (Session::isLoggedIn()): ?>
                    <?php
                    $eventIdDebug = $eventId;
                    $userIdDebug = Session::getUserId();
                    $isParticipating = $db->query('SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND user_id = ?', [$eventId, $userIdDebug])->fetchColumn() > 0;
                    ?>
                    <div class="event-details-actions">
                        <?php if ($isParticipating): ?>
                            <button class="btn btn-success" disabled>Vous participez déjà</button>
                        <?php else: ?>
                            <form id="participate-form-desc" method="post" action="api/join-event.php">
                                <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo Session::generateCSRFToken(); ?>">
                                <button type="submit" class="btn btn-primary" id="participate-btn-desc">Participer</button>
                            </form>
                        <?php endif; ?>
                        <a href="/chat.php?event_id=<?php echo $eventId; ?>" class="btn btn-secondary">Chat de l'événement</a>
                        <a href="#" class="btn btn-outline" onclick="addToCalendar(); return false;">
                            <i class="fas fa-calendar-plus"></i> Ajouter au calendrier
                        </a>
                    </div>
                    <div id="participate-message-desc" class="alert"></div>
                <?php endif; ?>
            </div>

            <div class="event-details-participants">
                <h2>Participants</h2>
                <div id="participants-list" class="participants-list">
                    <?php
                    $participants = $db->query('SELECT u.username FROM event_participants ep JOIN users u ON ep.user_id = u.id WHERE ep.event_id = ?', [$eventId])->fetchAll();
                    if ($participants):
                        echo '<ul>';
                        foreach ($participants as $p) {
                            echo '<li>' . htmlspecialchars($p['username']) . '</li>';
                        }
                        echo '</ul>';
                    else:
                        echo '<p>Aucun participant pour le moment.</p>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <a href="events.php" class="btn btn-outline">Retour aux événements</a>
</main>

<script>
function addToCalendar() {
    const event = {
        title: '<?= addslashes($event['title']) ?>',
        description: '<?= addslashes($event['description']) ?>',
        location: '<?= addslashes($event['location']) ?>',
        startTime: '<?= date('Ymd\THis', strtotime($event['start_time'])) ?>',
        endTime: '<?= date('Ymd\THis', strtotime($event['end_time'])) ?>'
    };

    // Create ICS file content
    const icsContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'BEGIN:VEVENT',
        `DTSTART:${event.startTime}`,
        `DTEND:${event.endTime}`,
        `SUMMARY:${event.title}`,
        `DESCRIPTION:${event.description}`,
        `LOCATION:${event.location}`,
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\r\n');

    // Create blob and download link
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'event.ics';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

document.getElementById('participate-form-desc')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('participate-btn-desc');
    const messageDiv = document.getElementById('participate-message-desc');
    btn.disabled = true;
    btn.textContent = '...';
    
    fetch('api/join-event.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.className = 'btn btn-success';
            btn.textContent = 'Participation confirmée !';
            btn.disabled = true;
            messageDiv.className = 'alert alert-success';
            messageDiv.textContent = 'Vous participez maintenant à cet événement !';
        } else {
            btn.disabled = false;
            btn.textContent = 'Participer';
            messageDiv.className = 'alert alert-error';
            messageDiv.textContent = data.error || 'Erreur lors de la participation.';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Participer';
        messageDiv.className = 'alert alert-error';
        messageDiv.textContent = 'Erreur réseau.';
    });
});
</script>

<style>
.event-details-section {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: var(--spacing-lg);
    margin: var(--spacing-lg) 0;
}

.event-details-header {
    margin-bottom: var(--spacing-lg);
}

.event-details-header h1 {
    font-size: 2rem;
    color: var(--text);
    margin-bottom: var(--spacing-md);
}

.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.event-meta span {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    font-size: 0.9rem;
}

.event-meta i {
    color: var(--primary);
}

.event-details-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-lg);
}

.event-details-description {
    background: var(--bg-secondary);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
}

.event-details-description h2 {
    color: var(--text);
    margin-bottom: var(--spacing-md);
}

.event-details-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

.event-details-participants {
    background: var(--bg-secondary);
    padding: var(--spacing-lg);
    border-radius: var(--border-radius);
}

.event-details-participants h2 {
    color: var(--text);
    margin-bottom: var(--spacing-md);
}

.participants-list ul {
    list-style: none;
    padding: 0;
}

.participants-list li {
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--border-color);
}

.participants-list li:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .event-details-content {
        grid-template-columns: 1fr;
    }
    
    .event-meta {
        flex-direction: column;
    }
    
    .event-details-actions {
        flex-direction: column;
    }
    
    .event-details-actions .btn {
        width: 100%;
    }
}
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html> 