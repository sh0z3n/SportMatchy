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
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Veuillez entrer votre adresse email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } else {
        $db = Database::getInstance();
        $user = $db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();

        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $db->query(
                "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
                [$user['id'], $token, $expires]
            );

            // Send reset email
            $reset_link = "http://{$_SERVER['HTTP_HOST']}/reset-password.php?token=" . $token;
            $to = $user['email'];
            $subject = APP_NAME . " - Réinitialisation de mot de passe";
            $message = "Bonjour {$user['username']},\n\n";
            $message .= "Vous avez demandé la réinitialisation de votre mot de passe. ";
            $message .= "Cliquez sur le lien suivant pour définir un nouveau mot de passe :\n\n";
            $message .= $reset_link . "\n\n";
            $message .= "Ce lien expirera dans 1 heure.\n\n";
            $message .= "Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.\n\n";
            $message .= "Cordialement,\nL'équipe " . APP_NAME;

            $headers = "From: " . APP_EMAIL . "\r\n";
            $headers .= "Reply-To: " . APP_EMAIL . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if (mail($to, $subject, $message, $headers)) {
                $success = 'Un email de réinitialisation a été envoyé à votre adresse email.';
                $email = '';
            } else {
                $error = 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.';
            }
        } else {
            // Don't reveal if email exists or not
            $success = 'Si votre adresse email est enregistrée, vous recevrez un email de réinitialisation.';
            $email = '';
        }
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
    <title><?php echo APP_NAME; ?> - Mot de passe oublié</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-form-container">
                <h1>Mot de passe oublié</h1>

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
                    </div>
                <?php endif; ?>

                <form method="POST" action="forgot-password.php" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <small>Entrez l'adresse email associée à votre compte.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer le lien de réinitialisation
                    </button>
                </form>

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