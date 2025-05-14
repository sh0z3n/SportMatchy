<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

Session::start();

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$message = strtolower($data['message'] ?? '');

if (empty($message) || strpos($message, 'bonjour') !== false || strpos($message, 'salut') !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Bonjour! Je suis l\'assistant SportMatchy meilleur bot en nice. Comment puis-je vous aider aujourd\'hui?'
    ]);
    exit;
}

$responses = [
    'events' => [
        'Pour créer un événement, cliquez sur le bouton "Créer un événement" dans le menu.',
        'Vous pouvez voir tous les événements disponibles sur la page Événements.',
        'N\'oubliez pas de vérifier les détails de l\'événement avant de vous inscrire.',
        'Les événements sont classés par sport et par date.'
    ],
    'sports' => [
        'Nous proposons une large gamme de sports, du football au tennis en passant par la natation.',
        'Vous pouvez voir tous les sports disponibles sur la page Sports.',
        'Chaque sport a ses propres événements et règles.',
        'N\'hésitez pas à essayer de nouveaux sports!'
    ],
    'profile' => [
        'Vous pouvez modifier votre profil dans les paramètres de votre compte.',
        'N\'oubliez pas de mettre à jour votre photo de profil.',
        'Votre profil montre votre historique d\'événements et vos sports préférés.',
        'Vous pouvez personnaliser vos préférences de notification dans votre profil.'
    ],
    'help' => [
        'Je suis là pour vous aider avec toutes vos questions sur SportMatchy.',
        'Vous pouvez me poser des questions sur les événements, les sports ou votre profil.',
        'Si vous avez besoin d\'aide supplémentaire, contactez notre support.',
        'N\'hésitez pas à explorer le site pour découvrir toutes les fonctionnalités.'
    ],
    'default' => [
        'Je ne suis pas sûr de comprendre. Pouvez-vous reformuler votre question?',
        'Je suis désolé, je n\'ai pas la réponse à cette question.',
        'Pourriez-vous préciser votre question?',
        'Je peux vous aider avec les événements, les sports et votre profil.'
    ]
];

$category = 'default';
if (strpos($message, 'événement') !== false || strpos($message, 'event') !== false) {
    $category = 'events';
} elseif (strpos($message, 'sport') !== false) {
    $category = 'sports';
} elseif (strpos($message, 'profil') !== false || strpos($message, 'compte') !== false) {
    $category = 'profile';
} elseif (strpos($message, 'aide') !== false || strpos($message, 'help') !== false) {
    $category = 'help';
}

$response = $responses[$category][array_rand($responses[$category])];

echo json_encode([
    'success' => true,
    'message' => $response
]); 