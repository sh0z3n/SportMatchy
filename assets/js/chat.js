class ChatClient {
    constructor() {
        this.socket = null;
        this.userId = null;
        this.currentGroup = null;
        this.typingTimeout = null;
        this.initialize();
    }

    initialize() {
        // Get user ID from the page
        const userIdElement = document.getElementById('user-id');
        if (userIdElement) {
            this.userId = userIdElement.dataset.userId;
        }

        // Initialize WebSocket connection
        this.connectWebSocket();

        // Initialize event listeners
        this.initializeEventListeners();
    }

    connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.hostname;
        const port = '8080';
        
        this.socket = new WebSocket(`${protocol}//${host}:${port}`);

        this.socket.onopen = () => {
            console.log('WebSocket connection established');
            this.authenticate();
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleMessage(data);
        };

        this.socket.onclose = () => {
            console.log('WebSocket connection closed');
            // Attempt to reconnect after 5 seconds
            setTimeout(() => this.connectWebSocket(), 5000);
        };

        this.socket.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }

    authenticate() {
        if (this.userId) {
            this.socket.send(JSON.stringify({
                type: 'auth',
                userId: this.userId
            }));
        }
    }

    initializeEventListeners() {
        const messageForm = document.getElementById('message-form');
        const messageInput = document.getElementById('message-input');
        const groupSelect = document.getElementById('group-select');

        if (messageForm) {
            messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        if (messageInput) {
            messageInput.addEventListener('input', () => {
                this.handleTyping(true);
            });
        }

        if (groupSelect) {
            groupSelect.addEventListener('change', (e) => {
                this.changeGroup(e.target.value);
            });
        }
    }

    sendMessage() {
        const messageInput = document.getElementById('message-input');
        const message = messageInput.value.trim();

        if (message && this.currentGroup) {
            this.socket.send(JSON.stringify({
                type: 'message',
                groupId: this.currentGroup,
                message: message
            }));

            messageInput.value = '';
            this.handleTyping(false);
        }
    }

    handleMessage(data) {
        switch (data.type) {
            case 'message':
                this.displayMessage(data);
                break;
            case 'typing':
                this.displayTypingIndicator(data);
                break;
        }
    }

    displayMessage(data) {
        const messagesContainer = document.getElementById('messages-container');
        if (!messagesContainer) return;

        const messageElement = document.createElement('div');
        messageElement.className = `message ${data.userId === this.userId ? 'sent' : 'received'}`;
        
        const timestamp = new Date(data.timestamp).toLocaleTimeString();
        
        messageElement.innerHTML = `
            <div class="message-content">
                <div class="sender">${data.userId === this.userId ? 'You' : 'User ' + data.userId}</div>
                <div class="text">${this.escapeHtml(data.message)}</div>
                <div class="time">${timestamp}</div>
            </div>
        `;

        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    displayTypingIndicator(data) {
        const typingIndicator = document.getElementById('typing-indicator');
        if (!typingIndicator) return;

        if (data.isTyping && data.userId !== this.userId) {
            typingIndicator.textContent = 'Someone is typing...';
            typingIndicator.style.display = 'block';
        } else {
            typingIndicator.style.display = 'none';
        }
    }

    handleTyping(isTyping) {
        if (this.currentGroup) {
            this.socket.send(JSON.stringify({
                type: 'typing',
                groupId: this.currentGroup,
                isTyping: isTyping
            }));

            if (isTyping) {
                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => {
                    this.handleTyping(false);
                }, 3000);
            }
        }
    }

    changeGroup(groupId) {
        this.currentGroup = groupId;
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
        }
        // Load group messages
        this.loadGroupMessages(groupId);
    }

    loadGroupMessages(groupId) {
        fetch(`/api/messages.php?groupId=${groupId}`)
            .then(response => response.json())
            .then(messages => {
                messages.forEach(message => {
                    this.displayMessage({
                        type: 'message',
                        userId: message.user_id,
                        message: message.message,
                        timestamp: message.created_at
                    });
                });
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize chat when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ChatClient();
}); 