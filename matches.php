<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';

// Start session
Session::start();

// Redirect if not logged in
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$error = '';
$success = '';

// Get user's favorite sports
$favoriteSports = $db->query("
    SELECT s.* 
    FROM sports s
    JOIN user_sport_preferences usp ON s.id = usp.sport_id
    WHERE usp.user_id = ?
", [Session::getUserId()])->fetchAll();

// Get live matches from API (simulated data for now)
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

// Generate new CSRF token
$csrfToken = Session::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matchs en Direct - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/live-matches.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    .live-matches-section {
      margin: 2rem 0;
    }
    #matches-list {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      justify-content: flex-start;
    }
    .match-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 2px 12px rgba(67,176,71,0.08);
      border: 1px solid #e0e0e0;
      padding: 1.5rem;
      width: 320px;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: box-shadow 0.2s, transform 0.2s;
    }
    .match-card:hover {
      box-shadow: 0 4px 24px rgba(67,176,71,0.18);
      transform: translateY(-4px) scale(1.03);
    }
    .teams {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .team-logo {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #43b047;
      background: #f6f6f6;
    }
    .team-names {
      font-weight: bold;
      font-size: 1.1rem;
      color: #222;
    }
    .match-info {
      margin-bottom: 1rem;
      color: #555;
      font-size: 0.98rem;
      text-align: center;
    }
    .btn.btn-primary {
      background: #43b047;
      color: #fff;
      border: none;
      border-radius: 0.5rem;
      padding: 0.5rem 1.2rem;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.2s;
      text-decoration: none;
      display: inline-block;
    }
    .btn.btn-primary:hover {
      background: #36913a;
    }
    @media (max-width: 700px) {
      #matches-list { flex-direction: column; gap: 1rem; }
      .match-card { width: 100%; }
    }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="live-matches-section">
            <h1>Matchs en Direct</h1>
            <div id="matches-list">
                <p>Chargement des matchs en direct...</p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
    async function fetchMatches() {
        const res = await fetch('https://www.scorebat.com/video-api/v3/'); // Free football API
        const data = await res.json();
        const matches = data.response || [];
        const container = document.getElementById('matches-list');
        if (!matches.length) {
            container.innerHTML = '<p>Aucun match en direct trouvé.</p>';
            return;
        }
        container.innerHTML = matches.slice(0, 10).map(match => `
            <div class="match-card">
                <div class="teams">
                    <img src="${match.thumbnail || 'https://source.unsplash.com/80x80/?football,team'}" alt="${match.title}" class="team-logo">
                    <div class="team-names">${match.title}</div>
                </div>
                <div class="match-info">
                    <span><b>Compétition:</b> ${match.competition}</span><br>
                    <span><b>Date:</b> ${new Date(match.date).toLocaleString('fr-FR')}</span>
                </div>
                <a href="${match.url}" target="_blank" class="btn btn-primary">Voir le résumé</a>
            </div>
        `).join('');
    }
    fetchMatches();
    setInterval(fetchMatches, 30000);
    </script>
</body>
</html> 