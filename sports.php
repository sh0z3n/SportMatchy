<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';

// Start session
Session::start();

$db = Database::getInstance();
$error = '';
$success = '';

// Get all sports
$sports = $db->query("SELECT * FROM sports ORDER BY name")->fetchAll();

// Get events count for each sport
foreach ($sports as &$sport) {
    $sport['event_count'] = $db->query(
        "SELECT COUNT(*) FROM events WHERE sport_id = ?",
        [$sport['id']]
    )->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="sports-section">
            <h1>Sports disponibles</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="sports-grid">
                <?php foreach ($sports as $sport): ?>
                    <div class="sport-card">
                        <i class="fas <?php echo $sport['icon']; ?>"></i>
                        <h3><?php echo htmlspecialchars($sport['name']); ?></h3>
                        <p class="sport-description">
                            <?php echo htmlspecialchars($sport['description']); ?>
                        </p>
                        <div class="sport-meta">
                            <span class="event-count">
                                <i class="fas fa-calendar"></i>
                                <?php echo $sport['event_count']; ?> événement(s)
                            </span>
                        </div>
                        <a href="events.php?sport=<?php echo $sport['id']; ?>" class="btn btn-primary">
                            Voir les événements
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html> 