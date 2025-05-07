<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/database.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $sport = trim($_POST['sport'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (!$title || !$sport || !$event_date || !$location) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $db = Database::getInstance();
        $db->query('INSERT INTO events (title, sport, event_date, location, description) VALUES (?, ?, ?, ?, ?)', [$title, $sport, $event_date, $location, $description]);
        header('Location: events.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un événement - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<section class="create-event-section container">
    <h1>Créer un événement</h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="create-event-form" method="post">
        <div class="form-row">
            <div class="form-group">
                <label for="title">Titre *</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="sport">Sport *</label>
                <input type="text" id="sport" name="sport" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="event_date">Date et heure *</label>
                <input type="datetime-local" id="event_date" name="event_date" required>
            </div>
            <div class="form-group">
                <label for="location">Lieu *</label>
                <input type="text" id="location" name="location" required>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Créer</button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html> 