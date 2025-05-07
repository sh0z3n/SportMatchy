<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Méthode non autorisée');
    }

    // Get and validate username
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        throw new Exception('Nom d\'utilisateur requis');
    }

    if (strlen($username) < 3 || strlen($username) > 20) {
        throw new Exception('Le nom d\'utilisateur doit contenir entre 3 et 20 caractères');
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        throw new Exception('Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores');
    }

    // Check availability
    $db = Database::getInstance();
    $exists = $db->exists('users', 'username = ?', [$username]);

    // Return response
    echo json_encode([
        'success' => true,
        'available' => !$exists
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 