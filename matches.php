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
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="live-matches-section">
            <h1>Matchs en Direct</h1>

            <div class="filters">
                <div class="sport-filter">
                    <label for="sport">Sport :</label>
                    <select id="sport" name="sport">
                        <option value="all">Tous les sports</option>
                        <?php foreach ($favoriteSports as $sport): ?>
                            <option value="<?php echo $sport['id']; ?>">
                                <?php echo htmlspecialchars($sport['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="league-filter">
                    <label for="league">Ligue :</label>
                    <select id="league" name="league">
                        <option value="all">Toutes les ligues</option>
                        <option value="ligue1">Ligue 1</option>
                        <option value="nba">NBA</option>
                        <option value="nfl">NFL</option>
                    </select>
                </div>
            </div>

            <div class="matches-grid">
                <?php foreach ($liveMatches as $match): ?>
                    <div class="match-card">
                        <div class="match-header">
                            <span class="match-sport"><?php echo htmlspecialchars($match['sport']); ?></span>
                            <span class="match-league"><?php echo htmlspecialchars($match['league']); ?></span>
                            <span class="match-status live">EN DIRECT</span>
                        </div>

                        <div class="match-content">
                            <div class="team home">
                                <img src="assets/images/teams/<?php echo strtolower($match['home_team']); ?>.png" 
                                     alt="<?php echo htmlspecialchars($match['home_team']); ?>"
                                     onerror="this.src='assets/images/default-team.png'">
                                <span class="team-name"><?php echo htmlspecialchars($match['home_team']); ?></span>
                            </div>

                            <div class="match-info">
                                <div class="score"><?php echo $match['score']; ?></div>
                                <?php if (isset($match['minute'])): ?>
                                    <div class="time"><?php echo $match['minute']; ?>'</div>
                                <?php else: ?>
                                    <div class="time"><?php echo $match['quarter']; ?> - <?php echo $match['time']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="team away">
                                <img src="assets/images/teams/<?php echo strtolower($match['away_team']); ?>.png" 
                                     alt="<?php echo htmlspecialchars($match['away_team']); ?>"
                                     onerror="this.src='assets/images/default-team.png'">
                                <span class="team-name"><?php echo htmlspecialchars($match['away_team']); ?></span>
                            </div>
                        </div>

                        <div class="match-events">
                            <h3>Événements récents</h3>
                            <div class="events-list">
                                <?php foreach ($match['events'] as $event): ?>
                                    <div class="event-item">
                                        <span class="event-time"><?php echo $event['minute']; ?></span>
                                        <span class="event-type <?php echo $event['type']; ?>">
                                            <?php
                                            switch ($event['type']) {
                                                case 'goal':
                                                    echo '<i class="fas fa-futbol"></i>';
                                                    break;
                                                case 'three_pointer':
                                                    echo '<i class="fas fa-basketball-ball"></i>';
                                                    break;
                                                case 'dunk':
                                                    echo '<i class="fas fa-basketball-ball"></i>';
                                                    break;
                                            }
                                            ?>
                                        </span>
                                        <span class="event-player"><?php echo $event['player']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        // Auto-refresh matches every 30 seconds
        setInterval(() => {
            fetch('api/live-matches.php')
                .then(response => response.json())
                .then(data => {
                    // Update matches data
                    updateMatches(data);
                })
                .catch(error => console.error('Error:', error));
        }, 30000);

        // Filter matches
        document.getElementById('sport').addEventListener('change', filterMatches);
        document.getElementById('league').addEventListener('change', filterMatches);

        function filterMatches() {
            const sport = document.getElementById('sport').value;
            const league = document.getElementById('league').value;
            
            // Add your filtering logic here
            console.log('Filtering matches:', { sport, league });
        }

        function updateMatches(data) {
            // Add your update logic here
            console.log('Updating matches:', data);
        }
    </script>
</body>
</html> 