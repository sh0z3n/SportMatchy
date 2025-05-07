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
    <div class="events-header">
        <h1>Événements sportifs</h1>
        <a href="create-event.php" class="btn btn-primary">Créer un événement</a>
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
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html> 