<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<main class="about-section container">
    <h1>À propos de SportMatchy</h1>
    <section class="about-content">
        <div class="about-text">
            <h2>Notre mission</h2>
            <p>SportMatchy est une plateforme dédiée à la création, la gestion et la participation à des événements sportifs. Notre objectif est de rassembler les passionnés de sport, faciliter l'organisation de matchs et d'événements, et offrir un espace de discussion en temps réel.</p>
            <h2>Fonctionnalités principales</h2>
            <ul>
                <li><i class="fas fa-calendar-alt"></i> Création et gestion d'événements sportifs</li>
                <li><i class="fas fa-users"></i> Rejoindre des groupes et des matchs</li>
                <li><i class="fas fa-comments"></i> Chat en temps réel avec WebSocket</li>
                <li><i class="fas fa-map-marker-alt"></i> Localisation des événements sur carte</li>
                <li><i class="fas fa-chart-bar"></i> Statistiques et suivi des performances</li>
            </ul>
        </div>
        <div class="about-image">
            <img src="assets/images/about-sport.jpg" alt="SportMatchy" style="width:100%;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        </div>
    </section>
    <section class="team-section">
        <h2>Notre équipe</h2>
        <div class="team-grid">
            <div class="team-member">
                <img src="assets/images/team1.jpg" alt="Membre 1">
                <h3>Alexandre Dupont</h3>
                <p>Fondateur & Développeur</p>
            </div>
            <div class="team-member">
                <img src="assets/images/team2.jpg" alt="Membre 2">
                <h3>Marie Lefevre</h3>
                <p>UI/UX Designer</p>
            </div>
            <div class="team-member">
                <img src="assets/images/team3.jpg" alt="Membre 3">
                <h3>Yassine Benali</h3>
                <p>Responsable Communauté</p>
            </div>
        </div>
    </section>
</main>
<?php require_once 'includes/footer.php'; ?>
</body>
</html> 