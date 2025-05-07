<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Start session
Session::start();

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Session::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    Session::logout();
}

// Redirect to home page
header('Location: index.php');
exit; 