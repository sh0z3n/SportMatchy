<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="header">
    <nav class="nav container">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-running"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <ul class="nav-menu">
            <li>
                <a href="index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                    Accueil
                </a>
            </li>
            <li>
                <a href="events.php" class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">
                    Événements
                </a>
            </li>
            <li>
                <a href="sports.php" class="nav-link <?php echo $currentPage === 'sports.php' ? 'active' : ''; ?>">
                    Sports
                </a>
            </li>
            <li>
                <a href="matches.php" class="nav-link ">Matchs</a>
            </li>
            <li>
                <a href="about.php" class="nav-link ">À propos</a>
            </li>
            <?php if (Session::isLoggedIn()): ?>
                <li>
                    <form method="POST" action="logout.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::generateCSRFToken(); ?>">
                        <button type="submit" class="nav-link btn-link">Déconnexion</button>
                    </form>
                </li>
            <?php else: ?>
                <li>
                    <a href="login.php" class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">
                        Connexion
                    </a>
                </li>
                <li>
                    <a href="register.php" class="nav-link <?php echo $currentPage === 'register.php' ? 'active' : ''; ?>">
                        Inscription
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');

    mobileMenuBtn.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });
});
</script> 