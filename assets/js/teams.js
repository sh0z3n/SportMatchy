class TeamManager {
    constructor() {
        this.teams = [];
        this.currentTeam = null;
        this.initializeEventListeners();
        this.loadTeams();
    }

    initializeEventListeners() {
        // Gestion du formulaire d'équipe
        const teamForm = document.querySelector('[data-team-form]');
        if (teamForm) {
            teamForm.addEventListener('submit', (e) => this.handleTeamSubmit(e));
        }

        // Gestion de la recherche d'équipe
        const searchInput = document.querySelector('[data-team-search]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleTeamSearch(e));
        }

        // Gestion des filtres d'équipe
        const filterButtons = document.querySelectorAll('[data-team-filter]');
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleTeamFilter(e));
        });

        // Gestion des actions d'équipe
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-team-action]')) {
                this.handleTeamAction(e);
            }
        });

        // Gestion des suppressions d'équipe
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-team-delete]')) {
                this.handleTeamDelete(e);
            }
        });

        // Gestion des partages d'équipe
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-team-share]')) {
                this.handleTeamShare(e);
            }
        });

        // Gestion des invitations d'équipe
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-team-invite]')) {
                this.handleTeamInvite(e);
            }
        });

        // Gestion des commentaires d'équipe
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-team-comment]')) {
                this.handleTeamComment(e);
            }
        });

        // Gestion du défilement pour charger plus d'équipes
        const teamList = document.querySelector('[data-team-list]');
        if (teamList) {
            teamList.addEventListener('scroll', (e) => this.handleTeamScroll(e));
        }
    }

    async loadTeams() {
        try {
            const response = await fetch('/api/teams', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.teams = data.teams;
                this.updateTeamUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des équipes:', error);
        }
    }

    async handleTeamSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/api/teams', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.teams.unshift(data.team);
                this.updateTeamUI();
                form.reset();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    handleTeamSearch(e) {
        const query = e.target.value.toLowerCase();
        const filteredTeams = this.teams.filter(team => 
            team.name.toLowerCase().includes(query) ||
            team.description.toLowerCase().includes(query) ||
            team.sport.toLowerCase().includes(query)
        );
        this.updateTeamList(filteredTeams);
    }

    async handleTeamFilter(e) {
        e.preventDefault();
        const filter = e.target.dataset.teamFilter;

        try {
            const response = await fetch(`/api/teams/filter/${filter}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.teams = data.teams;
                this.updateTeamUI();
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleTeamAction(e) {
        e.preventDefault();
        const action = e.target.dataset.teamAction;
        const teamId = e.target.closest('[data-team-item]').dataset.teamId;

        try {
            const response = await fetch(`/api/teams/${teamId}/${action}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateTeam(teamId, data.team);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleTeamDelete(e) {
        e.preventDefault();
        const teamId = e.target.closest('[data-team-item]').dataset.teamId;

        if (!confirm('Êtes-vous sûr de vouloir supprimer cette équipe ?')) {
            return;
        }

        try {
            const response = await fetch(`/api/teams/${teamId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.removeTeam(teamId);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleTeamShare(e) {
        e.preventDefault();
        const teamId = e.target.closest('[data-team-item]').dataset.teamId;

        try {
            const response = await fetch(`/api/teams/${teamId}/share`, {
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

    async handleTeamInvite(e) {
        e.preventDefault();
        const teamId = e.target.closest('[data-team-item]').dataset.teamId;
        const emailInput = e.target.closest('[data-team-item]').querySelector('[data-invite-email]');
        const email = emailInput.value.trim();

        if (!email) return;

        try {
            const response = await fetch(`/api/teams/${teamId}/invite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    email: email
                })
            });

            const data = await response.json();

            if (data.success) {
                emailInput.value = '';
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleTeamComment(e) {
        e.preventDefault();
        const teamId = e.target.closest('[data-team-item]').dataset.teamId;
        const commentInput = e.target.closest('[data-team-item]').querySelector('[data-comment-input]');
        const comment = commentInput.value.trim();

        if (!comment) return;

        try {
            const response = await fetch(`/api/teams/${teamId}/comment`, {
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
                this.updateTeam(teamId, data.team);
                commentInput.value = '';
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleTeamScroll(e) {
        const list = e.target;
        const scrollPosition = list.scrollTop + list.clientHeight;
        const scrollHeight = list.scrollHeight;

        if (scrollHeight - scrollPosition < 100) {
            await this.loadMoreTeams();
        }
    }

    async loadMoreTeams() {
        if (this.isLoading) return;
        this.isLoading = true;

        try {
            const lastTeam = this.teams[this.teams.length - 1];
            const response = await fetch(`/api/teams?after=${lastTeam.id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success && data.teams.length > 0) {
                this.teams.push(...data.teams);
                this.updateTeamUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des équipes supplémentaires:', error);
        } finally {
            this.isLoading = false;
        }
    }

    updateTeamUI() {
        this.updateTeamList();
    }

    updateTeamList(teams = this.teams) {
        const list = document.querySelector('[data-team-list]');
        if (list) {
            list.innerHTML = teams.map(team => this.generateTeamHTML(team)).join('');
        }
    }

    updateTeam(teamId, updatedTeam) {
        const index = this.teams.findIndex(t => t.id === teamId);
        if (index !== -1) {
            this.teams[index] = updatedTeam;
            this.updateTeamUI();
        }
    }

    removeTeam(teamId) {
        this.teams = this.teams.filter(t => t.id !== teamId);
        this.updateTeamUI();
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

    generateTeamHTML(team) {
        return `
            <div class="team-item" data-team-item="${team.id}">
                <div class="team-header">
                    <div class="team-info">
                        <h3>${team.name}</h3>
                        <div class="team-meta">
                            <span><i class="fas fa-users"></i> ${team.members_count} membres</span>
                            <span><i class="fas fa-trophy"></i> ${team.wins} victoires</span>
                            <span><i class="fas fa-star"></i> ${team.rating}</span>
                        </div>
                    </div>
                    <div class="team-actions">
                        <button class="btn btn-icon" data-team-invite title="Inviter">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <button class="btn btn-icon" data-team-share title="Partager">
                            <i class="fas fa-share"></i>
                        </button>
                        ${team.can_delete ? `
                            <button class="btn btn-icon" data-team-delete title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="team-content">
                    <div class="team-image">
                        <img src="${team.image}" alt="${team.name}">
                    </div>
                    <div class="team-details">
                        <p>${team.description}</p>
                        <div class="team-members">
                            <h4>Membres</h4>
                            <div class="members-list">
                                ${this.generateMembersHTML(team.members)}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="team-footer">
                    <div class="team-comments">
                        ${this.generateCommentsHTML(team.comments)}
                        <div class="comment-form">
                            <input type="text" data-comment-input placeholder="Ajouter un commentaire...">
                            <button class="btn btn-primary" data-team-comment>Envoyer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    generateMembersHTML(members) {
        if (!members || members.length === 0) return '';

        return `
            <div class="members-grid">
                ${members.map(member => `
                    <div class="member-item">
                        <img src="${member.avatar}" alt="${member.name}" class="member-avatar">
                        <div class="member-info">
                            <h5>${member.name}</h5>
                            <span class="member-role">${member.role}</span>
                        </div>
                    </div>
                `).join('')}
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
                                <span class="comment-time">${this.formatTeamTime(comment.created_at)}</span>
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

    formatTeamTime(timestamp) {
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
    window.teamManager = new TeamManager();
}); 