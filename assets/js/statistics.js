class StatisticsManager {
    constructor() {
        this.initializeEventListeners();
        this.initializeCharts();
    }

    initializeEventListeners() {
        // Gestion des filtres de statistiques
        const statFilters = document.querySelectorAll('[data-stat-filter]');
        statFilters.forEach(filter => {
            filter.addEventListener('change', (e) => this.handleStatFilter(e));
        });

        // Gestion des périodes
        const periodSelectors = document.querySelectorAll('[data-period-selector]');
        periodSelectors.forEach(selector => {
            selector.addEventListener('change', (e) => this.handlePeriodChange(e));
        });

        // Gestion des exports
        const exportButtons = document.querySelectorAll('[data-export-button]');
        exportButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleExport(e));
        });
    }

    initializeCharts() {
        // Initialisation des graphiques avec Chart.js
        this.charts = {};

        // Graphique des événements
        const eventsChart = document.querySelector('[data-chart="events"]');
        if (eventsChart) {
            this.charts.events = new Chart(eventsChart, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Événements',
                        data: [],
                        borderColor: '#4CAF50',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Graphique des participants
        const participantsChart = document.querySelector('[data-chart="participants"]');
        if (participantsChart) {
            this.charts.participants = new Chart(participantsChart, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Participants',
                        data: [],
                        backgroundColor: '#2196F3'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Graphique des sports
        const sportsChart = document.querySelector('[data-chart="sports"]');
        if (sportsChart) {
            this.charts.sports = new Chart(sportsChart, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#4CAF50',
                            '#2196F3',
                            '#FFC107',
                            '#F44336',
                            '#9C27B0'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Chargement initial des données
        this.loadStatistics();
    }

    async handleStatFilter(e) {
        const filter = e.target;
        const value = filter.value;

        try {
            const response = await fetch(`/api/statistics?filter=${value}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateStatistics(data.statistics);
            }
        } catch (error) {
            console.error('Erreur lors du filtrage des statistiques:', error);
        }
    }

    async handlePeriodChange(e) {
        const selector = e.target;
        const period = selector.value;

        try {
            const response = await fetch(`/api/statistics?period=${period}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateStatistics(data.statistics);
            }
        } catch (error) {
            console.error('Erreur lors du changement de période:', error);
        }
    }

    async handleExport(e) {
        e.preventDefault();
        const button = e.target;
        const format = button.dataset.exportFormat;
        const period = document.querySelector('[data-period-selector]').value;
        const filter = document.querySelector('[data-stat-filter]').value;

        try {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Export...';

            const response = await fetch(`/api/statistics/export?format=${format}&period=${period}&filter=${filter}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `statistics-${format}-${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors de l\'export.', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = 'Exporter';
        }
    }

    async loadStatistics() {
        try {
            const response = await fetch('/api/statistics', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateStatistics(data.statistics);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des statistiques:', error);
        }
    }

    updateStatistics(statistics) {
        // Mise à jour des graphiques
        this.updateEventsChart(statistics.events);
        this.updateParticipantsChart(statistics.participants);
        this.updateSportsChart(statistics.sports);

        // Mise à jour des compteurs
        this.updateCounters(statistics.counters);

        // Mise à jour des tableaux
        this.updateTables(statistics.tables);
    }

    updateEventsChart(data) {
        const chart = this.charts.events;
        if (chart) {
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.update();
        }
    }

    updateParticipantsChart(data) {
        const chart = this.charts.participants;
        if (chart) {
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.update();
        }
    }

    updateSportsChart(data) {
        const chart = this.charts.sports;
        if (chart) {
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.values;
            chart.update();
        }
    }

    updateCounters(counters) {
        Object.entries(counters).forEach(([key, value]) => {
            const element = document.querySelector(`[data-counter="${key}"]`);
            if (element) {
                element.textContent = this.formatNumber(value);
            }
        });
    }

    updateTables(tables) {
        Object.entries(tables).forEach(([key, data]) => {
            const table = document.querySelector(`[data-table="${key}"]`);
            if (table) {
                table.innerHTML = this.generateTableHTML(data);
            }
        });
    }

    generateTableHTML(data) {
        return `
            <thead>
                <tr>
                    ${data.headers.map(header => `<th>${header}</th>`).join('')}
                </tr>
            </thead>
            <tbody>
                ${data.rows.map(row => `
                    <tr>
                        ${row.map(cell => `<td>${cell}</td>`).join('')}
                    </tr>
                `).join('')}
            </tbody>
        `;
    }

    formatNumber(number) {
        return new Intl.NumberFormat('fr-FR').format(number);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.statisticsManager = new StatisticsManager();
}); 