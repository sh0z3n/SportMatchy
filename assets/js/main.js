// Gestion des événements DOM
document.addEventListener('DOMContentLoaded', () => {
    initializeNavigation();
    initializeForms();
    initializeEventListeners();
    initializeWebSocket();
});

// Initialisation de la navigation
function initializeNavigation() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            mobileMenuBtn.classList.toggle('active');
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', (event) => {
        if (navMenu && navMenu.classList.contains('active')) {
            if (!event.target.closest('.nav-menu') && !event.target.closest('.mobile-menu-btn')) {
                navMenu.classList.remove('active');
                mobileMenuBtn.classList.remove('active');
            }
        }
    });
}

// Initialisation des formulaires
function initializeForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', (event) => {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
            }
        });
    });
}

// Initialisation des écouteurs d'événements
function initializeEventListeners() {
    // Gestion des événements sportifs
    const eventCards = document.querySelectorAll('.event-card');
    eventCards.forEach(card => {
        card.addEventListener('click', () => {
            const eventId = card.dataset.eventId;
            if (eventId) {
                window.location.href = `/event.php?id=${eventId}`;
            }
        });
    });

    // Gestion des filtres
    const filterInputs = document.querySelectorAll('.filter-input');
    filterInputs.forEach(input => {
        input.addEventListener('change', handleFilterChange);
    });

    // Gestion des notifications
    const notificationCloseButtons = document.querySelectorAll('.notification-close');
    notificationCloseButtons.forEach(button => {
        button.addEventListener('click', () => {
            button.closest('.notification').remove();
        });
    });

    // Password visibility toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const passwordInput = this.previousElementSibling;
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Alert auto-dismiss
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (event) => {
            event.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Active link highlighting
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });

    // Form input focus effects
    const formInputs = document.querySelectorAll('.form-group input, .form-group textarea, .form-group select');
    formInputs.forEach(input => {
        input.addEventListener('focus', () => {
            this.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });

    // Initialize any tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', () => {
            const tooltipText = tooltip.getAttribute('data-tooltip');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'tooltip';
            tooltipEl.textContent = tooltipText;
            document.body.appendChild(tooltipEl);

            const rect = tooltip.getBoundingClientRect();
            tooltipEl.style.top = rect.top - tooltipEl.offsetHeight - 10 + 'px';
            tooltipEl.style.left = rect.left + (rect.width - tooltipEl.offsetWidth) / 2 + 'px';
        });

        tooltip.addEventListener('mouseleave', () => {
            const tooltipEl = document.querySelector('.tooltip');
            if (tooltipEl) {
                tooltipEl.remove();
            }
        });
    });
}

// Gestion des filtres
function handleFilterChange() {
    const filters = {};
    document.querySelectorAll('.filter-input').forEach(input => {
        if (input.value) {
            filters[input.name] = input.value;
        }
    });

    // Mettre à jour l'URL avec les filtres
    const searchParams = new URLSearchParams(filters);
    window.history.pushState({}, '', `${window.location.pathname}?${searchParams.toString()}`);

    // Recharger les événements
    loadEvents(filters);
}

// Chargement des événements
async function loadEvents(filters = {}) {
    try {
        const searchParams = new URLSearchParams(filters);
        const response = await fetch(`/api/events.php?${searchParams.toString()}`);
        const data = await response.json();

        if (data.success) {
            updateEventList(data.events);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des événements:', error);
        showNotification('Erreur lors du chargement des événements', 'error');
    }
}

// Mise à jour de la liste des événements
function updateEventList(events) {
    const eventList = document.querySelector('.event-list');
    if (!eventList) return;

    eventList.innerHTML = events.map(event => `
        <div class="event-card" data-event-id="${event.id}">
            <img src="${event.image || '/assets/images/default-event.jpg'}" alt="${event.title}" class="event-image">
            <div class="event-content">
                <h3 class="event-title">${event.title}</h3>
                <div class="event-meta">
                    <span><i class="fas fa-calendar"></i> ${formatDate(event.start_time)}</span>
                    <span><i class="fas fa-users"></i> ${event.participants_count}/${event.max_participants}</span>
                </div>
                <p>${truncateText(event.description)}</p>
            </div>
        </div>
    `).join('');
}

// Initialisation WebSocket
function initializeWebSocket() {
    const ws = new WebSocket(`ws://${window.location.hostname}:8080`);

    ws.onopen = () => {
        console.log('Connexion WebSocket établie');
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        handleWebSocketMessage(data);
    };

    ws.onclose = () => {
        console.log('Connexion WebSocket fermée');
        // Tentative de reconnexion après 5 secondes
        setTimeout(initializeWebSocket, 5000);
    };
}

// Gestion des messages WebSocket
function handleWebSocketMessage(data) {
    switch (data.type) {
        case 'new_event':
            showNotification(`Nouvel événement : ${data.event.title}`, 'info');
            if (window.location.pathname === '/events.php') {
                loadEvents();
            }
            break;
        case 'event_update':
            if (window.location.pathname === '/events.php') {
                loadEvents();
            }
            break;
        case 'new_participant':
            showNotification(`${data.user} a rejoint l'événement ${data.event}`, 'info');
            break;
        default:
            console.log('Message WebSocket non géré:', data);
    }
}

// Affichage des notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <p>${message}</p>
            <button class="notification-close">&times;</button>
        </div>
    `;

    document.body.appendChild(notification);

    // Animation d'entrée
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    // Suppression automatique après 5 secondes
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);

    // Gestion du bouton de fermeture
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
}

// Formatage des dates
function formatDate(dateString) {
    const options = { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Troncature de texte
function truncateText(text, maxLength = 100) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

// Gestion des messages flash
const flashMessages = document.querySelectorAll('.flash-message');
flashMessages.forEach(message => {
    setTimeout(() => {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
    }, 3000);
});

// Fonction pour afficher les messages flash
function showFlashMessage(message, type = 'info') {
    const flashContainer = document.querySelector('.flash-container') || createFlashContainer();
    const flashMessage = document.createElement('div');
    flashMessage.className = `flash-message flash-${type}`;
    flashMessage.innerHTML = `
        <i class="fas fa-${getIconForType(type)}"></i>
        <span>${message}</span>
    `;
    flashContainer.appendChild(flashMessage);

    // Supprimer le message après 3 secondes
    setTimeout(() => {
        flashMessage.style.opacity = '0';
        setTimeout(() => flashMessage.remove(), 300);
    }, 3000);
}

// Fonction pour créer le conteneur de messages flash
function createFlashContainer() {
    const container = document.createElement('div');
    container.className = 'flash-container';
    document.body.insertBefore(container, document.body.firstChild);
    return container;
}

// Fonction pour obtenir l'icône en fonction du type de message
function getIconForType(type) {
    switch (type) {
        case 'success':
            return 'check-circle';
        case 'error':
            return 'exclamation-circle';
        case 'warning':
            return 'exclamation-triangle';
        default:
            return 'info-circle';
    }
}

// Gestion du lazy loading des images
if ('loading' in HTMLImageElement.prototype) {
    const images = document.querySelectorAll('img[loading="lazy"]');
    images.forEach(img => {
        img.src = img.dataset.src;
    });
} else {
    // Fallback pour les navigateurs qui ne supportent pas le lazy loading
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.3.2/lazysizes.min.js';
    document.body.appendChild(script);
}

// Gestion du thème sombre
const themeToggle = document.querySelector('.theme-toggle');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-theme');
        const isDark = document.body.classList.contains('dark-theme');
        localStorage.setItem('darkTheme', isDark);
    });

    // Vérifier la préférence de thème sauvegardée
    if (localStorage.getItem('darkTheme') === 'true') {
        document.body.classList.add('dark-theme');
    }
}

// Gestion des animations au scroll
const animateOnScroll = () => {
    const elements = document.querySelectorAll('.animate-on-scroll');
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementBottom = element.getBoundingClientRect().bottom;
        const isVisible = (elementTop < window.innerHeight) && (elementBottom >= 0);
        
        if (isVisible) {
            element.classList.add('animated');
        }
    });
};

window.addEventListener('scroll', animateOnScroll);
window.addEventListener('load', animateOnScroll); 