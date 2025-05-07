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
    <h1><?= htmlspecialchars($event['title']) ?></h1>
    <div class="event-meta">
        <span class="sport-badge"><i class="fas fa-<?= htmlspecialchars($event['sport_icon'] ?? 'running') ?>"></i> <?= htmlspecialchars($event['sport_name']) ?></span>
        <span class="event-date"><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($event['start_time'])) ?> - <?= date('H:i', strtotime($event['end_time'])) ?></span>
        <span class="event-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></span>
        <span class="event-organizer"><i class="fas fa-user"></i> Organisateur : <?= htmlspecialchars($event['creator_name']) ?></span>
        <span class="event-status"><i class="fas fa-info-circle"></i> <?= ucfirst($event['status']) ?></span>
    </div>
    <div class="event-description">
        <h3>Description</h3>
        <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
    </div>
    <div class="event-participants">
        <h3>Participants</h3>
        <!-- Optionally, list participants here if you want -->
    </div>
    <a href="events.php" class="btn btn-outline">Retour aux événements</a>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html> 