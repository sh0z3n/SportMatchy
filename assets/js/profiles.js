class ProfileManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Gestion des formulaires de profil
        const profileForms = document.querySelectorAll('form[data-profile-form]');
        profileForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleProfileFormSubmit(e));
        });

        // Gestion des avatars
        const avatarInputs = document.querySelectorAll('input[type="file"][data-avatar-input]');
        avatarInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleAvatarChange(e));
        });

        // Gestion des sports favoris
        const sportToggles = document.querySelectorAll('[data-sport-toggle]');
        sportToggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => this.handleSportToggle(e));
        });

        // Gestion des disponibilités
        const availabilityInputs = document.querySelectorAll('[data-availability-input]');
        availabilityInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleAvailabilityChange(e));
        });
    }

    async handleProfileFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn.innerHTML;

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                showFlashMessage(data.message, 'success');
                if (data.profile) {
                    this.updateProfileDisplay(data.profile);
                }
            } else {
                showFlashMessage(data.message, 'error');
                if (data.errors) {
                    this.displayFormErrors(form, data.errors);
                }
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    async handleAvatarChange(e) {
        const input = e.target;
        const file = input.files[0];
        const preview = document.querySelector('[data-avatar-preview]');
        const form = input.closest('form');

        if (!file) return;

        if (!isAllowedImage(file.name)) {
            showFlashMessage('Format d\'image non autorisé. Utilisez JPG, PNG ou GIF.', 'error');
            input.value = '';
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            showFlashMessage('L\'image est trop volumineuse. Maximum 5MB.', 'error');
            input.value = '';
            return;
        }

        // Prévisualisation
        const reader = new FileReader();
        reader.onload = (e) => {
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);

        // Upload automatique
        if (form) {
            const submitBtn = form.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showFlashMessage(data.message, 'success');
                    if (data.avatar_url) {
                        this.updateAvatarDisplay(data.avatar_url);
                    }
                } else {
                    showFlashMessage(data.message, 'error');
                }
            } catch (error) {
                showFlashMessage('Une erreur est survenue lors de l\'upload.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    }

    async handleSportToggle(e) {
        const toggle = e.target;
        const sportId = toggle.dataset.sportId;
        const isFavorite = toggle.checked;

        try {
            const response = await fetch('/api/profile/sports', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    sport_id: sportId,
                    is_favorite: isFavorite
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

    async handleAvailabilityChange(e) {
        const input = e.target;
        const day = input.dataset.availabilityDay;
        const time = input.dataset.availabilityTime;
        const isAvailable = input.checked;

        try {
            const response = await fetch('/api/profile/availability', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    day,
                    time,
                    is_available: isAvailable
                })
            });

            const data = await response.json();

            if (data.success) {
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
                // Restaurer l'état précédent
                input.checked = !input.checked;
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
            // Restaurer l'état précédent
            input.checked = !input.checked;
        }
    }

    updateProfileDisplay(profile) {
        // Mise à jour des informations de base
        const nameElement = document.querySelector('[data-profile-name]');
        if (nameElement) {
            nameElement.textContent = profile.name;
        }

        const bioElement = document.querySelector('[data-profile-bio]');
        if (bioElement) {
            bioElement.textContent = profile.bio;
        }

        // Mise à jour des statistiques
        if (profile.stats) {
            Object.entries(profile.stats).forEach(([key, value]) => {
                const statElement = document.querySelector(`[data-profile-stat="${key}"]`);
                if (statElement) {
                    statElement.textContent = value;
                }
            });
        }

        // Mise à jour des sports favoris
        if (profile.favorite_sports) {
            const sportsContainer = document.querySelector('[data-favorite-sports]');
            if (sportsContainer) {
                sportsContainer.innerHTML = this.generateFavoriteSportsHTML(profile.favorite_sports);
            }
        }

        // Mise à jour des disponibilités
        if (profile.availability) {
            this.updateAvailabilityDisplay(profile.availability);
        }
    }

    updateAvatarDisplay(avatarUrl) {
        const avatarElements = document.querySelectorAll('[data-avatar]');
        avatarElements.forEach(element => {
            element.src = avatarUrl;
        });
    }

    updateAvailabilityDisplay(availability) {
        Object.entries(availability).forEach(([day, times]) => {
            Object.entries(times).forEach(([time, isAvailable]) => {
                const input = document.querySelector(`[data-availability-day="${day}"][data-availability-time="${time}"]`);
                if (input) {
                    input.checked = isAvailable;
                }
            });
        });
    }

    displayFormErrors(form, errors) {
        // Supprimer les erreurs précédentes
        form.querySelectorAll('.is-invalid').forEach(input => {
            input.classList.remove('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.remove();
            }
        });

        // Afficher les nouvelles erreurs
        Object.entries(errors).forEach(([field, message]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                input.parentNode.appendChild(feedback);
            }
        });
    }

    generateFavoriteSportsHTML(sports) {
        return sports.map(sport => `
            <div class="sport-item">
                <img src="${sport.icon_url}" alt="${sport.name}" class="sport-icon">
                <span>${sport.name}</span>
            </div>
        `).join('');
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.profileManager = new ProfileManager();
}); 