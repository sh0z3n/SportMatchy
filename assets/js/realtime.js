// Gestion des interactions en temps réel
class RealtimeManager {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 5000;
        this.initializeWebSocket();
    }

    initializeWebSocket() {
        const protocol = WS_SECURE ? 'wss' : 'ws';
        const wsUrl = `${protocol}://${WS_HOST}:${WS_PORT}`;
        
        try {
            this.ws = new WebSocket(wsUrl);
            this.setupWebSocketHandlers();
        } catch (error) {
            console.error('Erreur de connexion WebSocket:', error);
            this.handleReconnect();
        }
    }

    setupWebSocketHandlers() {
        this.ws.onopen = () => {
            console.log('Connexion WebSocket établie');
            this.reconnectAttempts = 0;
            this.authenticate();
        };

        this.ws.onclose = () => {
            console.log('Connexion WebSocket fermée');
            this.handleReconnect();
        };

        this.ws.onerror = (error) => {
            console.error('Erreur WebSocket:', error);
        };

        this.ws.onmessage = (event) => {
            this.handleMessage(event.data);
        };
    }

    authenticate() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token) {
            this.send({
                type: 'authenticate',
                token: token
            });
        }
    }

    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Tentative de reconnexion ${this.reconnectAttempts}/${this.maxReconnectAttempts}`);
            setTimeout(() => this.initializeWebSocket(), this.reconnectDelay);
        } else {
            console.error('Nombre maximum de tentatives de reconnexion atteint');
            showFlashMessage('Connexion en temps réel perdue. Veuillez rafraîchir la page.', 'error');
        }
    }

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        }
    }

    handleMessage(data) {
        try {
            const message = JSON.parse(data);
            
            switch (message.type) {
                case 'new_event':
                    this.handleNewEvent(message.data);
                    break;
                case 'event_update':
                    this.handleEventUpdate(message.data);
                    break;
                case 'new_participant':
                    this.handleNewParticipant(message.data);
                    break;
                case 'participant_left':
                    this.handleParticipantLeft(message.data);
                    break;
                case 'new_message':
                    this.handleNewMessage(message.data);
                    break;
                case 'user_status':
                    this.handleUserStatus(message.data);
                    break;
                default:
                    console.warn('Type de message non géré:', message.type);
            }
        } catch (error) {
            console.error('Erreur lors du traitement du message:', error);
        }
    }

    handleNewEvent(event) {
        const eventList = document.querySelector('.event-list');
        if (eventList) {
            const eventCard = document.createElement('div');
            eventCard.className = 'event-card';
            eventCard.dataset.eventId = event.id;
            eventCard.innerHTML = window.eventManager.generateEventCardHTML(event);
            eventList.insertBefore(eventCard, eventList.firstChild);
            window.eventManager.initializeEventListeners();
        }
        showFlashMessage(`Nouvel événement : ${event.title}`, 'info');
    }

    handleEventUpdate(event) {
        const eventCard = document.querySelector(`[data-event-id="${event.id}"]`);
        if (eventCard) {
            eventCard.innerHTML = window.eventManager.generateEventCardHTML(event);
            window.eventManager.initializeEventListeners();
        }
    }

    handleNewParticipant(data) {
        const { eventId, participant } = data;
        const participantsList = document.querySelector(`[data-event-participants="${eventId}"]`);
        if (participantsList) {
            const participantElement = document.createElement('div');
            participantElement.className = 'participant-item';
            participantElement.innerHTML = `
                <img src="${participant.avatar || '/assets/images/default-avatar.png'}" alt="${participant.username}" class="participant-avatar">
                <span class="participant-name">${participant.username}</span>
                <span class="participant-status">${formatParticipantStatus(participant.status)}</span>
            `;
            participantsList.appendChild(participantElement);
        }
        showFlashMessage(`${participant.username} a rejoint l'événement`, 'info');
    }

    handleParticipantLeft(data) {
        const { eventId, userId } = data;
        const participantsList = document.querySelector(`[data-event-participants="${eventId}"]`);
        if (participantsList) {
            const participantElement = participantsList.querySelector(`[data-user-id="${userId}"]`);
            if (participantElement) {
                participantElement.remove();
            }
        }
    }

    handleNewMessage(data) {
        const { eventId, message } = data;
        const chatContainer = document.querySelector(`[data-event-chat="${eventId}"]`);
        if (chatContainer) {
            const messageElement = document.createElement('div');
            messageElement.className = `message ${message.user_id === currentUserId ? 'message-sent' : 'message-received'}`;
            messageElement.innerHTML = `
                <div class="message-header">
                    <img src="${message.avatar || '/assets/images/default-avatar.png'}" alt="${message.username}" class="message-avatar">
                    <span class="message-username">${message.username}</span>
                    <span class="message-time">${formatDateRelative(message.created_at)}</span>
                </div>
                <div class="message-content">${message.content}</div>
            `;
            chatContainer.appendChild(messageElement);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    handleUserStatus(data) {
        const { userId, status } = data;
        const userElements = document.querySelectorAll(`[data-user-id="${userId}"]`);
        userElements.forEach(element => {
            element.classList.remove('online', 'offline', 'away');
            element.classList.add(status);
        });
    }

    subscribeToEvent(eventId) {
        this.send({
            type: 'subscribe',
            event_id: eventId
        });
    }

    unsubscribeFromEvent(eventId) {
        this.send({
            type: 'unsubscribe',
            event_id: eventId
        });
    }

    sendMessage(eventId, content) {
        this.send({
            type: 'message',
            event_id: eventId,
            content: content
        });
    }

    updateUserStatus(status) {
        this.send({
            type: 'status',
            status: status
        });
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.realtimeManager = new RealtimeManager();
}); 