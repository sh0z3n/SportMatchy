<?php
require_once 'config.php';

// Configuration de la session avant tout output
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    session_name(SESSION_NAME);
}

class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
            
            // Régénération de l'ID de session périodiquement
            if (!isset($_SESSION['last_regeneration'])) {
                self::regenerate();
            } else {
                $interval = 300; // 5 minutes
                if (time() - $_SESSION['last_regeneration'] > $interval) {
                    self::regenerate();
                }
            }
        }
    }

    public static function regenerate() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function clear() {
        session_unset();
        session_destroy();
    }

    public static function flash($key, $value = null) {
        if ($value === null) {
            $value = self::get("flash_{$key}");
            self::remove("flash_{$key}");
            return $value;
        }
        self::set("flash_{$key}", $value);
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getUsername() {
        return $_SESSION['username'] ?? null;
    }

    public static function getEmail() {
        return $_SESSION['email'] ?? null;
    }

    public static function getUser() {
        return $_SESSION['user'] ?? null;
    }

    public static function login($userId, $username, $email, $remember = false) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days

            setcookie(
                'remember_token',
                $token,
                [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );

            $db = Database::getInstance();
            $db->query(
                "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))",
                [$userId, $token, $expires]
            );
        }
    }

    public static function logout() {
        if (isset($_COOKIE['remember_token'])) {
            $db = Database::getInstance();
            $db->query(
                "DELETE FROM remember_tokens WHERE token = ?",
                [$_COOKIE['remember_token']]
            );

            setcookie(
                'remember_token',
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }

        self::clear();
        self::start();
    }

    public static function checkActivity() {
        $timeout = 1800; // 30 minutes
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
            return false;
        }
        self::set('last_activity', time());
        return true;
    }

    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function checkRememberToken() {
        if (!self::isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $db = Database::getInstance();
            $token = $db->query(
                "SELECT rt.*, u.username, u.email 
                 FROM remember_tokens rt 
                 JOIN users u ON rt.user_id = u.id 
                 WHERE rt.token = ? AND rt.expires_at > NOW()",
                [$_COOKIE['remember_token']]
            )->fetch();

            if ($token) {
                self::login($token['user_id'], $token['username'], $token['email']);
            }
        }
    }

    public static function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function setLanguage($lang) {
        self::set('language', $lang);
    }

    public static function getLanguage() {
        return self::get('language', 'fr');
    }

    public static function setTheme($theme) {
        self::set('theme', $theme);
    }

    public static function getTheme() {
        return self::get('theme', 'light');
    }

    public static function setNotification($message, $type = 'info') {
        $notifications = self::get('notifications', []);
        $notifications[] = [
            'message' => $message,
            'type' => $type,
            'time' => time()
        ];
        self::set('notifications', $notifications);
    }

    public static function getNotifications() {
        $notifications = self::get('notifications', []);
        self::remove('notifications');
        return $notifications;
    }

    public static function setRedirect($url) {
        self::set('redirect_after_login', $url);
    }

    public static function getRedirect() {
        $url = self::get('redirect_after_login');
        self::remove('redirect_after_login');
        return $url;
    }

    public static function setFormData($data) {
        self::set('form_data', $data);
    }

    public static function getFormData() {
        $data = self::get('form_data');
        self::remove('form_data');
        return $data;
    }

    public static function setError($field, $message) {
        $errors = self::get('form_errors', []);
        $errors[$field] = $message;
        self::set('form_errors', $errors);
    }

    public static function getErrors() {
        $errors = self::get('form_errors', []);
        self::remove('form_errors');
        return $errors;
    }

    public static function hasErrors() {
        return !empty(self::get('form_errors', []));
    }

    public static function setSuccess($message) {
        self::set('success_message', $message);
    }

    public static function getSuccess() {
        $message = self::get('success_message');
        self::remove('success_message');
        return $message;
    }

    public static function setWarning($message) {
        self::set('warning_message', $message);
    }

    public static function getWarning() {
        $message = self::get('warning_message');
        self::remove('warning_message');
        return $message;
    }

    public static function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }

    public static function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public static function setCurrentUser($user) {
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            self::setFlash('error', 'Vous devez être connecté pour accéder à cette page');
            header('Location: ' . ROUTES['login']);
            exit;
        }
    }

    public static function requireGuest() {
        if (self::isLoggedIn()) {
            header('Location: ' . ROUTES['home']);
            exit;
        }
    }
} 