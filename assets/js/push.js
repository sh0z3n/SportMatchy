class PushManager {
    constructor() {
        this.initializeEventListeners();
        this.checkPushSupport();
    }

    initializeEventListeners() {
        // Gestion des boutons de notification
        const pushButtons = document.querySelectorAll('[data-push-button]');
        pushButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handlePushButtonClick(e));
        });

        // Gestion des préférences de notification
        const notificationToggles = document.querySelectorAll('[data-notification-toggle]');
        notificationToggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => this.handleNotificationToggle(e));
        });
    }

    async checkPushSupport() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            this.updatePushStatus('unsupported');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                this.updatePushStatus('subscribed');
            } else {
                this.updatePushStatus('unsubscribed');
            }
        } catch (error) {
            console.error('Erreur lors de la vérification du support push:', error);
            this.updatePushStatus('error');
        }
    }

    async handlePushButtonClick(e) {
        e.preventDefault();
        const button = e.target;
        const action = button.dataset.pushAction;

        try {
            if (action === 'subscribe') {
                await this.subscribeToPush();
            } else if (action === 'unsubscribe') {
                await this.unsubscribeFromPush();
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors de la gestion des notifications.', 'error');
        }
    }

    async handleNotificationToggle(e) {
        const toggle = e.target;
        const type = toggle.dataset.notificationType;
        const enabled = toggle.checked;

        try {
            const response = await fetch('/api/notifications/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type,
                    enabled
                })
            });

            const data = await response.json();

            if (data.success) {
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
                // Restaurer l'état précédent
                toggle.checked = !toggle.checked;
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
            // Restaurer l'état précédent
            toggle.checked = !toggle.checked;
        }
    }

    async subscribeToPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });

            // Envoyer la souscription au serveur
            const response = await fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(subscription)
            });

            const data = await response.json();

            if (data.success) {
                this.updatePushStatus('subscribed');
                showFlashMessage(data.message, 'success');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Erreur lors de la souscription aux notifications:', error);
            showFlashMessage('Une erreur est survenue lors de la souscription aux notifications.', 'error');
            this.updatePushStatus('error');
        }
    }

    async unsubscribeFromPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();

                // Informer le serveur
                const response = await fetch('/api/push/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(subscription)
                });

                const data = await response.json();

                if (data.success) {
                    this.updatePushStatus('unsubscribed');
                    showFlashMessage(data.message, 'success');
                } else {
                    throw new Error(data.message);
                }
            }
        } catch (error) {
            console.error('Erreur lors de la désinscription des notifications:', error);
            showFlashMessage('Une erreur est survenue lors de la désinscription des notifications.', 'error');
        }
    }

    updatePushStatus(status) {
        const buttons = document.querySelectorAll('[data-push-button]');
        buttons.forEach(button => {
            const action = button.dataset.pushAction;
            button.style.display = 'none';
        });

        switch (status) {
            case 'subscribed':
                document.querySelector('[data-push-button="unsubscribe"]').style.display = 'block';
                break;
            case 'unsubscribed':
                document.querySelector('[data-push-button="subscribe"]').style.display = 'block';
                break;
            case 'unsupported':
                document.querySelector('[data-push-button="unsupported"]').style.display = 'block';
                break;
            case 'error':
                document.querySelector('[data-push-button="error"]').style.display = 'block';
                break;
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.pushManager = new PushManager();
}); 