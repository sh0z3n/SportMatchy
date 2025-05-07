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

    // Get and validate email
    $email = $_GET['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Adresse email requise');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide');
    }

    // Check availability
    $db = Database::getInstance();
    $exists = $db->exists('users', 'email = ?', [$email]);

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