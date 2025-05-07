class SettingsManager {
    constructor() {
        this.settings = {};
        this.initializeEventListeners();
        this.loadSettings();
    }

    initializeEventListeners() {
        // Gestion des formulaires de paramètres
        const settingsForms = document.querySelectorAll('[data-settings-form]');
        settingsForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleSettingsSubmit(e));
        });

        // Gestion des toggles
        const toggles = document.querySelectorAll('[data-settings-toggle]');
        toggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => this.handleToggleChange(e));
        });

        // Gestion des sélecteurs
        const selectors = document.querySelectorAll('[data-settings-select]');
        selectors.forEach(selector => {
            selector.addEventListener('change', (e) => this.handleSelectorChange(e));
        });

        // Gestion des inputs de couleur
        const colorInputs = document.querySelectorAll('[data-settings-color]');
        colorInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleColorChange(e));
        });

        // Gestion des inputs numériques
        const numberInputs = document.querySelectorAll('[data-settings-number]');
        numberInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleNumberChange(e));
        });

        // Gestion des inputs texte
        const textInputs = document.querySelectorAll('[data-settings-text]');
        textInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleTextChange(e));
        });

        // Gestion des inputs fichier
        const fileInputs = document.querySelectorAll('[data-settings-file]');
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleFileChange(e));
        });
    }

    async loadSettings() {
        try {
            const response = await fetch('/api/settings', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.settings = data.settings;
                this.updateSettingsUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des paramètres:', error);
        }
    }

    async handleSettingsSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.settings = data.settings;
                this.updateSettingsUI();
                showFlashMessage('Paramètres mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleToggleChange(e) {
        const toggle = e.target;
        const setting = toggle.dataset.settingsToggle;
        const value = toggle.checked;

        try {
            const response = await fetch('/api/settings/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    setting,
                    value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.settings[setting] = value;
                this.updateSettingsUI();
                showFlashMessage('Paramètre mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleSelectorChange(e) {
        const selector = e.target;
        const setting = selector.dataset.settingsSelect;
        const value = selector.value;

        try {
            const response = await fetch('/api/settings/select', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    setting,
                    value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.settings[setting] = value;
                this.updateSettingsUI();
                showFlashMessage('Paramètre mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleColorChange(e) {
        const input = e.target;
        const setting = input.dataset.settingsColor;
        const value = input.value;

        try {
            const response = await fetch('/api/settings/color', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    setting,
                    value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.settings[setting] = value;
                this.updateSettingsUI();
                showFlashMessage('Paramètre mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleNumberChange(e) {
        const input = e.target;
        const setting = input.dataset.settingsNumber;
        const value = input.value;

        try {
            const response = await fetch('/api/settings/number', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    setting,
                    value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.settings[setting] = value;
                this.updateSettingsUI();
                showFlashMessage('Paramètre mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleTextChange(e) {
        const input = e.target;
        const setting = input.dataset.settingsText;
        const value = input.value;

        try {
            const response = await fetch('/api/settings/text', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    setting,
                    value
                })
            });

            const data = await response.json();

            if (data.success) {
                this.settings[setting] = value;
                this.updateSettingsUI();
                showFlashMessage('Paramètre mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleFileChange(e) {
        const input = e.target;
        const setting = input.dataset.settingsFile;
        const file = input.files[0];

        if (!file) return;

        const formData = new FormData();
        formData.append('setting', setting);
        formData.append('file', file);

        try {
            const response = await fetch('/api/settings/file', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.settings[setting] = data.value;
                this.updateSettingsUI();
                showFlashMessage('Fichier mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    updateSettingsUI() {
        // Mise à jour des toggles
        const toggles = document.querySelectorAll('[data-settings-toggle]');
        toggles.forEach(toggle => {
            const setting = toggle.dataset.settingsToggle;
            toggle.checked = this.settings[setting] || false;
        });

        // Mise à jour des sélecteurs
        const selectors = document.querySelectorAll('[data-settings-select]');
        selectors.forEach(selector => {
            const setting = selector.dataset.settingsSelect;
            selector.value = this.settings[setting] || '';
        });

        // Mise à jour des inputs de couleur
        const colorInputs = document.querySelectorAll('[data-settings-color]');
        colorInputs.forEach(input => {
            const setting = input.dataset.settingsColor;
            input.value = this.settings[setting] || '#000000';
        });

        // Mise à jour des inputs numériques
        const numberInputs = document.querySelectorAll('[data-settings-number]');
        numberInputs.forEach(input => {
            const setting = input.dataset.settingsNumber;
            input.value = this.settings[setting] || 0;
        });

        // Mise à jour des inputs texte
        const textInputs = document.querySelectorAll('[data-settings-text]');
        textInputs.forEach(input => {
            const setting = input.dataset.settingsText;
            input.value = this.settings[setting] || '';
        });

        // Mise à jour des inputs fichier
        const fileInputs = document.querySelectorAll('[data-settings-file]');
        fileInputs.forEach(input => {
            const setting = input.dataset.settingsFile;
            const preview = input.parentElement.querySelector('.file-preview');
            if (preview && this.settings[setting]) {
                preview.src = this.settings[setting];
            }
        });
    }

    generateSettingsHTML() {
        return `
            <div class="settings-container">
                <div class="settings-header">
                    <h2>Paramètres</h2>
                </div>
                <div class="settings-content">
                    <form data-settings-form action="/api/settings" method="POST">
                        <div class="settings-section">
                            <h3>Général</h3>
                            <div class="form-group">
                                <label for="site_name">Nom du site</label>
                                <input type="text" id="site_name" name="site_name" data-settings-text="site_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="site_description">Description du site</label>
                                <textarea id="site_description" name="site_description" data-settings-text="site_description" class="form-control"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="site_logo">Logo du site</label>
                                <input type="file" id="site_logo" name="site_logo" data-settings-file="site_logo" class="form-control">
                                <img class="file-preview" src="" alt="Logo preview">
                            </div>
                        </div>
                        <div class="settings-section">
                            <h3>Apparence</h3>
                            <div class="form-group">
                                <label for="primary_color">Couleur principale</label>
                                <input type="color" id="primary_color" name="primary_color" data-settings-color="primary_color" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="secondary_color">Couleur secondaire</label>
                                <input type="color" id="secondary_color" name="secondary_color" data-settings-color="secondary_color" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="font_family">Police</label>
                                <select id="font_family" name="font_family" data-settings-select="font_family" class="form-control">
                                    <option value="Arial">Arial</option>
                                    <option value="Helvetica">Helvetica</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Courier New">Courier New</option>
                                </select>
                            </div>
                        </div>
                        <div class="settings-section">
                            <h3>Notifications</h3>
                            <div class="form-group">
                                <label class="toggle">
                                    <input type="checkbox" name="email_notifications" data-settings-toggle="email_notifications">
                                    <span class="toggle-label">Notifications par email</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="toggle">
                                    <input type="checkbox" name="push_notifications" data-settings-toggle="push_notifications">
                                    <span class="toggle-label">Notifications push</span>
                                </label>
                            </div>
                        </div>
                        <div class="settings-section">
                            <h3>Avancé</h3>
                            <div class="form-group">
                                <label for="cache_duration">Durée du cache (minutes)</label>
                                <input type="number" id="cache_duration" name="cache_duration" data-settings-number="cache_duration" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="toggle">
                                    <input type="checkbox" name="debug_mode" data-settings-toggle="debug_mode">
                                    <span class="toggle-label">Mode debug</span>
                                </label>
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <button type="reset" class="btn btn-secondary">Réinitialiser</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.settingsManager = new SettingsManager();
}); 