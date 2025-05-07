class FavoritesManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Gestion des boutons de favori
        const favoriteButtons = document.querySelectorAll('[data-favorite-button]');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleFavoriteClick(e));
        });

        // Gestion des listes de favoris
        const favoriteLists = document.querySelectorAll('[data-favorite-list]');
        favoriteLists.forEach(list => {
            list.addEventListener('click', (e) => this.handleFavoriteListClick(e));
        });

        // Gestion du scroll des favoris
        const favoritesContainer = document.querySelector('[data-favorites-container]');
        if (favoritesContainer) {
            favoritesContainer.addEventListener('scroll', (e) => this.handleFavoritesScroll(e));
        }
    }

    async handleFavoriteClick(e) {
        e.preventDefault();
        const button = e.target;
        const itemId = button.dataset.itemId;
        const itemType = button.dataset.itemType;
        const isFavorite = button.classList.contains('active');

        try {
            const response = await fetch('/api/favorites', {
                method: isFavorite ? 'DELETE' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    item_type: itemType
                })
            });

            const data = await response.json();

            if (data.success) {
                this.updateFavoriteButton(button, !isFavorite);
                this.updateFavoriteCount(data.count);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleFavoriteListClick(e) {
        const action = e.target.dataset.favoriteAction;
        if (!action) return;

        const itemId = e.target.closest('[data-favorite-item]').dataset.itemId;
        const itemType = e.target.closest('[data-favorite-item]').dataset.itemType;

        try {
            if (action === 'remove') {
                await this.removeFavorite(itemId, itemType);
            } else if (action === 'share') {
                await this.shareFavorite(itemId, itemType);
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    handleFavoritesScroll(e) {
        const container = e.target;
        const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;

        if (isNearBottom) {
            this.loadMoreFavorites();
        }
    }

    async removeFavorite(itemId, itemType) {
        try {
            const response = await fetch('/api/favorites', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    item_type: itemType
                })
            });

            const data = await response.json();

            if (data.success) {
                this.removeFavoriteItem(itemId);
                this.updateFavoriteCount(data.count);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async shareFavorite(itemId, itemType) {
        try {
            const response = await fetch(`/api/favorites/${itemId}/share`, {
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

    async loadMoreFavorites() {
        if (this.isLoadingFavorites) return;

        const container = document.querySelector('[data-favorites-container]');
        if (!container) return;

        this.isLoadingFavorites = true;

        try {
            const response = await fetch(`/api/favorites?after=${this.lastFavoriteId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success && data.favorites.length > 0) {
                const favoritesHTML = this.generateFavoritesHTML(data.favorites);
                container.insertAdjacentHTML('beforeend', favoritesHTML);
                this.lastFavoriteId = data.favorites[data.favorites.length - 1].id;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des favoris:', error);
        } finally {
            this.isLoadingFavorites = false;
        }
    }

    updateFavoriteButton(button, isFavorite) {
        button.classList.toggle('active', isFavorite);
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = isFavorite ? 'fas fa-heart' : 'far fa-heart';
        }
    }

    updateFavoriteCount(count) {
        const counters = document.querySelectorAll('[data-favorite-count]');
        counters.forEach(counter => {
            counter.textContent = count;
        });
    }

    removeFavoriteItem(itemId) {
        const item = document.querySelector(`[data-favorite-item="${itemId}"]`);
        if (item) {
            item.remove();
        }
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

    generateFavoritesHTML(favorites) {
        return favorites.map(favorite => `
            <div class="favorite-item" data-favorite-item="${favorite.id}" data-item-type="${favorite.type}">
                <div class="favorite-header">
                    <img src="${favorite.image_url}" alt="${favorite.title}" class="favorite-image">
                    <div class="favorite-info">
                        <h4>${favorite.title}</h4>
                        <p>${favorite.description}</p>
                    </div>
                </div>
                <div class="favorite-footer">
                    <a href="${favorite.url}" class="btn btn-primary">Voir plus</a>
                    <div class="favorite-actions">
                        <button class="btn btn-secondary" data-favorite-action="share">
                            <i class="fas fa-share"></i>
                        </button>
                        <button class="btn btn-danger" data-favorite-action="remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
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
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.favoritesManager = new FavoritesManager();
}); 