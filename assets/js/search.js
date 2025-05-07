class SearchManager {
    constructor() {
        this.initializeEventListeners();
        this.initializeSearchHistory();
    }

    initializeEventListeners() {
        // Gestion des formulaires de recherche
        const searchForms = document.querySelectorAll('form[data-search-form]');
        searchForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleSearchFormSubmit(e));
        });

        // Gestion des filtres
        const filters = document.querySelectorAll('[data-search-filter]');
        filters.forEach(filter => {
            filter.addEventListener('change', (e) => this.handleFilterChange(e));
        });

        // Gestion de la recherche en temps réel
        const searchInputs = document.querySelectorAll('[data-search-input]');
        searchInputs.forEach(input => {
            input.addEventListener('input', debounce((e) => this.handleSearchInput(e), 300));
        });

        // Gestion du scroll des résultats
        const resultsContainer = document.querySelector('[data-search-results]');
        if (resultsContainer) {
            resultsContainer.addEventListener('scroll', (e) => this.handleResultsScroll(e));
        }
    }

    initializeSearchHistory() {
        this.searchHistory = JSON.parse(localStorage.getItem('searchHistory') || '[]');
        this.updateSearchHistory();
    }

    async handleSearchFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const searchInput = form.querySelector('[data-search-input]');
        const query = searchInput.value.trim();

        if (!query) return;

        this.addToSearchHistory(query);
        await this.performSearch(query);
    }

    async handleSearchInput(e) {
        const input = e.target;
        const query = input.value.trim();

        if (query.length < 2) {
            this.clearSearchResults();
            return;
        }

        await this.performSearch(query);
    }

    async handleFilterChange(e) {
        const filter = e.target;
        const value = filter.value;
        const currentQuery = document.querySelector('[data-search-input]').value.trim();

        if (currentQuery) {
            await this.performSearch(currentQuery, { [filter.name]: value });
        }
    }

    handleResultsScroll(e) {
        const container = e.target;
        const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;

        if (isNearBottom) {
            this.loadMoreResults();
        }
    }

    async performSearch(query, filters = {}) {
        try {
            const params = new URLSearchParams({
                q: query,
                ...filters
            });

            const response = await fetch(`/api/search?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.displaySearchResults(data.results);
            }
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
        }
    }

    async loadMoreResults() {
        if (this.isLoadingResults) return;

        const container = document.querySelector('[data-search-results]');
        if (!container) return;

        this.isLoadingResults = true;

        try {
            const currentQuery = document.querySelector('[data-search-input]').value.trim();
            const filters = this.getCurrentFilters();

            const params = new URLSearchParams({
                q: currentQuery,
                ...filters,
                after: this.lastResultId
            });

            const response = await fetch(`/api/search?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success && data.results.length > 0) {
                const resultsHTML = this.generateResultsHTML(data.results);
                container.insertAdjacentHTML('beforeend', resultsHTML);
                this.lastResultId = data.results[data.results.length - 1].id;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des résultats:', error);
        } finally {
            this.isLoadingResults = false;
        }
    }

    displaySearchResults(results) {
        const container = document.querySelector('[data-search-results]');
        if (container) {
            if (results.length === 0) {
                container.innerHTML = '<div class="no-results">Aucun résultat trouvé</div>';
            } else {
                container.innerHTML = this.generateResultsHTML(results);
            }
        }
    }

    clearSearchResults() {
        const container = document.querySelector('[data-search-results]');
        if (container) {
            container.innerHTML = '';
        }
    }

    addToSearchHistory(query) {
        // Supprimer les doublons
        this.searchHistory = this.searchHistory.filter(item => item !== query);
        // Ajouter au début
        this.searchHistory.unshift(query);
        // Limiter à 10 éléments
        this.searchHistory = this.searchHistory.slice(0, 10);
        // Sauvegarder
        localStorage.setItem('searchHistory', JSON.stringify(this.searchHistory));
        // Mettre à jour l'affichage
        this.updateSearchHistory();
    }

    updateSearchHistory() {
        const container = document.querySelector('[data-search-history]');
        if (container) {
            if (this.searchHistory.length === 0) {
                container.innerHTML = '<div class="no-history">Aucune recherche récente</div>';
            } else {
                container.innerHTML = this.generateHistoryHTML();
            }
        }
    }

    getCurrentFilters() {
        const filters = {};
        document.querySelectorAll('[data-search-filter]').forEach(filter => {
            if (filter.value) {
                filters[filter.name] = filter.value;
            }
        });
        return filters;
    }

    generateResultsHTML(results) {
        return results.map(result => `
            <div class="search-result" data-result-id="${result.id}">
                <div class="result-header">
                    <img src="${result.image_url}" alt="${result.title}" class="result-image">
                    <div class="result-info">
                        <h4>${result.title}</h4>
                        <p>${result.description}</p>
                    </div>
                </div>
                <div class="result-footer">
                    <a href="${result.url}" class="btn btn-primary">Voir plus</a>
                    ${this.generateResultActionsHTML(result)}
                </div>
            </div>
        `).join('');
    }

    generateHistoryHTML() {
        return `
            <div class="search-history-list">
                ${this.searchHistory.map(query => `
                    <div class="history-item">
                        <a href="#" class="history-link" data-history-query="${query}">
                            <i class="fas fa-history"></i>
                            ${query}
                        </a>
                        <button class="btn btn-link" data-history-delete="${query}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
    }

    generateResultActionsHTML(result) {
        const actions = [];

        if (result.can_save) {
            actions.push(`
                <button class="btn btn-secondary" data-result-action="save" data-result-id="${result.id}">
                    <i class="fas fa-bookmark"></i>
                </button>
            `);
        }

        if (result.can_share) {
            actions.push(`
                <button class="btn btn-secondary" data-result-action="share" data-result-id="${result.id}">
                    <i class="fas fa-share"></i>
                </button>
            `);
        }

        return actions.join('');
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.searchManager = new SearchManager();
}); 