// Gestion des événements sportifs
class EventManager {
    constructor() {
        this.events = new Map();
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Gestion des formulaires d'événement
        const eventForms = document.querySelectorAll('form[data-event-form]');
        eventForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleEventFormSubmit(e));
        });

        // Gestion des boutons de participation
        const joinButtons = document.querySelectorAll('[data-join-event]');
        joinButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleJoinEvent(e));
        });

        // Gestion des boutons de suppression
        const deleteButtons = document.querySelectorAll('[data-delete-event]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleDeleteEvent(e));
        });

        // Gestion des filtres
        const filterForm = document.querySelector('form[data-event-filter]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => this.handleFilterSubmit(e));
            filterForm.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('change', () => filterForm.requestSubmit());
            });
        }

        // Gestion de la recherche
        const searchInput = document.querySelector('[data-event-search]');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => this.handleSearch(e), 300));
        }

        // Gestion de la pagination
        const paginationLinks = document.querySelectorAll('[data-event-page]');
        paginationLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handlePagination(e));
        });

        // Handle event card clicks
        const eventCards = document.querySelectorAll('.event-card');
        eventCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't redirect if clicking on a button or link
                if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
                    return;
                }
                
                const eventId = this.dataset.eventId;
                window.location.href = `/event.php?id=${eventId}`;
            });
        });

        // Handle filter form submission
        const filterForm = document.querySelector('.filter-form');
        if (filterForm) {
            const searchInput = filterForm.querySelector('input[name="search"]');
            const sportSelect = filterForm.querySelector('select[name="sport"]');
            
            // Add debounce to search input
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    filterForm.submit();
                }, 500);
            });

            // Submit form when sport selection changes
            sportSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        // Add hover effect to event cards
        eventCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('hover');
            });
            
            card.addEventListener('mouseleave', function() {
                this.classList.remove('hover');
            });
        });

        // Handle mobile menu
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navMenu = document.querySelector('.nav-menu');
        
        if (mobileMenuBtn && navMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                this.classList.toggle('active');
            });
        }

        // Add smooth scrolling to pagination links
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                
                // Scroll to top smoothly
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                
                // Navigate to the new page after a short delay
                setTimeout(() => {
                    window.location.href = href;
                }, 500);
            });
        });
    }

    async handleEventFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn.innerHTML;

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';

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
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else if (data.event) {
                    this.updateEventCard(data.event);
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

    async handleJoinEvent(e) {
        e.preventDefault();
        const button = e.target.closest('[data-join-event]');
        const eventId = button.dataset.joinEvent;
        const action = button.dataset.action || 'join';

        try {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const response = await fetch(`/api/events/${eventId}/participants`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action })
            });

            const data = await response.json();

            if (data.success) {
                showFlashMessage(data.message, 'success');
                this.updateEventParticipants(eventId, data.participants);
                this.updateJoinButton(button, action === 'join' ? 'leave' : 'join');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    async handleDeleteEvent(e) {
        e.preventDefault();
        const button = e.target.closest('[data-delete-event]');
        const eventId = button.dataset.deleteEvent;

        if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
            return;
        }

        try {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const response = await fetch(`/api/events/${eventId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                showFlashMessage(data.message, 'success');
                this.removeEventCard(eventId);
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                }
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    async handleFilterSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        try {
            const response = await fetch(`${window.location.pathname}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.text();
            const eventList = document.querySelector('.event-list');
            if (eventList) {
                eventList.innerHTML = data;
                this.initializeEventListeners();
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors du filtrage.', 'error');
        }
    }

    async handleSearch(e) {
        const query = e.target.value.trim();
        if (query.length < 2) return;

        try {
            const response = await fetch(`/api/events/search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateEventList(data.events);
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors de la recherche.', 'error');
        }
    }

    async handlePagination(e) {
        e.preventDefault();
        const link = e.target.closest('[data-event-page]');
        const page = link.dataset.eventPage;

        try {
            const response = await fetch(`${window.location.pathname}?page=${page}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.text();
            const eventList = document.querySelector('.event-list');
            if (eventList) {
                eventList.innerHTML = data;
                this.initializeEventListeners();
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors du chargement de la page.', 'error');
        }
    }

    updateEventCard(event) {
        const card = document.querySelector(`[data-event-id="${event.id}"]`);
        if (card) {
            card.innerHTML = this.generateEventCardHTML(event);
            this.initializeEventListeners();
        }
    }

    removeEventCard(eventId) {
        const card = document.querySelector(`[data-event-id="${eventId}"]`);
        if (card) {
            card.remove();
        }
    }

    updateEventParticipants(eventId, participants) {
        const container = document.querySelector(`[data-event-participants="${eventId}"]`);
        if (container) {
            container.innerHTML = this.generateParticipantsHTML(participants);
        }
    }

    updateJoinButton(button, action) {
        button.dataset.action = action;
        button.innerHTML = action === 'join' ? 'Rejoindre' : 'Quitter';
        button.classList.toggle('btn-primary', action === 'join');
        button.classList.toggle('btn-danger', action === 'leave');
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

    generateEventCardHTML(event) {
        return `
            <div class="event-content">
                <div class="event-header">
                    <i class="fas fa-${event.sport_icon}"></i>
                    <h3 class="event-title">${event.title}</h3>
                </div>
                <div class="event-meta">
                    <span><i class="fas fa-calendar"></i> ${formatDate(event.start_time)}</span>
                    <span><i class="fas fa-map-marker-alt"></i> ${event.location}</span>
                    <span><i class="fas fa-users"></i> ${event.participants_count}/${event.max_participants}</span>
                </div>
                <p>${truncateText(event.description)}</p>
                <div class="event-footer">
                    <span class="event-creator">Par ${event.creator_name}</span>
                    <a href="/event.php?id=${event.id}" class="btn btn-primary">Voir détails</a>
                </div>
            </div>
        `;
    }

    generateParticipantsHTML(participants) {
        return participants.map(participant => `
            <div class="participant-item">
                <img src="${participant.avatar || '/assets/images/default-avatar.png'}" alt="${participant.username}" class="participant-avatar">
                <span class="participant-name">${participant.username}</span>
                <span class="participant-status">${formatParticipantStatus(participant.status)}</span>
            </div>
        `).join('');
    }

    updateEventList(events) {
        const eventList = document.querySelector('.event-list');
        if (eventList) {
            eventList.innerHTML = events.map(event => `
                <div class="event-card" data-event-id="${event.id}">
                    ${this.generateEventCardHTML(event)}
                </div>
            `).join('');
            this.initializeEventListeners();
        }
    }
}

// Fonction utilitaire pour le debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.eventManager = new EventManager();
});

// Event API functions
const EventAPI = {
    list: async (filters = {}) => {
        const params = new URLSearchParams(filters);
        const response = await fetch(`/api/events.php?action=list&${params}`);
        return response.json();
    },
    
    create: async (eventData) => {
        const response = await fetch('/api/events.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(eventData)
        });
        return response.json();
    },
    
    join: async (eventId) => {
        const response = await fetch('/api/events.php?action=join', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId })
        });
        return response.json();
    },
    
    leave: async (eventId) => {
        const response = await fetch('/api/events.php?action=leave', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId })
        });
        return response.json();
    },
    
    delete: async (eventId) => {
        const response = await fetch('/api/events.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId })
        });
        return response.json();
    },
    
    update: async (eventId, eventData) => {
        const response = await fetch('/api/events.php?action=update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId, ...eventData })
        });
        return response.json();
    }
};

// Event UI functions
const EventUI = {
    showError: (message) => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = message;
        
        const container = document.querySelector('.container');
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => alert.remove(), 5000);
    },
    
    showSuccess: (message) => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = message;
        
        const container = document.querySelector('.container');
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => alert.remove(), 5000);
    },
    
    formatDate: (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    createEventCard: (event) => {
        return `
            <div class="event-card">
                <div class="event-header">
                    <h3>${event.title}</h3>
                    <span class="sport-badge">${event.sport_name}</span>
                </div>
                
                <div class="event-details">
                    <p><i class="fas fa-map-marker-alt"></i> ${event.location}</p>
                    <p><i class="fas fa-calendar"></i> ${EventUI.formatDate(event.event_date)}</p>
                    <p><i class="fas fa-user"></i> Organisé par ${event.creator_name}</p>
                    <p><i class="fas fa-users"></i> ${event.participant_count}/${event.max_participants} participants</p>
                </div>
                
                <div class="event-description">
                    ${event.description}
                </div>
                
                <div class="event-actions">
                    <a href="event-details.php?id=${event.id}" class="btn btn-primary">
                        <i class="fas fa-info-circle"></i> Détails
                    </a>
                </div>
            </div>
        `;
    },
    
    updateEventsList: async (filters = {}) => {
        try {
            const response = await EventAPI.list(filters);
            
            if (response.success) {
                const eventsGrid = document.querySelector('.events-grid');
                
                if (response.events.length === 0) {
                    eventsGrid.innerHTML = `
                        <div class="no-events">
                            <i class="fas fa-calendar-times"></i>
                            <p>Aucun événement trouvé</p>
                            <a href="create-event.php" class="btn btn-primary">
                                Créer le premier événement
                            </a>
                        </div>
                    `;
                } else {
                    eventsGrid.innerHTML = response.events.map(event => 
                        EventUI.createEventCard(event)
                    ).join('');
                }
            } else {
                EventUI.showError('Erreur lors de la récupération des événements');
            }
        } catch (error) {
            EventUI.showError('Erreur lors de la récupération des événements');
        }
    }
};

// Event handlers
document.addEventListener('DOMContentLoaded', () => {
    // Filter form handling
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(filterForm);
            const filters = Object.fromEntries(formData.entries());
            
            await EventUI.updateEventsList(filters);
        });
    }
    
    // Event creation form handling
    const createForm = document.querySelector('.create-event-form');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            try {
                const formData = new FormData(createForm);
                const eventData = Object.fromEntries(formData.entries());
                
                const response = await EventAPI.create(eventData);
                
                if (response.success) {
                    window.location.href = `event-details.php?id=${response.event_id}`;
                } else {
                    EventUI.showError(response.message);
                }
            } catch (error) {
                EventUI.showError("Erreur lors de la création de l'événement");
            }
        });
    }
    
    // Event participation handling
    document.addEventListener('click', async (e) => {
        if (e.target.matches('[data-action="join"]')) {
            const eventId = e.target.dataset.eventId;
            
            try {
                const response = await EventAPI.join(eventId);
                
                if (response.success) {
                    location.reload();
                } else {
                    EventUI.showError(response.message);
                }
            } catch (error) {
                EventUI.showError("Erreur lors de la participation à l'événement");
            }
        }
        
        if (e.target.matches('[data-action="leave"]')) {
            if (!confirm('Êtes-vous sûr de vouloir quitter cet événement ?')) return;
            
            const eventId = e.target.dataset.eventId;
            
            try {
                const response = await EventAPI.leave(eventId);
                
                if (response.success) {
                    location.reload();
                } else {
                    EventUI.showError(response.message);
                }
            } catch (error) {
                EventUI.showError("Erreur lors du départ de l'événement");
            }
        }
        
        if (e.target.matches('[data-action="delete"]')) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) return;
            
            const eventId = e.target.dataset.eventId;
            
            try {
                const response = await EventAPI.delete(eventId);
                
                if (response.success) {
                    window.location.href = 'events.php';
                } else {
                    EventUI.showError(response.message);
                }
            } catch (error) {
                EventUI.showError("Erreur lors de la suppression de l'événement");
            }
        }
    });
    
    // Initial events load
    EventUI.updateEventsList();
}); 