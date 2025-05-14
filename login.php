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
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $db = Database::getInstance();
            $user = $db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Check if user is active
                if ($user['status'] !== 'active') {
                    $error = 'Votre compte est désactivé. Veuillez contacter l\'administrateur.';
                } else {
                    // Update last login
                    $db->query(
                        "UPDATE users SET last_login = NOW() WHERE id = ?",
                        [$user['id']]
                    );

                    // Login successful
                    Session::login($user['id'], $user['username'], $user['email'], $remember);
                    
                    // Redirect to intended page or home
                    $redirect = Session::get('redirect_after_login') ?? 'index.php';
                    Session::remove('redirect_after_login');
                    header("Location: $redirect");
                    exit;
                }
            } else {
                // Log failed login attempt
                $db->query(
                    "INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)",
                    [$email, $_SERVER['REMOTE_ADDR']]
                );
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $error = 'Une erreur est survenue. Veuillez réessayer plus tard.';
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
    <title><?php echo APP_NAME; ?> - Connexion</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-form-container">
                <h1>Connexion</h1>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($flash = Session::getFlash()): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group checkbox">
                        <label>
                            <input type="checkbox" name="remember">
                            Se souvenir de moi
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </button>
                </form>

                <div class="auth-links">
                    <a href="forgot-password.php">Mot de passe oublié ?</a>
                    <span class="separator">|</span>
                    <a href="register.php">Créer un compte</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/chatbot.js"></script>
</body>
</html> 