<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';

// Start session
Session::start();

// Redirect if not logged in
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$userId = Session::getUserId();
$error = '';
$success = '';

// Get user data
$user = $db->query("SELECT * FROM users WHERE id = ?", [$userId])->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!Session::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Session expirée. Veuillez réessayer.');
        }

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $bio = $_POST['bio'] ?? '';

        if (empty($username) || empty($email)) {
            throw new Exception('Le nom d\'utilisateur et l\'email sont requis');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Adresse email invalide');
        }

        // Check if username is taken by another user
        if ($username !== $user['username']) {
            if ($db->exists('users', 'username = ? AND id != ?', [$username, $userId])) {
                throw new Exception('Ce nom d\'utilisateur est déjà pris');
            }
        }

        // Check if email is taken by another user
        if ($email !== $user['email']) {
            if ($db->exists('users', 'email = ? AND id != ?', [$email, $userId])) {
                throw new Exception('Cette adresse email est déjà utilisée');
            }
        }

        // Handle avatar upload
        $avatar = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Format d\'image non supporté. Utilisez JPG, PNG ou GIF.');
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                throw new Exception('L\'image est trop volumineuse. Maximum 5MB.');
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $uploadPath = 'uploads/avatars/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Erreur lors du téléchargement de l\'image');
            }

            // Delete old avatar if exists
            if ($user['avatar'] && file_exists($user['avatar'])) {
                unlink($user['avatar']);
            }

            $avatar = $uploadPath;
        }

        // Update user
        $db->update('users', [
            'username' => $username,
            'email' => $email,
            'bio' => $bio,
            'avatar' => $avatar
        ], 'id = ?', [$userId]);

        $success = 'Profil mis à jour avec succès';
        
        // Refresh user data
        $user = $db->query("SELECT * FROM users WHERE id = ?", [$userId])->fetch();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's events
$events = $db->query(
    "SELECT e.*, s.name as sport_name, s.icon as sport_icon 
    FROM events e 
    JOIN sports s ON e.sport_id = s.id 
    WHERE e.creator_id = ? 
    ORDER BY e.start_time DESC",
    [$userId]
)->fetchAll();

// Get user's participations
$participations = $db->query(
    "SELECT e.*, s.name as sport_name, s.icon as sport_icon, ep.status 
    FROM events e 
    JOIN sports s ON e.sport_id = s.id 
    JOIN event_participants ep ON e.id = ep.event_id 
    WHERE ep.user_id = ? 
    ORDER BY e.start_time DESC",
    [$userId]
)->fetchAll();

// Generate new CSRF token
$csrfToken = Session::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo $user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" alt="Avatar">
                    <form method="POST" enctype="multipart/form-data" class="avatar-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <label for="avatar" class="avatar-upload">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                    </form>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['username'] ?? ''); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    <p class="profile-bio"><?php echo htmlspecialchars($user['bio'] ?? 'Aucune biographie'); ?></p>
                </div>
            </div>

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

            <form method="POST" class="profile-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bio">Biographie</label>
                    <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Mettre à jour le profil</button>
                </div>
            </form>

            <div class="profile-events">
                <h2>Mes événements créés</h2>
                <?php if (empty($events)): ?>
                    <p class="no-events">Vous n'avez pas encore créé d'événements.</p>
                <?php else: ?>
                    <div class="event-list">
                        <?php foreach ($events as $event): ?>
                            <div class="event-card">
                                <div class="event-content">
                                    <div class="event-header">
                                        <i class="fas <?php echo $event['sport_icon']; ?>"></i>
                                        <h3 class="event-title"><?php echo htmlspecialchars($event['title'] ?? ''); ?></h3>
                                    </div>
                                    <div class="event-meta">
                                        <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($event['start_time'] ?? '')); ?></span>
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?? ''); ?></span>
                                    </div>
                                    <p class="event-description"><?php echo htmlspecialchars($event['description'] ?? ''); ?></p>
                                    <div class="event-footer">
                                        <span class="event-sport"><?php echo htmlspecialchars($event['sport_name'] ?? ''); ?></span>
                                        <a href="event.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">Voir détails</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h2>Mes participations</h2>
                <?php if (empty($participations)): ?>
                    <p class="no-events">Vous ne participez à aucun événement.</p>
                <?php else: ?>
                    <div class="event-list">
                        <?php foreach ($participations as $event): ?>
                            <div class="event-card">
                                <div class="event-content">
                                    <div class="event-header">
                                        <i class="fas <?php echo $event['sport_icon']; ?>"></i>
                                        <h3 class="event-title"><?php echo htmlspecialchars($event['title'] ?? ''); ?></h3>
                                    </div>
                                    <div class="event-meta">
                                        <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($event['start_time'] ?? '')); ?></span>
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?? ''); ?></span>
                                        <span class="status-badge <?php echo $event['status'] ?? ''; ?>"><?php echo ucfirst($event['status'] ?? ''); ?></span>
                                    </div>
                                    <p class="event-description"><?php echo htmlspecialchars($event['description'] ?? ''); ?></p>
                                    <div class="event-footer">
                                        <span class="event-sport"><?php echo htmlspecialchars($event['sport_name'] ?? ''); ?></span>
                                        <a href="event.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">Voir détails</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 1rem; text-align: right;">
                <a href="stats.php" class="btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> Voir mes statistiques
                </a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/chatbot.js"></script>
</body>
</html> 