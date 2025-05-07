class UserManager {
    constructor() {
        this.currentUser = null;
        this.initializeEventListeners();
        this.loadCurrentUser();
    }

    initializeEventListeners() {
        // Gestion du formulaire de profil
        const profileForm = document.querySelector('[data-profile-form]');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.handleProfileSubmit(e));
        }

        // Gestion du formulaire d'avatar
        const avatarForm = document.querySelector('[data-avatar-form]');
        if (avatarForm) {
            avatarForm.addEventListener('submit', (e) => this.handleAvatarSubmit(e));
        }

        // Gestion du formulaire de mot de passe
        const passwordForm = document.querySelector('[data-password-form]');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => this.handlePasswordSubmit(e));
        }

        // Gestion du formulaire de préférences
        const preferencesForm = document.querySelector('[data-preferences-form]');
        if (preferencesForm) {
            preferencesForm.addEventListener('submit', (e) => this.handlePreferencesSubmit(e));
        }

        // Gestion des sports favoris
        const favoriteSports = document.querySelectorAll('[data-favorite-sport]');
        favoriteSports.forEach(sport => {
            sport.addEventListener('click', (e) => this.handleFavoriteSport(e));
        });

        // Gestion des disponibilités
        const availabilityInputs = document.querySelectorAll('[data-availability]');
        availabilityInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleAvailabilityChange(e));
        });

        // Gestion des notifications
        const notificationToggles = document.querySelectorAll('[data-notification-toggle]');
        notificationToggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => this.handleNotificationToggle(e));
        });

        // Gestion de la confidentialité
        const privacyToggles = document.querySelectorAll('[data-privacy-toggle]');
        privacyToggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => this.handlePrivacyToggle(e));
        });

        // Gestion de la suppression de compte
        const deleteButton = document.querySelector('[data-delete-account]');
        if (deleteButton) {
            deleteButton.addEventListener('click', (e) => this.handleDeleteAccount(e));
        }
    }

    async loadCurrentUser() {
        try {
            const response = await fetch('/api/user', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement de l\'utilisateur:', error);
        }
    }

    async handleProfileSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/api/user/profile', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleAvatarSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const fileInput = form.querySelector('[data-avatar-input]');
        const file = fileInput.files[0];

        if (!file) return;

        formData.append('avatar', file);

        try {
            const response = await fetch('/api/user/avatar', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handlePasswordSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/api/user/password', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                form.reset();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handlePreferencesSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/api/user/preferences', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleFavoriteSport(e) {
        e.preventDefault();
        const sport = e.target;
        const sportId = sport.dataset.favoriteSport;
        const isFavorite = sport.classList.contains('active');

        try {
            const response = await fetch('/api/user/favorite-sport', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    sport_id: sportId,
                    favorite: !isFavorite
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleAvailabilityChange(e) {
        const input = e.target;
        const day = input.dataset.availability;
        const value = input.value;

        try {
            const response = await fetch('/api/user/availability', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    day: day,
                    value: value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
                showFlashMessage(data.message, 'success');
            } else {
                input.value = input.dataset.originalValue;
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            input.value = input.dataset.originalValue;
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleNotificationToggle(e) {
        const toggle = e.target;
        const notificationKey = toggle.dataset.notificationToggle;
        const value = toggle.checked;

        try {
            const response = await fetch('/api/user/notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    key: notificationKey,
                    value: value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
                showFlashMessage(data.message, 'success');
            } else {
                toggle.checked = !value;
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            toggle.checked = !value;
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handlePrivacyToggle(e) {
        const toggle = e.target;
        const privacyKey = toggle.dataset.privacyToggle;
        const value = toggle.checked;

        try {
            const response = await fetch('/api/user/privacy', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    key: privacyKey,
                    value: value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentUser = data.user;
                this.updateUserUI();
                showFlashMessage(data.message, 'success');
            } else {
                toggle.checked = !value;
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            toggle.checked = !value;
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleDeleteAccount(e) {
        e.preventDefault();

        if (!confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
            return;
        }

        try {
            const response = await fetch('/api/user', {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = '/';
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    updateUserUI() {
        if (!this.currentUser) return;

        // Mise à jour de l'avatar
        const avatars = document.querySelectorAll('[data-user-avatar]');
        avatars.forEach(avatar => {
            avatar.src = this.currentUser.avatar;
            avatar.alt = this.currentUser.name;
        });

        // Mise à jour du nom
        const names = document.querySelectorAll('[data-user-name]');
        names.forEach(name => {
            name.textContent = this.currentUser.name;
        });

        // Mise à jour des sports favoris
        const favoriteSports = document.querySelectorAll('[data-favorite-sport]');
        favoriteSports.forEach(sport => {
            const sportId = sport.dataset.favoriteSport;
            sport.classList.toggle('active', this.currentUser.favorite_sports.includes(sportId));
        });

        // Mise à jour des disponibilités
        Object.entries(this.currentUser.availability).forEach(([day, value]) => {
            const input = document.querySelector(`[data-availability="${day}"]`);
            if (input) {
                input.value = value;
                input.dataset.originalValue = value;
            }
        });

        // Mise à jour des notifications
        Object.entries(this.currentUser.notifications).forEach(([key, value]) => {
            const toggle = document.querySelector(`[data-notification-toggle="${key}"]`);
            if (toggle) {
                toggle.checked = value;
            }
        });

        // Mise à jour de la confidentialité
        Object.entries(this.currentUser.privacy).forEach(([key, value]) => {
            const toggle = document.querySelector(`[data-privacy-toggle="${key}"]`);
            if (toggle) {
                toggle.checked = value;
            }
        });
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.userManager = new UserManager();
}); 