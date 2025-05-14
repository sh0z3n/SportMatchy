<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/session.php';

Session::start();
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$db = Database::getInstance();

// Get available sports
$sports = $db->query('SELECT id, name FROM sports ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $sport_id = (int)($_POST['sport_id'] ?? 0);
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $max_participants = (int)($_POST['max_participants'] ?? 0);

    if (!$title || !$sport_id || !$start_time || !$end_time || !$location) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            $db->query(
                'INSERT INTO events (title, sport_id, creator_id, start_time, end_time, location, description, max_participants) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$title, $sport_id, Session::getUserId(), $start_time, $end_time, $location, $description, $max_participants]
            );
            header('Location: events.php');
            exit;
        } catch (Exception $e) {
            $error = "Une erreur est survenue lors de la création de l'événement.";
            error_log($e->getMessage());
        }
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
                <label for="sport_id">Sport *</label>
                <select id="sport_id" name="sport_id" required>
                    <option value="">Sélectionner un sport</option>
                    <?php foreach ($sports as $sport): ?>
                        <option value="<?= $sport['id'] ?>"><?= htmlspecialchars($sport['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Date et heure de début *</label>
                <input type="datetime-local" id="start_time" name="start_time" required>
            </div>
            <div class="form-group">
                <label for="end_time">Date et heure de fin *</label>
                <input type="datetime-local" id="end_time" name="end_time" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="location">Lieu *</label>
                <input type="text" id="location" name="location" required>
            </div>
            <div class="form-group">
                <label for="max_participants">Nombre maximum de participants</label>
                <input type="number" id="max_participants" name="max_participants" min="2" value="10">
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Créer l'événement</button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html> 