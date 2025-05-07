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

<!-- Chat Button -->
<?php if (Session::isLoggedIn()): ?>
    <button id="chatButton" class="chat-button" title="Chat">
        <i class="fas fa-comments"></i>
    </button>

    <!-- Chat Window -->
    <div id="chatWindow" class="chat-window hidden">
        <div class="chat-header">
            <h3>Chat</h3>
            <div class="chat-actions">
                <button id="createGroupButton" class="btn btn-sm" title="Créer un groupe">
                    <i class="fas fa-users"></i>
                </button>
                <button class="close-chat" title="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="chat-sidebar">
            <select id="groupSelect" class="form-control">
                <option value="">Sélectionner un groupe</option>
            </select>
        </div>
        <div id="messageContainer" class="message-container"></div>
        <div class="chat-input-container">
            <div class="chat-toolbar">
                <button id="locationButton" class="btn btn-sm" title="Partager ma position">
                    <i class="fas fa-map-marker-alt"></i>
                </button>
                <button id="imageButton" class="btn btn-sm" title="Envoyer une image">
                    <i class="fas fa-image"></i>
                </button>
                <input type="file" id="imageInput" accept="image/*" class="hidden">
            </div>
            <textarea id="messageInput" class="form-control" placeholder="Écrivez votre message..."></textarea>
            <button id="sendButton" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Créer un groupe</h3>
                <button class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="createGroupForm" class="modal-body">
                <div class="form-group">
                    <label for="groupName">Nom du groupe</label>
                    <input type="text" id="groupName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="groupDescription">Description</label>
                    <textarea id="groupDescription" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Membres</label>
                    <div id="memberList" class="member-list">
                        <!-- Member list will be populated dynamically -->
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Créer</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Chatbot Button -->
<button id="chatbotButton" class="chatbot-button" title="Assistant">
    <i class="fas fa-robot"></i>
</button>

<!-- Chatbot Window -->
<div id="chatbotWindow" class="chatbot-window hidden">
    <div class="chatbot-header">
        <h3>Assistant SportMatchy</h3>
        <button class="close-chatbot">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div id="chatbotMessages" class="chatbot-messages"></div>
    <div class="chatbot-input-container">
        <input type="text" id="chatbotInput" class="form-control" placeholder="Posez votre question...">
        <button id="chatbotSendButton" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Scripts -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/chat.js"></script>
<script src="assets/js/chatbot.js"></script>
</body>
</html> 