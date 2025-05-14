<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>À propos</h3>
                <p>SportMatchy est votre plateforme de rencontre sportive. Trouvez des partenaires, rejoignez des événements et partagez votre passion pour le sport.</p>
            </div>
            
            <div class="footer-section">
                <h3>Liens rapides</h3>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="events.php">Événements</a></li>
                    <li><a href="sports.php">Sports</a></li>
                    <?php if (Session::isLoggedIn()): ?>
                        <li><a href="create-event.php">Créer un événement</a></li>
                        <li><a href="profile.php">Mon profil</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact</h3>
                <p><i class="fas fa-envelope"></i> <?php echo APP_EMAIL; ?></p>
                <p><i class="fas fa-phone"></i> +33 1 23 45 67 89</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
<script src="assets/js/main.js"></script>
</body>
</html> 