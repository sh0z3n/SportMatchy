<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/database.php';

Session::start();
$db = Database::getInstance();

$upcomingEvents = $db->query(
    "SELECT e.*, s.name as sport_name, u.username as creator_name, 
     (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND status = 'confirmed') as participant_count
     FROM events e 
     JOIN sports s ON e.sport_id = s.id 
     JOIN users u ON e.creator_id = u.id 
     WHERE e.start_time > NOW() 
     ORDER BY e.start_time ASC 
     LIMIT 3"
)->fetchAll();

$popularSports = $db->query(
    "SELECT s.*, COUNT(DISTINCT e.id) as event_count 
     FROM sports s 
     LEFT JOIN events e ON s.id = e.sport_id 
     GROUP BY s.id 
     ORDER BY event_count DESC 
     LIMIT 4"
)->fetchAll();

$userStats = null;
if (Session::isLoggedIn()) {
    $userStats = $db->query(
        "SELECT 
            (SELECT COUNT(*) FROM events WHERE creator_id = ?) as created_events,
            (SELECT COUNT(*) FROM event_participants WHERE user_id = ? AND status = 'confirmed') as joined_events,
            (SELECT COUNT(*) FROM chat_groups WHERE created_by = ?) as created_groups",
        [Session::getUserId(), Session::getUserId(), Session::getUserId()]
    )->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Plateforme sportive interactive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    .hero {
        background: linear-gradient(120deg, var(--primary-color) 60%, var(--secondary-color) 100%);
        color: #fff;
        padding: 6rem 0 4rem;
        text-align: center;
        border-radius: 0 0 2rem 2rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        position: relative;
        overflow: hidden;
    }
    .hero .animated-icon {
        font-size: 5rem;
        color: #fff;
        margin-bottom: 1.5rem;
        animation: bounce 2s infinite;
    }
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 800;
    }
    .hero p {
        font-size: 1.3rem;
        margin-bottom: 2.5rem;
    }
    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    .cta-section {
        background: var(--bg-secondary);
        padding: 3rem 0;
        text-align: center;
        margin-top: 2rem;
        border-radius: 2rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .cta-section h2 {
        color: var(--primary-color);
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    .cta-section p {
        color: var(--text-secondary);
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }
    .cta-section .btn {
        font-size: 1.1rem;
        padding: 1rem 2.5rem;
    }
    .scroll-down {
        position: absolute;
        left: 50%;
        bottom: 2rem;
        transform: translateX(-50%);
        color: #fff;
        font-size: 2rem;
        opacity: 0.7;
        animation: scrollDown 1.5s infinite;
    }
    @keyframes scrollDown {
        0%, 100% { transform: translate(-50%, 0); }
        50% { transform: translate(-50%, 15px); }
    }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="animated-icon"><i class="fas fa-basketball-ball"></i></div>
                <h1>Bienvenue sur <?php echo APP_NAME; ?></h1>
                <p>La plateforme pour organiser, rejoindre et discuter autour d'événements sportifs.<br>Créez des groupes, discutez en temps réel, partagez votre passion !</p>
                <div class="hero-buttons">
                    <a href="events.php" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i>
                        Voir les événements
                    </a>
                    <?php if (Session::isLoggedIn()): ?>
                        <a href="create-event.php" class="btn btn-secondary">
                            <i class="fas fa-plus"></i>
                            Créer un événement
                        </a>
                        <a href="profile.php" class="btn btn-outline">
                            <i class="fas fa-user"></i>
                            Mon profil
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i>
                            S'inscrire
                        </a>
                        <a href="login.php" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i>
                            Se connecter
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="scroll-down"><i class="fas fa-chevron-down"></i></div>
    </section>

    <?php if (Session::isLoggedIn() && $userStats): ?>
    <section class="user-stats-section">
        <div class="container">
            <h2>Mes statistiques</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-calendar-plus"></i>
                    <h3>Événements créés</h3>
                    <div class="stat-number"><?php echo $userStats['created_events']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>Événements rejoints</h3>
                    <div class="stat-number"><?php echo $userStats['joined_events']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-comments"></i>
                    <h3>Groupes créés</h3>
                    <div class="stat-number"><?php echo $userStats['created_groups']; ?></div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="upcoming-events-section">
        <div class="container">
            <h2>Prochains événements</h2>
            <div class="events-grid">
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="event-card">
                        <div class="event-header">
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <span class="sport-badge"><?php echo htmlspecialchars($event['sport_name']); ?></span>
                        </div>
                        <div class="event-details">
                            <p><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($event['start_time'])); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                            <p><i class="fas fa-users"></i> <?php echo $event['participant_count']; ?> participants</p>
                        </div>
                        <div class="event-actions">
                            <a href="event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">Voir détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="events.php" class="btn btn-secondary">Voir tous les événements</a>
            </div>
        </div>
    </section>
<!-- // quoi je dois pour le popular sports ? -->
    <section class="popular-sports-section">
        <div class="container">
            <h2>Sports populaires</h2>
            <div class="sports-grid">
                <?php foreach ($popularSports as $sport): ?>
                    <div class="sport-card">
                        <i class="fas fa-<?php echo strtolower($sport['name']); ?>"></i>
                        <h3><?php echo htmlspecialchars($sport['name']); ?></h3>
                        <p><?php echo htmlspecialchars($sport['description']); ?></p>
                        <div class="sport-stats">
                            <span><?php echo $sport['event_count']; ?> événements</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Rejoignez la communauté SportMatchy</h2>
            <p>Participez à des événements sportifs, discutez en temps réel, et faites de nouvelles rencontres autour de votre passion !</p>
            <?php if (!Session::isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary">Créer un compte</a>
            <?php else: ?>
                <a href="create-event.php" class="btn btn-primary">Créer un événement</a>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/chatbot.js"></script>
</body>
</html> 