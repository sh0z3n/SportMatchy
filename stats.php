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
$userId = Session::getUserId();

// Get user statistics
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM events WHERE creator_id = ?) as events_created,
        (SELECT COUNT(*) FROM event_participants WHERE user_id = ? AND status = 'joined') as events_joined,
        (SELECT COUNT(*) FROM event_comments WHERE user_id = ?) as comments_made,
        (SELECT COUNT(DISTINCT sport_id) FROM event_participants ep 
         JOIN events e ON ep.event_id = e.id 
         WHERE ep.user_id = ? AND ep.status = 'joined') as sports_practiced
", [$userId, $userId, $userId, $userId])->fetch();

// Get monthly activity
$monthlyActivity = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM events
    WHERE creator_id = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
", [$userId])->fetchAll();

// Get popular sports
$popularSports = $db->query("
    SELECT 
        s.name,
        COUNT(*) as count
    FROM event_participants ep
    JOIN events e ON ep.event_id = e.id
    JOIN sports s ON e.sport_id = s.id
    WHERE ep.user_id = ? AND ep.status = 'joined'
    GROUP BY s.id
    ORDER BY count DESC
    LIMIT 5
", [$userId])->fetchAll();

// Generate new CSRF token
$csrfToken = Session::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/stats.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <?php if (Session::isLoggedIn()): ?>
            <div class="stats-header" style="text-align: right; margin-bottom: 1.5rem;">
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> Retour au profil
                </a>
            </div>
        <?php else: ?>
            <div class="stats-header" style="text-align: right; margin-bottom: 1.5rem;">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
            </div>
        <?php endif; ?>
        <div class="stats-section">
            <h1>Mes Statistiques</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-calendar-plus"></i>
                    <div class="stat-content">
                        <h3>Événements créés</h3>
                        <p class="stat-value"><?php echo $stats['events_created']; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-content">
                        <h3>Événements rejoints</h3>
                        <p class="stat-value"><?php echo $stats['events_joined']; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <i class="fas fa-comments"></i>
                    <div class="stat-content">
                        <h3>Commentaires</h3>
                        <p class="stat-value"><?php echo $stats['comments_made']; ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <i class="fas fa-running"></i>
                    <div class="stat-content">
                        <h3>Sports pratiqués</h3>
                        <p class="stat-value"><?php echo $stats['sports_practiced']; ?></p>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <h2>Activité mensuelle</h2>
                    <canvas id="activityChart"></canvas>
                </div>

                <div class="chart-card">
                    <h2>Sports populaires</h2>
                    <canvas id="sportsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        // Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($monthlyActivity), 'month')); ?>,
                datasets: [{
                    label: 'Événements créés',
                    data: <?php echo json_encode(array_column(array_reverse($monthlyActivity), 'count')); ?>,
                    borderColor: '#3498db',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Sports Chart
        const sportsCtx = document.getElementById('sportsChart').getContext('2d');
        new Chart(sportsCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($popularSports, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($popularSports, 'count')); ?>,
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#e74c3c',
                        '#f1c40f',
                        '#9b59b6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
</body>
</html> 