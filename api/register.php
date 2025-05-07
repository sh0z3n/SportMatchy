<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Validate CSRF token
    if (!Session::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Session expirée. Veuillez réessayer.');
    }

    // Get and validate input
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        throw new Exception('Tous les champs sont requis');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide');
    }

    if (strlen($password) < 8) {
        throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
    }

    if ($password !== $confirmPassword) {
        throw new Exception('Les mots de passe ne correspondent pas');
    }

    // Register user
    $auth = new Auth();
    $auth->register($username, $email, $password);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie'
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 