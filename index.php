<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
Session::start();
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
                        <a href="profile.php" class="btn btn-secondary">
                            <i class="fas fa-user"></i>
                            Mon profil
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i>
                            S'inscrire
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="scroll-down"><i class="fas fa-chevron-down"></i></div>
    </section>
    <section class="cta-section">
        <div class="container">
            <h2>Rejoignez la communauté SportMatchy</h2>
            <p>Participez à des événements sportifs, discutez en temps réel, et faites de nouvelles rencontres autour de votre passion !</p>
            <a href="register.php" class="btn btn-primary">Créer un compte</a>
        </div>
    </section>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 