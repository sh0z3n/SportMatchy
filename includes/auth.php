<?php
require_once 'config.php';
require_once 'database.php';
require_once 'session.php';

class Auth {
    private $db;
    private $maxAttempts = MAX_LOGIN_ATTEMPTS;
    private $lockoutTime = LOGIN_TIMEOUT;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register($username, $email, $password) {
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("Tous les champs sont requis.");
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new Exception("Le nom d'utilisateur doit contenir entre 3 et 50 caractères.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("L'email n'est pas valide.");
        }

        if (strlen($password) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères.");
        }

        $existingUser = $this->db->query(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        )->fetchAll();

        if (!empty($existingUser)) {
            if ($existingUser[0]['username'] === $username) {
                throw new Exception("Ce nom d'utilisateur est déjà pris.");
            } else {
                throw new Exception("Cette adresse email est déjà utilisée.");
            }
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $userId;
    }

    public function login($email, $password, $remember = false) {
        if (empty($email) || empty($password)) {
            throw new Exception("Email et mot de passe requis.");
        }

        $user = $this->db->query(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );

        if (empty($user)) {
            throw new Exception("Email ou mot de passe incorrect.");
        }

        $user = $user[0];

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Email ou mot de passe incorrect.");
        }

        Session::login($user['id'], $user['username'], $user['email']);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            $this->db->insert('remember_tokens', [
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => $expires
            ]);

            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
        }

        return true;
    }

    public function logout() {
        if (Session::isLoggedIn()) {
            $userId = Session::getUserId();
            $this->db->delete('remember_tokens', 'user_id = ?', [$userId]);
            Session::clearRememberToken();
        }
        Session::logout();
    }

    public function resetPassword($email) {
        if (empty($email)) {
            throw new Exception("Email requis.");
        }

        $user = $this->db->query(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );

        if (empty($user)) {
            throw new Exception("Aucun compte associé à cet email.");
        }

        $user = $user[0];
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->db->insert('password_resets', [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires
        ]);

        $resetLink = APP_URL . "/reset-password.php?token=" . $token;
        $to = $user['email'];
        $subject = "Réinitialisation de votre mot de passe - " . APP_NAME;
        $message = "Bonjour " . $user['username'] . ",\n\n";
        $message .= "Vous avez demandé la réinitialisation de votre mot de passe. ";
        $message .= "Cliquez sur le lien suivant pour définir un nouveau mot de passe :\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "Ce lien expirera dans 1 heure.\n\n";
        $message .= "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\n";
        $message .= "Cordialement,\n" . APP_NAME;

        $headers = "From: " . APP_EMAIL . "\r\n";
        $headers .= "Reply-To: " . APP_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        if (!mail($to, $subject, $message, $headers)) {
            throw new Exception("Erreur lors de l'envoi de l'email de réinitialisation.");
        }

        return true;
    }

    public function updatePassword($token, $newPassword) {
        if (empty($token) || empty($newPassword)) {
            throw new Exception("Token et nouveau mot de passe requis.");
        }

        if (strlen($newPassword) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères.");
        }

        $reset = $this->db->query(
            "SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()",
            [$token]
        );

        if (empty($reset)) {
            throw new Exception("Token invalide ou expiré.");
        }

        $reset = $reset[0];
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->db->update('users', 
            ['password' => $passwordHash],
            'id = ?',
            [$reset['user_id']]
        );

        $this->db->update('password_resets',
            ['used' => 1],
            'id = ?',
            [$reset['id']]
        );

        return true;
    }

    public function validateResetToken($token) {
        if (empty($token)) {
            return false;
        }

        $reset = $this->db->query(
            "SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()",
            [$token]
        );

        return !empty($reset);
    }

    private function isBlocked($email) {
        $attempts = $this->db->count('login_attempts',
            'email = ? AND ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$email, $_SERVER['REMOTE_ADDR'], $this->lockoutTime]
        );

        return $attempts >= $this->maxAttempts;
    }

    private function logAttempt($email) {
        $this->db->insert('login_attempts', [
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    }

    private function clearAttempts($email) {
        $this->db->delete('login_attempts',
            'email = ? AND ip_address = ?',
            [$email, $_SERVER['REMOTE_ADDR']]
        );
    }

    private function sendPasswordResetEmail($email, $resetLink) {
        $to = $email;
        $subject = 'Réinitialisation de votre mot de passe - ' . APP_NAME;
        $message = "Bonjour,\n\n";
        $message .= "Vous avez demandé la réinitialisation de votre mot de passe.\n";
        $message .= "Cliquez sur le lien suivant pour définir un nouveau mot de passe :\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "Ce lien expirera dans " . (TOKEN_LIFETIME / 3600) . " heures.\n\n";
        $message .= "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\n";
        $message .= "Cordialement,\n" . APP_NAME;

        $headers = [
            'From' => SMTP_FROM_NAME . ' <' . SMTP_FROM . '>',
            'Reply-To' => SMTP_FROM,
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        mail($to, $subject, $message, $headers);
    }

    public function checkRememberToken() {
        $token = Session::getRememberToken();
        if ($token) {
            $remember = $this->db->query(
                "SELECT u.* FROM users u 
                JOIN remember_tokens rt ON u.id = rt.user_id 
                WHERE rt.token = ? AND rt.expires_at > NOW()",
                [$token]
            )->fetch();

            if ($remember) {
                Session::login($remember);
                return true;
            }
        }
        return false;
    }
} 