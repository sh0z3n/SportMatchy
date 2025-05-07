class SportsManager {
    constructor() {
        this.initializeEventListeners();
        this.initializeWebSocket();
    }

    initializeEventListeners() {
        // Gestion des formulaires de sport
        const sportForms = document.querySelectorAll('form[data-sport-form]');
        sportForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleSportFormSubmit(e));
        });

        // Gestion des filtres de sport
        const sportFilters = document.querySelectorAll('[data-sport-filter]');
        sportFilters.forEach(filter => {
            filter.addEventListener('change', (e) => this.handleSportFilter(e));
        });

        // Gestion de la recherche de sports
        const searchInput = document.querySelector('[data-sport-search]');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => this.handleSportSearch(e), 300));
        }

        // Gestion du scroll des sports
        const sportsContainer = document.querySelector('[data-sports-container]');
        if (sportsContainer) {
            sportsContainer.addEventListener('scroll', (e) => this.handleSportsScroll(e));
        }
    }

    initializeWebSocket() {
        if (window.realtimeManager) {
            window.realtimeManager.on('sport_update', (data) => this.handleSportUpdate(data));
            window.realtimeManager.on('sport_event', (data) => this.handleSportEvent(data));
        }
    }

    async handleSportFormSubmit(e) {
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
                if (data.sport) {
                    this.updateSportDisplay(data.sport);
                }
                if (form.dataset.sportForm === 'create') {
                    form.reset();
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

    async handleSportFilter(e) {
        const filter = e.target;
        const value = filter.value;

        try {
            const response = await fetch(`/api/sports?filter=${value}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateSportsList(data.sports);
            }
        } catch (error) {
            console.error('Erreur lors du filtrage des sports:', error);
        }
    }

    async handleSportSearch(e) {
        const input = e.target;
        const query = input.value.trim();

        if (query.length < 2) {
            this.clearSearchResults();
            return;
        }

        try {
            const response = await fetch(`/api/sports/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.displaySearchResults(data.sports);
            }
        } catch (error) {
            console.error('Erreur lors de la recherche des sports:', error);
        }
    }

    handleSportsScroll(e) {
        const container = e.target;
        const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;

        if (isNearBottom) {
            this.loadMoreSports();
        }
    }

    handleSportUpdate(data) {
        const sportElement = document.querySelector(`[data-sport-id="${data.sport.id}"]`);
        if (sportElement) {
            sportElement.innerHTML = this.generateSportHTML(data.sport);
        }
    }

    handleSportEvent(data) {
        const sportElement = document.querySelector(`[data-sport-id="${data.sport_id}"]`);
        if (sportElement) {
            const eventCount = sportElement.querySelector('[data-event-count]');
            if (eventCount) {
                const currentCount = parseInt(eventCount.textContent);
                eventCount.textContent = currentCount + 1;
            }
        }
    }

    async loadSports() {
        const container = document.querySelector('[data-sports-container]');
        if (!container) return;

        try {
            const response = await fetch('/api/sports', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                container.innerHTML = this.generateSportsHTML(data.sports);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des sports:', error);
        }
    }

    async loadMoreSports() {
        if (this.isLoadingSports) return;

        const container = document.querySelector('[data-sports-container]');
        if (!container) return;

        this.isLoadingSports = true;

        try {
            const response = await fetch(`/api/sports?after=${this.lastSportId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success && data.sports.length > 0) {
                const sportsHTML = this.generateSportsHTML(data.sports);
                container.insertAdjacentHTML('beforeend', sportsHTML);
                this.lastSportId = data.sports[data.sports.length - 1].id;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des sports:', error);
        } finally {
            this.isLoadingSports = false;
        }
    }

    updateSportDisplay(sport) {
        const sportElement = document.querySelector(`[data-sport-id="${sport.id}"]`);
        if (sportElement) {
            sportElement.innerHTML = this.generateSportHTML(sport);
        } else {
            const container = document.querySelector('[data-sports-container]');
            if (container) {
                const sportElement = document.createElement('div');
                sportElement.innerHTML = this.generateSportHTML(sport);
                container.insertBefore(sportElement.firstElementChild, container.firstChild);
            }
        }
    }

    updateSportsList(sports) {
        const container = document.querySelector('[data-sports-container]');
        if (container) {
            container.innerHTML = this.generateSportsHTML(sports);
        }
    }

    displaySearchResults(sports) {
        const resultsContainer = document.querySelector('[data-search-results]');
        if (resultsContainer) {
            if (sports.length === 0) {
                resultsContainer.innerHTML = '<div class="no-results">Aucun sport trouvé</div>';
            } else {
                resultsContainer.innerHTML = sports.map(sport => `
                    <a href="/sports/${sport.id}" class="sport-item">
                        <img src="${sport.icon_url}" alt="${sport.name}" class="sport-icon">
                        <div class="sport-info">
                            <h4>${sport.name}</h4>
                            <p>${sport.description}</p>
                        </div>
                    </a>
                `).join('');
            }
        }
    }

    clearSearchResults() {
        const resultsContainer = document.querySelector('[data-search-results]');
        if (resultsContainer) {
            resultsContainer.innerHTML = '';
        }
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

    generateSportsHTML(sports) {
        if (sports.length === 0) {
            return '<div class="no-sports">Aucun sport disponible</div>';
        }

        return sports.map(sport => this.generateSportHTML(sport)).join('');
    }

    generateSportHTML(sport) {
        return `
            <div class="sport-card" data-sport-id="${sport.id}">
                <div class="sport-header">
                    <img src="${sport.icon_url}" alt="${sport.name}" class="sport-icon">
                    <h3>${sport.name}</h3>
                </div>
                <div class="sport-body">
                    <p>${sport.description}</p>
                    <div class="sport-stats">
                        <span class="stat">
                            <i class="fas fa-calendar"></i>
                            <span data-event-count>${sport.event_count}</span> événements
                        </span>
                        <span class="stat">
                            <i class="fas fa-users"></i>
                            <span data-participant-count>${sport.participant_count}</span> participants
                        </span>
                    </div>
                </div>
                <div class="sport-footer">
                    <a href="/sports/${sport.id}" class="btn btn-primary">Voir les événements</a>
                    ${this.generateSportActionsHTML(sport)}
                </div>
            </div>
        `;
    }

    generateSportActionsHTML(sport) {
        const actions = [];

        if (sport.can_edit) {
            actions.push(`
                <button class="btn btn-secondary" data-sport-action="edit" data-sport-id="${sport.id}">
                    <i class="fas fa-edit"></i>
                </button>
            `);
        }

        if (sport.can_delete) {
            actions.push(`
                <button class="btn btn-danger" data-sport-action="delete" data-sport-id="${sport.id}">
                    <i class="fas fa-trash"></i>
                </button>
            `);
        }

        return actions.join('');
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.sportsManager = new SportsManager();
}); 