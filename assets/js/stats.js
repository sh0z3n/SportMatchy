class StatsManager {
    constructor() {
        this.charts = new Map();
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Gestion des filtres de statistiques
        const filterForm = document.querySelector('[data-stats-filter-form]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => this.handleFilterSubmit(e));
        }

        // Gestion des changements de période
        const periodSelect = document.querySelector('[data-stats-period]');
        if (periodSelect) {
            periodSelect.addEventListener('change', (e) => this.handlePeriodChange(e));
        }

        // Gestion des exports
        const exportButtons = document.querySelectorAll('[data-stats-export]');
        exportButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleExport(e));
        });

        // Initialisation des graphiques
        this.initializeCharts();
    }

    async handleFilterSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/api/stats/filter', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateStats(data.stats);
                this.updateCharts(data.charts);
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handlePeriodChange(e) {
        const period = e.target.value;

        try {
            const response = await fetch(`/api/stats/period/${period}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateStats(data.stats);
                this.updateCharts(data.charts);
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleExport(e) {
        const format = e.target.dataset.statsExport;
        const period = document.querySelector('[data-stats-period]').value;

        try {
            const response = await fetch(`/api/stats/export/${format}?period=${period}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `stats-${period}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            } else {
                const data = await response.json();
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors de l\'export.', 'error');
        }
    }

    initializeCharts() {
        // Graphique des événements
        const eventsChart = document.querySelector('[data-chart="events"]');
        if (eventsChart) {
            this.charts.set('events', new Chart(eventsChart, {
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
            }));
        }

        // Graphique des participants
        const participantsChart = document.querySelector('[data-chart="participants"]');
        if (participantsChart) {
            this.charts.set('participants', new Chart(participantsChart, {
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
            }));
        }

        // Graphique des sports
        const sportsChart = document.querySelector('[data-chart="sports"]');
        if (sportsChart) {
            this.charts.set('sports', new Chart(sportsChart, {
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
            }));
        }

        // Graphique des lieux
        const locationsChart = document.querySelector('[data-chart="locations"]');
        if (locationsChart) {
            this.charts.set('locations', new Chart(locationsChart, {
                type: 'pie',
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
            }));
        }
    }

    updateStats(stats) {
        // Mise à jour des compteurs
        Object.entries(stats.counts).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stats-count="${key}"]`);
            if (element) {
                element.textContent = value;
            }
        });

        // Mise à jour des moyennes
        Object.entries(stats.averages).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stats-average="${key}"]`);
            if (element) {
                element.textContent = value.toFixed(1);
            }
        });

        // Mise à jour des tendances
        Object.entries(stats.trends).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stats-trend="${key}"]`);
            if (element) {
                element.textContent = `${value > 0 ? '+' : ''}${value}%`;
                element.className = `trend ${value > 0 ? 'up' : value < 0 ? 'down' : 'stable'}`;
            }
        });
    }

    updateCharts(chartsData) {
        // Mise à jour du graphique des événements
        const eventsChart = this.charts.get('events');
        if (eventsChart && chartsData.events) {
            eventsChart.data.labels = chartsData.events.labels;
            eventsChart.data.datasets[0].data = chartsData.events.data;
            eventsChart.update();
        }

        // Mise à jour du graphique des participants
        const participantsChart = this.charts.get('participants');
        if (participantsChart && chartsData.participants) {
            participantsChart.data.labels = chartsData.participants.labels;
            participantsChart.data.datasets[0].data = chartsData.participants.data;
            participantsChart.update();
        }

        // Mise à jour du graphique des sports
        const sportsChart = this.charts.get('sports');
        if (sportsChart && chartsData.sports) {
            sportsChart.data.labels = chartsData.sports.labels;
            sportsChart.data.datasets[0].data = chartsData.sports.data;
            sportsChart.update();
        }

        // Mise à jour du graphique des lieux
        const locationsChart = this.charts.get('locations');
        if (locationsChart && chartsData.locations) {
            locationsChart.data.labels = chartsData.locations.labels;
            locationsChart.data.datasets[0].data = chartsData.locations.data;
            locationsChart.update();
        }
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.statsManager = new StatsManager();
}); 