class ActivityManager {
    constructor() {
        this.activities = [];
        this.initializeEventListeners();
        this.loadActivities();
    }

    initializeEventListeners() {
        // Gestion du formulaire d'activité
        const activityForm = document.querySelector('[data-activity-form]');
        if (activityForm) {
            activityForm.addEventListener('submit', (e) => this.handleActivitySubmit(e));
        }

        // Gestion du scroll des activités
        const activityList = document.querySelector('[data-activity-list]');
        if (activityList) {
            activityList.addEventListener('scroll', (e) => this.handleActivityScroll(e));
        }

        // Gestion des filtres d'activité
        const filterButtons = document.querySelectorAll('[data-activity-filter]');
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleActivityFilter(e));
        });

        // Gestion de la recherche d'activité
        const searchInput = document.querySelector('[data-activity-search]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleActivitySearch(e));
        }

        // Gestion des actions d'activité
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-activity-action]')) {
                this.handleActivityAction(e);
            }
        });

        // Gestion des suppressions d'activité
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-activity-delete]')) {
                this.handleActivityDelete(e);
            }
        });

        // Gestion des partages d'activité
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-activity-share]')) {
                this.handleActivityShare(e);
            }
        });

        // Gestion des likes d'activité
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-activity-like]')) {
                this.handleActivityLike(e);
            }
        });

        // Gestion des commentaires d'activité
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-activity-comment]')) {
                this.handleActivityComment(e);
            }
        });
    }

    async loadActivities() {
        try {
            const response = await fetch('/api/activities', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.activities = data.activities;
                this.updateActivityUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des activités:', error);
        }
    }

    async loadMoreActivities() {
        if (this.isLoadingActivities) return;

        this.isLoadingActivities = true;

        try {
            const response = await fetch(`/api/activities?after=${this.lastActivityId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success && data.activities.length > 0) {
                this.activities = [...this.activities, ...data.activities];
                this.lastActivityId = data.activities[data.activities.length - 1].id;
                this.updateActivityList();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des activités:', error);
        } finally {
            this.isLoadingActivities = false;
        }
    }

    async handleActivitySubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/api/activities', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.activities.unshift(data.activity);
                this.updateActivityUI();
                form.reset();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    handleActivityScroll(e) {
        const list = e.target;
        const isNearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 50;

        if (isNearBottom) {
            this.loadMoreActivities();
        }
    }

    async handleActivityFilter(e) {
        e.preventDefault();
        const filter = e.target.dataset.activityFilter;

        try {
            const response = await fetch(`/api/activities/filter/${filter}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.activities = data.activities;
                this.updateActivityList();
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    handleActivitySearch(e) {
        const query = e.target.value.toLowerCase();
        const filteredActivities = this.activities.filter(activity => 
            activity.title.toLowerCase().includes(query) ||
            activity.description.toLowerCase().includes(query)
        );
        this.updateActivityList(filteredActivities);
    }

    async handleActivityAction(e) {
        e.preventDefault();
        const action = e.target.dataset.activityAction;
        const activityId = e.target.closest('[data-activity-item]').dataset.activityId;

        try {
            const response = await fetch(`/api/activities/${activityId}/${action}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateActivity(activityId, data.activity);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleActivityDelete(e) {
        e.preventDefault();
        const activityId = e.target.closest('[data-activity-item]').dataset.activityId;

        if (!confirm('Êtes-vous sûr de vouloir supprimer cette activité ?')) {
            return;
        }

        try {
            const response = await fetch(`/api/activities/${activityId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.removeActivity(activityId);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleActivityShare(e) {
        e.preventDefault();
        const activityId = e.target.closest('[data-activity-item]').dataset.activityId;

        try {
            const response = await fetch(`/api/activities/${activityId}/share`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                if (navigator.share) {
                    await navigator.share({
                        title: data.title,
                        text: data.text,
                        url: data.url
                    });
                } else {
                    this.showShareModal(data);
                }
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                showFlashMessage('Une erreur est survenue lors du partage.', 'error');
            }
        }
    }

    async handleActivityLike(e) {
        e.preventDefault();
        const activityId = e.target.closest('[data-activity-item]').dataset.activityId;

        try {
            const response = await fetch(`/api/activities/${activityId}/like`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateActivity(activityId, data.activity);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleActivityComment(e) {
        e.preventDefault();
        const activityId = e.target.closest('[data-activity-item]').dataset.activityId;
        const commentInput = e.target.closest('[data-activity-item]').querySelector('[data-comment-input]');
        const comment = commentInput.value.trim();

        if (!comment) return;

        try {
            const response = await fetch(`/api/activities/${activityId}/comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    comment: comment
                })
            });

            const data = await response.json();

            if (data.success) {
                this.updateActivity(activityId, data.activity);
                commentInput.value = '';
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    updateActivityUI() {
        this.updateActivityList();
    }

    updateActivityList(activities = this.activities) {
        const list = document.querySelector('[data-activity-list]');
        if (list) {
            list.innerHTML = activities.map(activity => this.generateActivityHTML(activity)).join('');
        }
    }

    updateActivity(activityId, updatedActivity) {
        const index = this.activities.findIndex(a => a.id === activityId);
        if (index !== -1) {
            this.activities[index] = updatedActivity;
            this.updateActivityUI();
        }
    }

    removeActivity(activityId) {
        this.activities = this.activities.filter(a => a.id !== activityId);
        this.updateActivityUI();
    }

    showShareModal(data) {
        const modal = document.createElement('div');
        modal.className = 'share-modal';
        modal.innerHTML = `
            <div class="share-modal-content">
                <div class="share-modal-header">
                    <h3>Partager</h3>
                    <button class="close-button">&times;</button>
                </div>
                <div class="share-modal-body">
                    <div class="share-options">
                        ${this.generateShareOptionsHTML(data)}
                    </div>
                    <div class="share-link">
                        <input type="text" value="${data.url}" readonly>
                        <button class="copy-button">Copier</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Gestion de la fermeture
        modal.querySelector('.close-button').addEventListener('click', () => {
            modal.remove();
        });

        // Gestion de la copie
        modal.querySelector('.copy-button').addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(data.url);
                showFlashMessage('Lien copié dans le presse-papiers !', 'success');
            } catch (error) {
                showFlashMessage('Une erreur est survenue lors de la copie du lien.', 'error');
            }
        });

        // Fermeture en cliquant en dehors
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    generateActivityHTML(activity) {
        return `
            <div class="activity-item" data-activity-item="${activity.id}">
                <div class="activity-header">
                    <div class="activity-user">
                        <img src="${activity.user.avatar}" alt="${activity.user.name}" class="activity-avatar">
                        <div class="activity-user-info">
                            <h4>${activity.user.name}</h4>
                            <span class="activity-time">${this.formatActivityTime(activity.created_at)}</span>
                        </div>
                    </div>
                    <div class="activity-actions">
                        <button class="btn btn-icon" data-activity-share title="Partager">
                            <i class="fas fa-share"></i>
                        </button>
                        ${activity.can_delete ? `
                            <button class="btn btn-icon" data-activity-delete title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="activity-content">
                    <h3>${activity.title}</h3>
                    <p>${activity.description}</p>
                    ${activity.image ? `
                        <div class="activity-image">
                            <img src="${activity.image}" alt="${activity.title}">
                        </div>
                    ` : ''}
                </div>
                <div class="activity-footer">
                    <div class="activity-stats">
                        <button class="btn btn-icon ${activity.liked ? 'active' : ''}" data-activity-like>
                            <i class="fas fa-heart"></i>
                            <span>${activity.likes_count}</span>
                        </button>
                        <button class="btn btn-icon" data-activity-comment>
                            <i class="fas fa-comment"></i>
                            <span>${activity.comments_count}</span>
                        </button>
                    </div>
                    <div class="activity-comments">
                        ${this.generateCommentsHTML(activity.comments)}
                        <div class="comment-form">
                            <input type="text" data-comment-input placeholder="Ajouter un commentaire...">
                            <button class="btn btn-primary" data-activity-comment>Envoyer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    generateCommentsHTML(comments) {
        if (!comments || comments.length === 0) return '';

        return `
            <div class="comments-list">
                ${comments.map(comment => `
                    <div class="comment-item">
                        <img src="${comment.user.avatar}" alt="${comment.user.name}" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-header">
                                <h4>${comment.user.name}</h4>
                                <span class="comment-time">${this.formatActivityTime(comment.created_at)}</span>
                            </div>
                            <p>${comment.content}</p>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    generateShareOptionsHTML(data) {
        const options = [];

        // Facebook
        options.push(`
            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(data.url)}" 
               target="_blank" 
               class="share-option facebook">
                <i class="fab fa-facebook"></i>
                Facebook
            </a>
        `);

        // Twitter
        options.push(`
            <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(data.url)}&text=${encodeURIComponent(data.text)}" 
               target="_blank" 
               class="share-option twitter">
                <i class="fab fa-twitter"></i>
                Twitter
            </a>
        `);

        // LinkedIn
        options.push(`
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(data.url)}&title=${encodeURIComponent(data.title)}" 
               target="_blank" 
               class="share-option linkedin">
                <i class="fab fa-linkedin"></i>
                LinkedIn
            </a>
        `);

        // WhatsApp
        options.push(`
            <a href="https://wa.me/?text=${encodeURIComponent(data.text + ' ' + data.url)}" 
               target="_blank" 
               class="share-option whatsapp">
                <i class="fab fa-whatsapp"></i>
                WhatsApp
            </a>
        `);

        // Email
        options.push(`
            <a href="mailto:?subject=${encodeURIComponent(data.title)}&body=${encodeURIComponent(data.text + '\n\n' + data.url)}" 
               class="share-option email">
                <i class="fas fa-envelope"></i>
                Email
            </a>
        `);

        return options.join('');
    }

    formatActivityTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) { // moins d'une minute
            return 'À l\'instant';
        } else if (diff < 3600000) { // moins d'une heure
            const minutes = Math.floor(diff / 60000);
            return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
        } else if (diff < 86400000) { // moins d'un jour
            const hours = Math.floor(diff / 3600000);
            return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
        } else if (diff < 604800000) { // moins d'une semaine
            const days = Math.floor(diff / 86400000);
            return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
        } else {
            return date.toLocaleDateString();
        }
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.activityManager = new ActivityManager();
}); 