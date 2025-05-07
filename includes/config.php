<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sportmatchy');
define('DB_USER', 'sportmatchy');
define('DB_PASS', 'sportmatchy123');

// Application configuration
define('APP_NAME', 'SportMatchy');
define('APP_URL', 'http://localhost:8000');
define('APP_EMAIL', 'contact@sportmatchy.com');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_LIFETIME', 2592000); // 30 days

// Security configuration
define('HASH_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes
define('TOKEN_LIFETIME', 3600); // 1 hour

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// Configuration de l'application
define('APP_VERSION', '1.0.0');

// Configuration des sessions
define('SESSION_NAME', 'sportmatchy_session');

// Configuration des emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@sportmatchy.com');
define('SMTP_FROM_NAME', 'SportMatchy');

// Configuration des uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Configuration des WebSockets
define('WS_HOST', 'localhost');
define('WS_PORT', 8080);
define('WS_SECURE', false);

// Configuration des timezones
date_default_timezone_set('Europe/Paris');

// Configuration des cookies
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

// Configuration des limites
define('MAX_EVENTS_PER_PAGE', 12);
define('MAX_PARTICIPANTS_PER_EVENT', 50);
define('MAX_MESSAGES_PER_PAGE', 50);

// Configuration des chemins
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Création des répertoires nécessaires
$directories = [
    UPLOAD_DIR,
    LOGS_PATH,
    UPLOAD_DIR . '/avatars',
    UPLOAD_DIR . '/events'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Fonction pour obtenir la configuration
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Fonction pour vérifier si l'application est en mode développement
function isDev() {
    return getConfig('APP_ENV', 'production') === 'development';
}

// Fonction pour obtenir l'URL complète
function getUrl($path = '') {
    return rtrim(getConfig('APP_URL'), '/') . '/' . ltrim($path, '/');
}

// Fonction pour obtenir le chemin complet
function getPath($path = '') {
    return rtrim(getConfig('ROOT_PATH'), '/') . '/' . ltrim($path, '/');
}

// Fonction pour logger les erreurs
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    error_log($logMessage . PHP_EOL, 3, getConfig('LOGS_PATH') . '/error.log');
}

// Configuration des messages d'erreur
define('ERROR_MESSAGES', [
    'db_connection' => 'Erreur de connexion à la base de données',
    'invalid_credentials' => 'Identifiants invalides',
    'user_exists' => 'Cet utilisateur existe déjà',
    'invalid_email' => 'Adresse email invalide',
    'password_mismatch' => 'Les mots de passe ne correspondent pas',
    'invalid_file' => 'Type de fichier non autorisé',
    'file_too_large' => 'Fichier trop volumineux',
    'event_full' => 'L\'événement est complet',
    'event_past' => 'L\'événement est déjà passé',
    'unauthorized' => 'Accès non autorisé'
]);

// Configuration des messages de succès
define('SUCCESS_MESSAGES', [
    'registration' => 'Inscription réussie !',
    'login' => 'Connexion réussie !',
    'event_created' => 'Événement créé avec succès !',
    'event_joined' => 'Vous avez rejoint l\'événement !',
    'event_left' => 'Vous avez quitté l\'événement',
    'profile_updated' => 'Profil mis à jour avec succès !'
]);

// Configuration des routes
define('ROUTES', [
    'home' => '/',
    'login' => '/login.php',
    'register' => '/register.php',
    'profile' => '/profile.php',
    'events' => '/events.php',
    'create_event' => '/create-event.php'
]);

// Fonction de connexion à la base de données
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}

// Fonction pour sécuriser les entrées
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
} 