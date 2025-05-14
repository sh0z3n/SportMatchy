<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/database.php';
// Récupérer les événements depuis la base de données
$db = Database::getInstance();
$events = $db->query('SELECT e.*, s.name as sport_name FROM events e LEFT JOIN sports s ON e.sport_id = s.id ORDER BY e.start_time DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<section class="events-section container">
    <div class="events-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1>Événements</h1>
        <a href="map.php" class="btn btn-outline" style="margin-left: auto;">
            <i class="fas fa-map-marked-alt"></i> Voir la carte des événements
        </a>
    </div>
    <div class="events-grid">
        <?php if (empty($events)): ?>
            <div class="no-events">
                <i class="fas fa-calendar-times"></i>
                <p>Aucun événement trouvé.</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-header">
                        <h3><?= htmlspecialchars($event['title']) ?></h3>
                        <span class="sport-badge"><?= htmlspecialchars($event['sport_name']) ?></span>
                    </div>
                    <div class="event-details">
                        <p><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($event['start_time'])) ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></p>
                    </div>
                    <div class="event-description">
                        <?= nl2br(htmlspecialchars($event['description'])) ?>
                    </div>
                    <div class="event-actions">
                        <a href="event.php?id=<?= $event['id'] ?>" class="btn btn-outline">Voir</a>
                        <a href="chat.php?event_id=<?= $event['id'] ?>" class="btn btn-secondary" style="margin-left:0.5rem;">Chat</a>
                        <form class="participate-form" method="post" action="api/join-event.php" style="display:inline;margin-left:0.5rem;">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button type="submit" class="btn btn-success">Participer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<script>
document.querySelectorAll('.participate-form').forEach(form => {
    form.onsubmit = function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        btn.disabled = true;
        btn.textContent = '...';
        const formData = new FormData(this);
        fetch('api/join-event.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.className = 'btn btn-success';
                btn.textContent = 'Participation confirmée !';
            } else {
                btn.disabled = false;
                btn.textContent = 'Participer';
                alert(data.error || 'Erreur lors de la participation.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'Participer';
            alert('Erreur réseau.');
        });
    };
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html> 