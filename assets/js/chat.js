class Chat {
    constructor() {
        this.chatButton = document.getElementById('chatButton');
        this.chatWindow = document.getElementById('chatWindow');
        this.chatHeader = document.getElementById('chatHeader');
        this.messageContainer = document.getElementById('messageContainer');
        this.messageInput = document.getElementById('messageInput');
        this.sendButton = document.getElementById('sendButton');
        this.groupSelect = document.getElementById('groupSelect');
        this.createGroupButton = document.getElementById('createGroupButton');
        this.createGroupModal = document.getElementById('createGroupModal');
        this.closeModalButtons = document.querySelectorAll('.close-modal');
        this.currentGroupId = null;
        this.lastMessageId = 0;
        this.pollingInterval = null;
        this.map = null;
        this.locationButton = document.getElementById('locationButton');
        this.imageButton = document.getElementById('imageButton');
        this.imageInput = document.getElementById('imageInput');
        this.emojiButton = document.querySelector('.chat-emoji');
        this.videoButton = document.querySelector('.chat-video');
        this.groupList = document.querySelector('.chat-groups');
        this.currentGroup = null;
        this.typingTimeout = null;
        this.typingUsers = new Set();

        this.initializeEventListeners();
        this.loadGroups();
        this.startPolling();
        this.initializeEmojiPicker();
    }

    initializeEventListeners() {
        // Chat window toggle
        this.chatButton.addEventListener('click', () => this.toggleChat());
        
        // Send message
        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Typing indicator
        this.messageInput.addEventListener('input', () => this.handleTyping());

        // Group selection
        this.groupSelect.addEventListener('change', () => this.handleGroupChange());

        // Create group
        this.createGroupButton.addEventListener('click', () => this.showCreateGroupModal());
        this.closeModalButtons.forEach(button => {
            button.addEventListener('click', () => this.hideCreateGroupModal());
        });

        // Location sharing
        this.locationButton.addEventListener('click', () => this.shareLocation());

        // Image upload
        this.imageButton.addEventListener('click', () => this.imageInput.click());
        this.imageInput.addEventListener('change', (e) => this.handleImageUpload(e));

        // Close chat when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.chatWindow.contains(e.target) && !this.chatButton.contains(e.target)) {
                this.chatWindow.classList.add('hidden');
            }
        });

        this.emojiButton.addEventListener('click', () => this.toggleEmojiPicker());
        this.videoButton.addEventListener('click', () => this.toggleVideoUpload());

        // Group selection
        this.groupList.addEventListener('click', (e) => {
            const groupItem = e.target.closest('.chat-group-item');
            if (groupItem) {
                const groupId = groupItem.dataset.groupId;
                this.selectGroup(groupId);
            }
        });
    }

    async loadGroups() {
        try {
            const response = await fetch('/api/chat.php?action=get_groups');
            const data = await response.json();
            
            if (data.success) {
                this.renderGroups(data.groups);
                if (data.groups.length > 0) {
                    this.selectGroup(data.groups[0].id);
                }
            }
        } catch (error) {
            console.error('Error loading groups:', error);
        }
    }

    renderGroups(groups) {
        this.groupSelect.innerHTML = '<option value="">Sélectionner un groupe</option>';
        groups.forEach(group => {
            const option = document.createElement('option');
            option.value = group.id;
            option.textContent = group.name;
            this.groupSelect.appendChild(option);
        });
    }

    async loadMessages() {
        if (!this.currentGroup) return;

        try {
            const response = await fetch(`/api/chat.php?action=get_messages&group_id=${this.currentGroup}&last_id=${this.lastMessageId}`);
            const data = await response.json();
            
            if (data.success) {
                data.messages.forEach(message => {
                    this.addMessage(message);
                    this.lastMessageId = Math.max(this.lastMessageId, message.id);
                });
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    async sendMessage(content = null) {
        if (!this.currentGroup) return;

        const message = content || this.messageInput.value.trim();
        if (!message) return;

        try {
            const response = await fetch('/api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'send',
                    group_id: this.currentGroup,
                    message: message,
                    type: 'text'
                })
            });

            const data = await response.json();
            if (data.success) {
                this.messageInput.value = '';
                this.addMessage(data.message);
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    addMessage(message) {
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${message.user_id === this.currentUserId ? 'sent' : 'received'}`;
        
        let content = '';
        switch (message.type) {
            case 'text':
                content = this.formatMessage(message.message);
                break;
            case 'location':
                content = this.createLocationMessage(message.message);
                break;
            case 'video':
                content = this.createVideoMessage(message.message);
                break;
            case 'image':
                content = this.createImageMessage(message.message);
                break;
        }

        messageElement.innerHTML = `
            <div class="chat-message-avatar">
                <img src="${message.profile_image || '/assets/images/default-avatar.png'}" alt="${message.username}">
            </div>
            <div class="chat-message-content">
                <div class="chat-message-header">
                    <span class="chat-message-username">${this.escapeHtml(message.username)}</span>
                    <span class="chat-message-time">${this.formatTime(message.created_at)}</span>
                </div>
                <div class="chat-message-body">${content}</div>
            </div>
        `;

        this.messageContainer.appendChild(messageElement);
    }

    handleTyping() {
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        this.typingTimeout = setTimeout(() => {
            this.sendTypingStatus(false);
        }, 1000);

        this.sendTypingStatus(true);
    }

    async sendTypingStatus(isTyping) {
        if (!this.currentGroup) return;

        try {
            await fetch('/api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'typing',
                    group_id: this.currentGroup,
                    is_typing: isTyping
                })
            });
        } catch (error) {
            console.error('Error sending typing status:', error);
        }
    }

    updateTypingIndicator(users) {
        const typingIndicator = document.querySelector('.typing-indicator');
        if (!typingIndicator) return;

        if (users.size > 0) {
            const names = Array.from(users).join(', ');
            typingIndicator.textContent = `${names} ${users.size === 1 ? 'est en train d\'écrire...' : 'sont en train d\'écrire...'}`;
            typingIndicator.style.display = 'block';
        } else {
            typingIndicator.style.display = 'none';
        }
    }

    initializeEmojiPicker() {
        const emojiButton = document.querySelector('.chat-emoji');
        const emojiPicker = document.createElement('div');
        emojiPicker.className = 'emoji-picker hidden';
        
        // Add emoji categories
        const categories = ['😀', '❤️', '👍', '🎮', '⚽', '🎵'];
        categories.forEach(emoji => {
            const button = document.createElement('button');
            button.textContent = emoji;
            button.addEventListener('click', () => {
                this.messageInput.value += emoji;
                this.messageInput.focus();
            });
            emojiPicker.appendChild(button);
        });

        emojiButton.parentNode.insertBefore(emojiPicker, emojiButton.nextSibling);

        emojiButton.addEventListener('click', () => {
            emojiPicker.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!emojiButton.contains(e.target) && !emojiPicker.contains(e.target)) {
                emojiPicker.classList.add('hidden');
            }
        });
    }

    startPolling() {
        this.pollingInterval = setInterval(() => {
            if (this.currentGroup) {
                this.loadMessages();
                this.checkTypingStatus();
            }
        }, 2000);
    }

    async checkTypingStatus() {
        if (!this.currentGroup) return;

        try {
            const response = await fetch(`/api/chat.php?action=get_typing&group_id=${this.currentGroup}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateTypingIndicator(new Set(data.typing_users));
            }
        } catch (error) {
            console.error('Error checking typing status:', error);
        }
    }

    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }

    toggleChat() {
        this.chatWindow.classList.toggle('hidden');
        if (!this.chatWindow.classList.contains('hidden')) {
            this.messageInput.focus();
        }
    }

    scrollToBottom() {
        this.messageContainer.scrollTop = this.messageContainer.scrollHeight;
    }

    formatMessage(message) {
        // Convert URLs to clickable links
        message = message.replace(
            /(https?:\/\/[^\s]+)/g,
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
        );

        // Convert emojis
        message = message.replace(/:\w+:/g, match => {
            const emoji = this.getEmoji(match);
            return emoji || match;
        });

        return message;
    }

    getEmoji(code) {
        const emojiMap = {
            ':smile:': '😊',
            ':heart:': '❤️',
            ':thumbsup:': '👍',
            ':soccer:': '⚽',
            ':basketball:': '🏀',
            ':tennis:': '🎾'
        };
        return emojiMap[code];
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async selectGroup(groupId) {
        this.currentGroup = groupId;
        this.lastMessageId = 0;
        this.messageContainer.innerHTML = '';
        this.loadMessages();
        
        // Update active state
        document.querySelectorAll('.chat-group-item').forEach(item => {
            item.classList.toggle('active', item.dataset.groupId === groupId);
        });
    }

    async shareLocation() {
        if (!navigator.geolocation) {
            alert('La géolocalisation n\'est pas supportée par votre navigateur.');
            return;
        }

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject);
            });

            const { latitude, longitude } = position.coords;
            const location = {
                lat: latitude,
                lng: longitude,
                name: 'Ma position'
            };

            await this.sendMessage(JSON.stringify(location));
        } catch (error) {
            console.error('Error getting location:', error);
            alert('Impossible d\'obtenir votre position.');
        }
    }

    async handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Veuillez sélectionner une image');
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await fetch('/api/chat.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.sendMessage(data.url);
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            alert('Erreur lors du téléchargement de l\'image');
        }
    }

    async createGroup(name, description, members) {
        try {
            const response = await fetch('/api/chat.php?action=create_group', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name,
                    description,
                    members
                })
            });

            const data = await response.json();
            if (data.success) {
                this.loadGroups();
                this.hideCreateGroupModal();
            }
        } catch (error) {
            console.error('Error creating group:', error);
        }
    }

    initializeMap(container, location) {
        const [lat, lng] = location.split(',');
        const map = new google.maps.Map(container, {
            center: { lat: parseFloat(lat), lng: parseFloat(lng) },
            zoom: 15
        });

        new google.maps.Marker({
            position: { lat: parseFloat(lat), lng: parseFloat(lng) },
            map: map
        });
    }

    showCreateGroupModal() {
        this.createGroupModal.classList.remove('hidden');
    }

    hideCreateGroupModal() {
        this.createGroupModal.classList.add('hidden');
    }

    async toggleVideoUpload() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'video/*';
        
        input.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 50 * 1024 * 1024) { // 50MB limit
                alert('La vidéo ne doit pas dépasser 50MB.');
                return;
            }

            const formData = new FormData();
            formData.append('video', file);
            formData.append('action', 'upload_video');
            formData.append('group_id', this.currentGroup);

            try {
                const response = await fetch('/api/chat.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    await this.sendMessage(data.video_url);
                }
            } catch (error) {
                console.error('Error uploading video:', error);
                alert('Erreur lors de l\'upload de la vidéo.');
            }
        };

        input.click();
    }

    toggleEmojiPicker() {
        // Implementation of emoji picker
        // You can use a library like emoji-mart or implement your own
    }

    handleGroupChange() {
        const groupId = this.groupSelect.value;
        if (groupId) {
            this.currentGroupId = groupId;
            this.messageContainer.innerHTML = '';
            this.lastMessageId = 0;
            this.startPolling();
        } else {
            this.currentGroupId = null;
            this.stopPolling();
        }
    }
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.chat = new Chat();
}); 