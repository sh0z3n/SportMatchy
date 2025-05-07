class MessageManager {
    constructor() {
        this.conversations = [];
        this.currentConversation = null;
        this.messages = [];
        this.initializeEventListeners();
        this.loadConversations();
    }

    initializeEventListeners() {
        // Gestion du bouton de messages
        const messageButton = document.querySelector('[data-message-button]');
        if (messageButton) {
            messageButton.addEventListener('click', (e) => this.handleMessageButtonClick(e));
        }

        // Gestion du panneau de messages
        const messagePanel = document.querySelector('[data-message-panel]');
        if (messagePanel) {
            messagePanel.addEventListener('click', (e) => this.handleMessagePanelClick(e));
        }

        // Gestion du formulaire de message
        const messageForm = document.querySelector('[data-message-form]');
        if (messageForm) {
            messageForm.addEventListener('submit', (e) => this.handleMessageSubmit(e));
        }

        // Gestion du scroll des messages
        const messageList = document.querySelector('[data-message-list]');
        if (messageList) {
            messageList.addEventListener('scroll', (e) => this.handleMessageScroll(e));
        }

        // Gestion de la recherche de conversations
        const searchInput = document.querySelector('[data-message-search]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleMessageSearch(e));
        }
    }

    async loadConversations() {
        try {
            const response = await fetch('/api/conversations', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.conversations = data.conversations;
                this.updateConversationUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des conversations:', error);
        }
    }

    async loadMessages(conversationId) {
        try {
            const response = await fetch(`/api/conversations/${conversationId}/messages`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.messages = data.messages;
                this.currentConversation = conversationId;
                this.updateMessageUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des messages:', error);
        }
    }

    handleMessageButtonClick(e) {
        e.preventDefault();
        const panel = document.querySelector('[data-message-panel]');
        if (panel) {
            panel.classList.toggle('active');
        }
    }

    handleMessagePanelClick(e) {
        const conversation = e.target.closest('[data-conversation-item]');
        if (conversation) {
            e.preventDefault();
            const conversationId = conversation.dataset.conversationId;
            this.loadMessages(conversationId);
        }
    }

    async handleMessageSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const messageInput = form.querySelector('[data-message-input]');
        const message = messageInput.value.trim();

        if (!message || !this.currentConversation) return;

        try {
            const response = await fetch(`/api/conversations/${this.currentConversation}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();

            if (data.success) {
                this.messages.push(data.message);
                this.updateMessageUI();
                messageInput.value = '';
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    handleMessageScroll(e) {
        const list = e.target;
        const isNearTop = list.scrollTop < 50;

        if (isNearTop && this.currentConversation) {
            this.loadMoreMessages();
        }
    }

    async loadMoreMessages() {
        if (!this.currentConversation || this.messages.length === 0) return;

        try {
            const response = await fetch(`/api/conversations/${this.currentConversation}/messages?before=${this.messages[0].id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                this.messages = [...data.messages, ...this.messages];
                this.updateMessageUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des messages:', error);
        }
    }

    handleMessageSearch(e) {
        const query = e.target.value.toLowerCase();
        const filteredConversations = this.conversations.filter(conversation => 
            conversation.name.toLowerCase().includes(query) ||
            conversation.lastMessage.toLowerCase().includes(query)
        );
        this.updateConversationUI(filteredConversations);
    }

    updateConversationUI(conversations = this.conversations) {
        const list = document.querySelector('[data-conversation-list]');
        if (list) {
            list.innerHTML = this.generateConversationListHTML(conversations);
        }
    }

    updateMessageUI() {
        const list = document.querySelector('[data-message-list]');
        if (list) {
            list.innerHTML = this.generateMessageListHTML();
            list.scrollTop = list.scrollHeight;
        }
    }

    generateConversationListHTML(conversations) {
        if (conversations.length === 0) {
            return `
                <div class="conversation-empty">
                    <i class="fas fa-comments"></i>
                    <p>Aucune conversation</p>
                </div>
            `;
        }

        return conversations.map(conversation => `
            <div class="conversation-item ${conversation.id === this.currentConversation ? 'active' : ''}" 
                 data-conversation-item 
                 data-conversation-id="${conversation.id}">
                <div class="conversation-avatar">
                    <img src="${conversation.avatar}" alt="${conversation.name}">
                    ${conversation.online ? '<span class="status online"></span>' : ''}
                </div>
                <div class="conversation-content">
                    <div class="conversation-header">
                        <div class="conversation-name">${conversation.name}</div>
                        <div class="conversation-time">${this.formatTime(conversation.lastMessageTime)}</div>
                    </div>
                    <div class="conversation-message">${conversation.lastMessage}</div>
                </div>
            </div>
        `).join('');
    }

    generateMessageListHTML() {
        if (this.messages.length === 0) {
            return `
                <div class="message-empty">
                    <i class="fas fa-comment-slash"></i>
                    <p>Aucun message</p>
                </div>
            `;
        }

        return this.messages.map(message => `
            <div class="message-item ${message.isMine ? 'mine' : 'other'}" data-message-id="${message.id}">
                <div class="message-avatar">
                    <img src="${message.avatar}" alt="${message.sender}">
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <div class="message-sender">${message.sender}</div>
                        <div class="message-time">${this.formatTime(message.created_at)}</div>
                    </div>
                    <div class="message-text">${message.text}</div>
                </div>
            </div>
        `).join('');
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        // Moins d'une minute
        if (diff < 60000) {
            return 'À l\'instant';
        }

        // Moins d'une heure
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
        }

        // Moins d'un jour
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
        }

        // Moins d'une semaine
        if (diff < 604800000) {
            const days = Math.floor(diff / 86400000);
            return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
        }

        // Date complète
        return date.toLocaleDateString();
    }

    generateMessageHTML() {
        return `
            <div class="message-container">
                <button class="message-button" data-message-button>
                    <i class="fas fa-comments"></i>
                    <span class="message-counter" data-message-counter>0</span>
                </button>
                <div class="message-panel" data-message-panel>
                    <div class="message-header">
                        <h3>Messages</h3>
                        <div class="message-search">
                            <input type="text" placeholder="Rechercher..." data-message-search>
                        </div>
                    </div>
                    <div class="message-content">
                        <div class="conversation-list" data-conversation-list>
                            ${this.generateConversationListHTML(this.conversations)}
                        </div>
                        <div class="message-detail">
                            <div class="message-list" data-message-list>
                                ${this.generateMessageListHTML()}
                            </div>
                            <form class="message-form" data-message-form>
                                <input type="text" placeholder="Écrivez votre message..." data-message-input>
                                <button type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.messageManager = new MessageManager();
}); 