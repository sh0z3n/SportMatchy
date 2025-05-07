    </main>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>À propos</h5>
                    <p>SportMatchy est votre plateforme de matchmaking sportif pour trouver des partenaires et participer à des événements sportifs.</p>
                </div>
                <div class="col-md-4">
                    <h5>Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="/?page=home">Accueil</a></li>
                        <li><a href="/?page=events">Événements</a></li>
                        <li><a href="/?page=about">À propos</a></li>
                        <li><a href="/?page=contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Suivez-nous</h5>
                    <div class="social-links">
                        <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- WebSocket Connection -->
    <?php if ($isLoggedIn): ?>
    <script>
        const ws = new WebSocket('ws://<?php echo WS_HOST; ?>:<?php echo WS_PORT; ?>');
        
        ws.onopen = function() {
            console.log('WebSocket Connected');
            ws.send(JSON.stringify({
                type: 'auth',
                userId: <?php echo $currentUser['id']; ?>
            }));
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            handleWebSocketMessage(data);
        };

        ws.onclose = function() {
            console.log('WebSocket Disconnected');
        };

        function handleWebSocketMessage(data) {
            switch(data.type) {
                case 'event_update':
                    updateEventUI(data.event);
                    break;
                case 'new_message':
                    showNotification(data.message);
                    break;
                case 'participant_joined':
                    updateParticipantsList(data.eventId, data.participant);
                    break;
            }
        }
    </script>
    <?php endif; ?>
</body>
</html> 