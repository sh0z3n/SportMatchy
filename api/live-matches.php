<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

Session::start();

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sport = isset($_GET['sport']) ? $_GET['sport'] : 'all';
$league = isset($_GET['league']) ? $_GET['league'] : 'all';

$liveMatches = [
    [
        'id' => 1,
        'sport' => 'Football',
        'league' => 'Ligue 1',
        'home_team' => 'PSG',
        'away_team' => 'Marseille',
        'score' => '2-1',
        'minute' => '65',
        'status' => 'live',
        'events' => [
            ['minute' => '15', 'type' => 'goal', 'team' => 'home', 'player' => 'Mbappé'],
            ['minute' => '32', 'type' => 'goal', 'team' => 'away', 'player' => 'Payet'],
            ['minute' => '45', 'type' => 'goal', 'team' => 'home', 'player' => 'Messi']
        ]
    ],
    [
        'id' => 2,
        'sport' => 'Basketball',
        'league' => 'NBA',
        'home_team' => 'Lakers',
        'away_team' => 'Warriors',
        'score' => '98-95',
        'quarter' => '4',
        'time' => '2:15',
        'status' => 'live',
        'events' => [
            ['minute' => 'Q3', 'type' => 'three_pointer', 'team' => 'home', 'player' => 'James'],
            ['minute' => 'Q4', 'type' => 'dunk', 'team' => 'away', 'player' => 'Curry']
        ]
    ]
];

if ($sport !== 'all') {
    $liveMatches = array_filter($liveMatches, function($match) use ($sport) {
        return strtolower($match['sport']) === strtolower($sport);
    });
}

if ($league !== 'all') {
    $liveMatches = array_filter($liveMatches, function($match) use ($league) {
        return strtolower($match['league']) === strtolower($league);
    });
}

foreach ($liveMatches as &$match) {
    if ($match['sport'] === 'Football') {
        $match['minute'] = (int)$match['minute'] + rand(1, 3);
        if ($match['minute'] > 90) {
            $match['minute'] = '90+';
        }
    } else {
        $match['time'] = max(0, (int)str_replace(':', '', $match['time']) - rand(1, 15));
        if ($match['time'] < 0) {
            $match['quarter'] = (int)$match['quarter'] - 1;
            $match['time'] = '2:00';
        }
    }
}

echo json_encode([
    'success' => true,
    'matches' => array_values($liveMatches)
]); 