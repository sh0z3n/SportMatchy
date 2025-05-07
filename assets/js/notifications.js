class NotificationManager {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.initializeEventListeners();
        this.loadNotifications();
    }

    initializeEventListeners() {
        // Gestion du bouton de notifications
        const notificationButton = document.querySelector('[data-notification-button]');
        if (notificationButton) {
            notificationButton.addEventListener('click', (e) => this.handleNotificationButtonClick(e));
        }

        // Gestion du panneau de notifications
        const notificationPanel = document.querySelector('[data-notification-panel]');
        if (notificationPanel) {
            notificationPanel.addEventListener('click', (e) => this.handleNotificationPanelClick(e));
        }

        // Gestion des actions de notification
        const notificationActions = document.querySelectorAll('[data-notification-action]');
        notificationActions.forEach(action => {
            action.addEventListener('click', (e) => this.handleNotificationAction(e));
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('/api/notifications', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.notifications = data.notifications;
                this.unreadCount = data.unreadCount;
                this.updateNotificationUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
        }
    }

    handleNotificationButtonClick(e) {
        e.preventDefault();
        const panel = document.querySelector('[data-notification-panel]');
        if (panel) {
            panel.classList.toggle('active');
        }
    }

    handleNotificationPanelClick(e) {
        const action = e.target.closest('[data-notification-action]');
        if (action) {
            e.preventDefault();
            this.handleNotificationAction(e);
        }
    }

    async handleNotificationAction(e) {
        const action = e.target.closest('[data-notification-action]');
        const notificationId = action.dataset.notificationId;
        const actionType = action.dataset.notificationAction;

        try {
            const response = await fetch(`/api/notifications/${notificationId}/${actionType}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                if (actionType === 'read') {
                    this.markNotificationAsRead(notificationId);
                } else if (actionType === 'delete') {
                    this.deleteNotification(notificationId);
                }
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    markNotificationAsRead(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification && !notification.read) {
            notification.read = true;
            this.unreadCount--;
            this.updateNotificationUI();
        }
    }

    deleteNotification(notificationId) {
        const index = this.notifications.findIndex(n => n.id === notificationId);
        if (index !== -1) {
            const notification = this.notifications[index];
            if (!notification.read) {
                this.unreadCount--;
            }
            this.notifications.splice(index, 1);
            this.updateNotificationUI();
        }
    }

    updateNotificationUI() {
        // Mise à jour du compteur de notifications non lues
        const counter = document.querySelector('[data-notification-counter]');
        if (counter) {
            counter.textContent = this.unreadCount;
            counter.style.display = this.unreadCount > 0 ? 'block' : 'none';
        }

        // Mise à jour de la liste des notifications
        const list = document.querySelector('[data-notification-list]');
        if (list) {
            list.innerHTML = this.generateNotificationListHTML();
        }
    }

    generateNotificationListHTML() {
        if (this.notifications.length === 0) {
            return `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune notification</p>
                </div>
            `;
        }

        return this.notifications.map(notification => `
            <div class="notification-item ${notification.read ? 'read' : 'unread'}" data-notification-id="${notification.id}">
                <div class="notification-icon">
                    <i class="fas ${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                </div>
                <div class="notification-actions">
                    ${!notification.read ? `
                        <button class="btn btn-sm btn-primary" data-notification-action="read" data-notification-id="${notification.id}">
                            <i class="fas fa-check"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-danger" data-notification-action="delete" data-notification-id="${notification.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    getNotificationIcon(type) {
        const icons = {
            'activity': 'fa-calendar',
            'message': 'fa-envelope',
            'friend': 'fa-user',
            'team': 'fa-users',
            'match': 'fa-trophy',
            'system': 'fa-cog'
        };
        return icons[type] || 'fa-bell';
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

    generateNotificationHTML() {
        return `
            <div class="notification-container">
                <button class="notification-button" data-notification-button>
                    <i class="fas fa-bell"></i>
                    <span class="notification-counter" data-notification-counter>0</span>
                </button>
                <div class="notification-panel" data-notification-panel>
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button class="btn btn-sm btn-secondary" data-notification-action="read-all">
                            <i class="fas fa-check-double"></i>
                            Tout marquer comme lu
                        </button>
                    </div>
                    <div class="notification-list" data-notification-list>
                        ${this.generateNotificationListHTML()}
                    </div>
                </div>
            </div>
        `;
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
}); 