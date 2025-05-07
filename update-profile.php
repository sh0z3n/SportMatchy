<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';
require_once 'includes/auth.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$userId = Session::getUserId();
$error = '';
$success = '';

try {
    // Validate CSRF token
    if (!Session::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Session expirée. Veuillez réessayer.');
    }

    // Get current user data
    $user = $db->query("SELECT * FROM users WHERE id = ?", [$userId])->fetch();

    // Validate input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email)) {
        throw new Exception('Le nom d\'utilisateur et l\'email sont requis.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide.');
    }

    // Check if username is taken
    if ($username !== $user['username']) {
        $exists = $db->query("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $userId])->fetch();
        if ($exists) {
            throw new Exception('Ce nom d\'utilisateur est déjà pris.');
        }
    }

    // Check if email is taken
    if ($email !== $user['email']) {
        $exists = $db->query("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId])->fetch();
        if ($exists) {
            throw new Exception('Cette adresse email est déjà utilisée.');
        }
    }

    // Handle password change
    if (!empty($currentPassword)) {
        if (empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Veuillez remplir tous les champs du mot de passe.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Les mots de passe ne correspondent pas.');
        }

        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception('Mot de passe actuel incorrect.');
        }

        $password = password_hash($newPassword, PASSWORD_DEFAULT);
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

        if (!is_dir('uploads/avatars')) {
            mkdir('uploads/avatars', 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Erreur lors du téléchargement de l\'image.');
        }

        // Delete old avatar if exists
        if ($user['avatar'] && file_exists($user['avatar'])) {
            unlink($user['avatar']);
        }

        $avatar = $uploadPath;
    }

    // Update user
    $updateData = [
        'username' => $username,
        'email' => $email,
        'bio' => $bio,
        'avatar' => $avatar
    ];

    if (isset($password)) {
        $updateData['password'] = $password;
    }

    $db->query(
        "UPDATE users SET " . implode(', ', array_map(fn($key) => "$key = ?", array_keys($updateData))) . " WHERE id = ?",
        [...array_values($updateData), $userId]
    );

    $success = 'Profil mis à jour avec succès.';
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Redirect back to profile page with status
$redirectUrl = 'profile.php';
if ($error) {
    $redirectUrl .= '?error=' . urlencode($error);
} elseif ($success) {
    $redirectUrl .= '?success=' . urlencode($success);
}

header('Location: ' . $redirectUrl);
exit; 