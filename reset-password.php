<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';

// Start session
Session::start();

// Redirect if already logged in
if (Session::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

$db = Database::getInstance();
$reset = $db->query(
    "SELECT pr.*, u.email, u.username 
     FROM password_resets pr 
     JOIN users u ON pr.user_id = u.id 
     WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0",
    [$token]
)->fetch();

if (!$reset) {
    $error = 'Le lien de réinitialisation est invalide ou a expiré.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        // Update password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $db->query(
            "UPDATE users SET password = ? WHERE id = ?",
            [$password_hash, $reset['user_id']]
        );

        // Mark reset token as used
        $db->query(
            "UPDATE password_resets SET used = 1 WHERE id = ?",
            [$reset['id']]
        );

        $success = 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.';
    }
}

// Generate CSRF token
$csrf_token = Session::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Réinitialisation du mot de passe</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-form-container">
                <h1>Réinitialisation du mot de passe</h1>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <div class="mt-3">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Se connecter
                            </a>
                        </div>
                    </div>
                <?php elseif (!$error): ?>
                    <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-group">
                            <label for="password">Nouveau mot de passe</label>
                            <input type="password" id="password" name="password" required>
                            <small>Le mot de passe doit contenir au moins 8 caractères.</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-key"></i>
                            Réinitialiser le mot de passe
                        </button>
                    </form>
                <?php endif; ?>

                <div class="auth-links">
                    <a href="login.php">Retour à la connexion</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/chatbot.js"></script>
</body>
</html> 