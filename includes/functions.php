<?php
require_once 'config.php';

/**
 * Fonctions utilitaires pour l'application SportMatchy
 */

// Formatage des dates
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function formatDateRelative($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'à l\'instant';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "il y a {$minutes} minute" . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "il y a {$hours} heure" . ($hours > 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "il y a {$days} jour" . ($days > 1 ? 's' : '');
    } else {
        return formatDate($date);
    }
}

// Formatage des nombres
function formatNumber($number) {
    return number_format($number, 0, ',', ' ');
}

function formatDistance($meters) {
    if ($meters < 1000) {
        return $meters . ' m';
    }
    return number_format($meters / 1000, 1, ',', ' ') . ' km';
}

// Validation des données
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function validatePassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

// Sécurité
function sanitizeString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Manipulation de texte
function truncateText($text, $maxLength = 100) {
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

// Gestion des fichiers
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isAllowedImage($filename) {
    $extension = getFileExtension($filename);
    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
}

function generateUniqueFilename($filename) {
    $extension = getFileExtension($filename);
    return uniqid() . '.' . $extension;
}

// URLs et chemins
function getBaseUrl() {
    return rtrim(APP_URL, '/');
}

function getAssetUrl($path) {
    return getBaseUrl() . '/assets/' . ltrim($path, '/');
}

function getUploadUrl($path) {
    return getBaseUrl() . '/uploads/' . ltrim($path, '/');
}

// Messages d'erreur
function getErrorMessage($code) {
    $messages = [
        'invalid_email' => 'L\'adresse email n\'est pas valide.',
        'invalid_password' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.',
        'invalid_phone' => 'Le numéro de téléphone n\'est pas valide.',
        'required_field' => 'Ce champ est obligatoire.',
        'file_too_large' => 'Le fichier est trop volumineux.',
        'invalid_file_type' => 'Le type de fichier n\'est pas autorisé.',
        'upload_failed' => 'L\'upload du fichier a échoué.',
        'event_full' => 'L\'événement est complet.',
        'event_past' => 'L\'événement est déjà passé.',
        'already_registered' => 'Vous êtes déjà inscrit à cet événement.',
        'not_registered' => 'Vous n\'êtes pas inscrit à cet événement.',
        'invalid_token' => 'Le token n\'est pas valide.',
        'session_expired' => 'Votre session a expiré.',
        'permission_denied' => 'Vous n\'avez pas les permissions nécessaires.',
        'not_found' => 'La ressource demandée n\'existe pas.',
        'server_error' => 'Une erreur est survenue sur le serveur.'
    ];
    return $messages[$code] ?? 'Une erreur est survenue.';
}

// Validation des événements
function isEventFull($event) {
    return $event['participants_count'] >= $event['max_participants'];
}

function isEventPast($event) {
    return strtotime($event['start_time']) < time();
}

function canJoinEvent($event, $userId) {
    if (isEventFull($event)) {
        return false;
    }
    if (isEventPast($event)) {
        return false;
    }
    if ($event['creator_id'] === $userId) {
        return false;
    }
    return true;
}

// Formatage des statuts
function formatEventStatus($status) {
    $statuses = [
        'active' => 'Actif',
        'cancelled' => 'Annulé',
        'completed' => 'Terminé',
        'pending' => 'En attente'
    ];
    return $statuses[$status] ?? $status;
}

function formatParticipantStatus($status) {
    $statuses = [
        'joined' => 'Inscrit',
        'pending' => 'En attente',
        'declined' => 'Refusé',
        'left' => 'Parti'
    ];
    return $statuses[$status] ?? $status;
}

// Calculs
function calculateAge($birthdate) {
    return date_diff(date_create($birthdate), date_create('today'))->y;
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Rayon de la Terre en mètres
    
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    $latDelta = $lat2 - $lat1;
    $lonDelta = $lon2 - $lon1;
    
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($lat1) * cos($lat2) * pow(sin($lonDelta / 2), 2)));
    
    return $angle * $earthRadius;
}

// Pagination
function getPagination($currentPage, $totalPages, $range = 2) {
    $pages = [];
    
    // Pages précédentes
    for ($i = max(1, $currentPage - $range); $i < $currentPage; $i++) {
        $pages[] = $i;
    }
    
    // Page courante
    $pages[] = $currentPage;
    
    // Pages suivantes
    for ($i = $currentPage + 1; $i <= min($totalPages, $currentPage + $range); $i++) {
        $pages[] = $i;
    }
    
    return [
        'pages' => $pages,
        'current' => $currentPage,
        'total' => $totalPages,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Debug
function dd($data) {
    if (isDev()) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

function logDebug($message, $context = []) {
    if (isDev()) {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($context)) {
            $logMessage .= ' - Context: ' . json_encode($context);
        }
        error_log($logMessage . PHP_EOL, 3, LOGS_PATH . '/debug.log');
    }
}

// Fonction pour nettoyer les entrées utilisateur
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour vérifier si un fichier est une image valide
function isValidImage($file) {
    $allowedTypes = ALLOWED_IMAGE_TYPES;
    $maxSize = MAX_FILE_SIZE;

    if (!isset($file['type']) || !in_array($file['type'], $allowedTypes)) {
        return false;
    }

    if ($file['size'] > $maxSize) {
        return false;
    }

    return true;
}

// Fonction pour uploader une image
function uploadImage($file, $directory = UPLOAD_DIR) {
    if (!isValidImage($file)) {
        throw new Exception(ERROR_MESSAGES['invalid_file']);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Erreur lors de l'upload du fichier");
    }

    return $filename;
}

// Fonction pour rediriger
function redirect($url) {
    header("Location: $url");
    exit;
}

// Fonction pour afficher un message d'erreur
function displayError($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

// Fonction pour afficher un message de succès
function displaySuccess($message) {
    return "<div class='alert alert-success'>$message</div>";
}

// Fonction pour vérifier si une requête est AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Fonction pour vérifier si un utilisateur a les droits d'accès
function hasPermission($requiredRole) {
    $user = Session::getCurrentUser();
    return $user && $user['role'] === $requiredRole;
}

// Fonction pour vérifier si une date est dans le futur
function isFutureDate($date) {
    return strtotime($date) > time();
}

// Fonction pour calculer la durée entre deux dates
function calculateDuration($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    return $interval->format('%H heures %i minutes');
} 