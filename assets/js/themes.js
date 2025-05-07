class ThemeManager {
    constructor() {
        this.theme = {};
        this.initializeEventListeners();
        this.loadTheme();
    }

    initializeEventListeners() {
        // Gestion des toggles de thème
        const themeToggles = document.querySelectorAll('[data-theme-toggle]');
        themeToggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => this.handleThemeToggle(e));
        });

        // Gestion des inputs de couleur
        const colorInputs = document.querySelectorAll('[data-theme-color]');
        colorInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleColorChange(e));
        });

        // Gestion des inputs de style
        const styleInputs = document.querySelectorAll('[data-theme-style]');
        styleInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleStyleChange(e));
        });

        // Gestion des sélecteurs de police
        const fontSelectors = document.querySelectorAll('[data-theme-font]');
        fontSelectors.forEach(selector => {
            selector.addEventListener('change', (e) => this.handleFontChange(e));
        });

        // Gestion des inputs de taille
        const sizeInputs = document.querySelectorAll('[data-theme-size]');
        sizeInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleSizeChange(e));
        });

        // Gestion des inputs d'espacement
        const spacingInputs = document.querySelectorAll('[data-theme-spacing]');
        spacingInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleSpacingChange(e));
        });

        // Gestion des inputs de bordure
        const borderInputs = document.querySelectorAll('[data-theme-border]');
        borderInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleBorderChange(e));
        });

        // Gestion des inputs d'ombre
        const shadowInputs = document.querySelectorAll('[data-theme-shadow]');
        shadowInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleShadowChange(e));
        });

        // Gestion des inputs d'animation
        const animationInputs = document.querySelectorAll('[data-theme-animation]');
        animationInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleAnimationChange(e));
        });
    }

    async loadTheme() {
        try {
            const response = await fetch('/api/theme', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.theme = data.theme;
                this.applyTheme();
            }
        } catch (error) {
            console.error('Erreur lors du chargement du thème:', error);
        }
    }

    async handleThemeToggle(e) {
        const toggle = e.target;
        const setting = toggle.dataset.themeToggle;
        const value = toggle.checked;

        try {
            const response = await fetch('/api/theme/toggle', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Thème mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleColorChange(e) {
        const input = e.target;
        const setting = input.dataset.themeColor;
        const value = input.value;

        try {
            const response = await fetch('/api/theme/color', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Couleur mise à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleStyleChange(e) {
        const input = e.target;
        const setting = input.dataset.themeStyle;
        const value = input.value;

        try {
            const response = await fetch('/api/theme/style', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Style mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleFontChange(e) {
        const selector = e.target;
        const setting = selector.dataset.themeFont;
        const value = selector.value;

        try {
            const response = await fetch('/api/theme/font', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Police mise à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleSizeChange(e) {
        const input = e.target;
        const setting = input.dataset.themeSize;
        const value = input.value;

        try {
            const response = await fetch('/api/theme/size', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Taille mise à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleSpacingChange(e) {
        const input = e.target;
        const setting = input.dataset.themeSpacing;
        const value = input.value;

        try {
            const response = await fetch('/api/theme/spacing', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Espacement mis à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleBorderChange(e) {
        const input = e.target;
        const setting = input.dataset.themeBorder;
        const value = input.value;

        try {
            const response = await fetch('/api/theme/border', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Bordure mise à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleShadowChange(e) {
        const input = e.target;
        const setting = input.dataset.themeShadow;
        const value = input.value;

        try {
            const response = await fetch('/api/theme/shadow', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Ombre mise à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleAnimationChange(e) {
        const input = e.target;
        const setting = input.dataset.themeAnimation;
        const value = input.value;

        try {
            const response = await fetch('/api/theme/animation', {
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
                this.theme[setting] = value;
                this.applyTheme();
                showFlashMessage('Animation mise à jour avec succès.', 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    applyTheme() {
        // Application des couleurs
        Object.entries(this.theme.colors || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--color-${key}`, value);
        });

        // Application des styles
        Object.entries(this.theme.styles || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--style-${key}`, value);
        });

        // Application des polices
        Object.entries(this.theme.fonts || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--font-${key}`, value);
        });

        // Application des tailles
        Object.entries(this.theme.sizes || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--size-${key}`, value);
        });

        // Application des espacements
        Object.entries(this.theme.spacing || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--spacing-${key}`, value);
        });

        // Application des bordures
        Object.entries(this.theme.borders || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--border-${key}`, value);
        });

        // Application des ombres
        Object.entries(this.theme.shadows || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--shadow-${key}`, value);
        });

        // Application des animations
        Object.entries(this.theme.animations || {}).forEach(([key, value]) => {
            document.documentElement.style.setProperty(`--animation-${key}`, value);
        });

        // Application du thème clair/sombre
        if (this.theme.darkMode) {
            document.documentElement.classList.add('dark-mode');
        } else {
            document.documentElement.classList.remove('dark-mode');
        }
    }

    generateThemeHTML() {
        return `
            <div class="theme-container">
                <div class="theme-header">
                    <h2>Thème</h2>
                </div>
                <div class="theme-content">
                    <div class="theme-section">
                        <h3>Mode</h3>
                        <div class="form-group">
                            <label class="toggle">
                                <input type="checkbox" data-theme-toggle="darkMode">
                                <span class="toggle-label">Mode sombre</span>
                            </label>
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Couleurs</h3>
                        <div class="form-group">
                            <label for="primary_color">Couleur principale</label>
                            <input type="color" id="primary_color" data-theme-color="primary" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="secondary_color">Couleur secondaire</label>
                            <input type="color" id="secondary_color" data-theme-color="secondary" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="accent_color">Couleur d'accent</label>
                            <input type="color" id="accent_color" data-theme-color="accent" class="form-control">
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Styles</h3>
                        <div class="form-group">
                            <label for="border_radius">Rayon de bordure</label>
                            <input type="text" id="border_radius" data-theme-style="border-radius" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="transition">Transition</label>
                            <input type="text" id="transition" data-theme-style="transition" class="form-control">
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Polices</h3>
                        <div class="form-group">
                            <label for="primary_font">Police principale</label>
                            <select id="primary_font" data-theme-font="primary" class="form-control">
                                <option value="Arial">Arial</option>
                                <option value="Helvetica">Helvetica</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="secondary_font">Police secondaire</label>
                            <select id="secondary_font" data-theme-font="secondary" class="form-control">
                                <option value="Arial">Arial</option>
                                <option value="Helvetica">Helvetica</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                            </select>
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Tailles</h3>
                        <div class="form-group">
                            <label for="base_size">Taille de base</label>
                            <input type="text" id="base_size" data-theme-size="base" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="heading_size">Taille des titres</label>
                            <input type="text" id="heading_size" data-theme-size="heading" class="form-control">
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Espacements</h3>
                        <div class="form-group">
                            <label for="base_spacing">Espacement de base</label>
                            <input type="text" id="base_spacing" data-theme-spacing="base" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="section_spacing">Espacement des sections</label>
                            <input type="text" id="section_spacing" data-theme-spacing="section" class="form-control">
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Bordures</h3>
                        <div class="form-group">
                            <label for="border_width">Largeur de bordure</label>
                            <input type="text" id="border_width" data-theme-border="width" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="border_style">Style de bordure</label>
                            <select id="border_style" data-theme-border="style" class="form-control">
                                <option value="solid">Solid</option>
                                <option value="dashed">Dashed</option>
                                <option value="dotted">Dotted</option>
                                <option value="double">Double</option>
                            </select>
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Ombres</h3>
                        <div class="form-group">
                            <label for="box_shadow">Ombre de boîte</label>
                            <input type="text" id="box_shadow" data-theme-shadow="box" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="text_shadow">Ombre de texte</label>
                            <input type="text" id="text_shadow" data-theme-shadow="text" class="form-control">
                        </div>
                    </div>
                    <div class="theme-section">
                        <h3>Animations</h3>
                        <div class="form-group">
                            <label for="hover_animation">Animation au survol</label>
                            <select id="hover_animation" data-theme-animation="hover" class="form-control">
                                <option value="none">Aucune</option>
                                <option value="scale">Échelle</option>
                                <option value="fade">Fondu</option>
                                <option value="slide">Glissement</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="click_animation">Animation au clic</label>
                            <select id="click_animation" data-theme-animation="click" class="form-control">
                                <option value="none">Aucune</option>
                                <option value="bounce">Rebond</option>
                                <option value="pulse">Pulsation</option>
                                <option value="shake">Secousse</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
}); 