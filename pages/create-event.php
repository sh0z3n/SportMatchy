<?php
// Vérification de l'authentification
if (!$isLoggedIn) {
    $_SESSION['flash_message'] = 'Vous devez être connecté pour créer un événement';
    $_SESSION['flash_type'] = 'warning';
    header('Location: /?page=login');
    exit;
}

// Récupération des sports disponibles
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM sports ORDER BY name");
$sports = $stmt->fetchAll();

// Traitement du formulaire de création d'événement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $sportId = $_POST['sport_id'] ?? '';
    $location = $_POST['location'] ?? '';
    $maxParticipants = $_POST['max_participants'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $errors = [];

    // Validation des données
    if (empty($title)) {
        $errors[] = "Le titre est requis";
    }

    if (empty($sportId)) {
        $errors[] = "Le sport est requis";
    }

    if (empty($location)) {
        $errors[] = "Le lieu est requis";
    }

    if (!is_numeric($maxParticipants) || $maxParticipants < 2) {
        $errors[] = "Le nombre de participants doit être au moins 2";
    }

    if (empty($startTime)) {
        $errors[] = "La date de début est requise";
    }

    if (empty($endTime)) {
        $errors[] = "La date de fin est requise";
    }

    if (strtotime($endTime) <= strtotime($startTime)) {
        $errors[] = "La date de fin doit être postérieure à la date de début";
    }

    // Si pas d'erreurs, création de l'événement
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO events (
                title, description, sport_id, creator_id, location,
                max_participants, start_time, end_time, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");

        if ($stmt->execute([
            $title, $description, $sportId, $currentUser['id'],
            $location, $maxParticipants, $startTime, $endTime
        ])) {
            $eventId = $db->lastInsertId();

            // Ajout automatique du créateur comme participant
            $stmt = $db->prepare("
                INSERT INTO event_participants (event_id, user_id, status, joined_at)
                VALUES (?, ?, 'joined', NOW())
            ");
            $stmt->execute([$eventId, $currentUser['id']]);

            $_SESSION['flash_message'] = 'Événement créé avec succès !';
            $_SESSION['flash_type'] = 'success';
            header('Location: /?page=event-details&id=' . $eventId);
            exit;
        } else {
            $errors[] = "Une erreur est survenue lors de la création de l'événement";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Créer un événement</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/?page=create-event" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="title" class="form-label">Titre de l'événement</label>
                        <input type="text" class="form-control" id="title" name="title" required
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="sport_id" class="form-label">Sport</label>
                        <select class="form-select" id="sport_id" name="sport_id" required>
                            <option value="">Sélectionnez un sport</option>
                            <?php foreach ($sports as $sport): ?>
                                <option value="<?php echo $sport['id']; ?>"
                                    <?php echo (isset($_POST['sport_id']) && $_POST['sport_id'] == $sport['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sport['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php 
                            echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                        ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Lieu</label>
                        <input type="text" class="form-control" id="location" name="location" required
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Date et heure de début</label>
                            <input type="datetime-local" class="form-control" id="start_time" name="start_time" required
                                   value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : ''; ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">Date et heure de fin</label>
                            <input type="datetime-local" class="form-control" id="end_time" name="end_time" required
                                   value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="max_participants" class="form-label">Nombre maximum de participants</label>
                        <input type="number" class="form-control" id="max_participants" name="max_participants"
                               min="2" required
                               value="<?php echo isset($_POST['max_participants']) ? htmlspecialchars($_POST['max_participants']) : '2'; ?>">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Créer l'événement</button>
                        <a href="/?page=events" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Validation côté client
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Validation des dates
document.getElementById('start_time').addEventListener('change', function() {
    document.getElementById('end_time').min = this.value;
});

document.getElementById('end_time').addEventListener('change', function() {
    if (this.value <= document.getElementById('start_time').value) {
        this.setCustomValidity('La date de fin doit être postérieure à la date de début');
    } else {
        this.setCustomValidity('');
    }
});
</script> 