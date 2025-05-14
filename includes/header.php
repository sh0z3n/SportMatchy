<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="header">
    <nav class="nav container">
        <div class="header-content" style="display: flex; align-items: center; gap: 3rem;">
            <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 0.7rem; font-size: 1.7rem; font-weight: bold; color: #43b047; text-decoration: none;">
                <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg/1f3c3.svg" alt="SportMatchy Logo" style="height: 2.2rem; width: 2.2rem;">
                <?php echo APP_NAME; ?>
            </a>
            <button id="darkmode-toggle" style="margin-left:2rem;font-size:1.5rem;background:none;border:none;cursor:pointer;" title="Mode sombre/clair">🌙</button>
        </div>
        <ul class="nav-menu" style="display: flex; align-items: center; gap: 2.5rem; margin-left: 3rem;">
            <li><a href="index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Accueil</a></li>
            <li><a href="events.php" class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">Événements</a></li>
            <li><a href="sports.php" class="nav-link <?php echo $currentPage === 'sports.php' ? 'active' : ''; ?>">Sports</a></li>
            <li><a href="matches.php" class="nav-link ">Matchs</a></li>
            <li><a href="about.php" class="nav-link ">À propos</a></li>
            <?php if (Session::isLoggedIn()): ?>
                <li>
                    <form method="POST" action="logout.php" class="inline-form" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::generateCSRFToken(); ?>">
                        <button type="submit" class="nav-link btn-link" style="background:none; border:none; color:#222; font-size:1.1rem; padding:0.5rem 1rem; cursor:pointer; border-radius:0.5rem; transition:color 0.2s, background 0.2s;">Déconnexion</button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">Connexion</a></li>
                <li><a href="register.php" class="nav-link <?php echo $currentPage === 'register.php' ? 'active' : ''; ?>">Inscription</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<script>
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    document.getElementById('darkmode-toggle').textContent = theme === 'dark' ? '☀️' : '🌙';
}
const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
setTheme(savedTheme);
document.getElementById('darkmode-toggle').onclick = function() {
    const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
};
</script> 