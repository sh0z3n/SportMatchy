class ShareManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Gestion des boutons de partage
        const shareButtons = document.querySelectorAll('[data-share-button]');
        shareButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleShareClick(e));
        });

        // Gestion des liens de partage
        const shareLinks = document.querySelectorAll('[data-share-link]');
        shareLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handleShareLinkClick(e));
        });

        // Gestion des QR codes
        const qrButtons = document.querySelectorAll('[data-qr-button]');
        qrButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleQRClick(e));
        });
    }

    async handleShareClick(e) {
        e.preventDefault();
        const button = e.target;
        const type = button.dataset.shareType;
        const url = button.dataset.shareUrl;
        const title = button.dataset.shareTitle;
        const text = button.dataset.shareText;

        try {
            if (navigator.share) {
                await navigator.share({
                    title,
                    text,
                    url
                });
            } else {
                this.showShareModal(type, url, title, text);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                showFlashMessage('Une erreur est survenue lors du partage.', 'error');
            }
        }
    }

    async handleShareLinkClick(e) {
        e.preventDefault();
        const link = e.target;
        const url = link.dataset.shareUrl;

        try {
            await navigator.clipboard.writeText(url);
            showFlashMessage('Lien copié dans le presse-papiers !', 'success');
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors de la copie du lien.', 'error');
        }
    }

    async handleQRClick(e) {
        e.preventDefault();
        const button = e.target;
        const url = button.dataset.qrUrl;

        try {
            const response = await fetch(`/api/qr-code?url=${encodeURIComponent(url)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showQRModal(data.qr_code);
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue lors de la génération du QR code.', 'error');
        }
    }

    showShareModal(type, url, title, text) {
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
                        ${this.generateShareOptionsHTML(type, url, title, text)}
                    </div>
                    <div class="share-link">
                        <input type="text" value="${url}" readonly>
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
                await navigator.clipboard.writeText(url);
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

    showQRModal(qrCode) {
        const modal = document.createElement('div');
        modal.className = 'qr-modal';
        modal.innerHTML = `
            <div class="qr-modal-content">
                <div class="qr-modal-header">
                    <h3>QR Code</h3>
                    <button class="close-button">&times;</button>
                </div>
                <div class="qr-modal-body">
                    <img src="${qrCode}" alt="QR Code" class="qr-code">
                    <div class="qr-actions">
                        <button class="download-button">Télécharger</button>
                        <button class="share-button">Partager</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Gestion de la fermeture
        modal.querySelector('.close-button').addEventListener('click', () => {
            modal.remove();
        });

        // Gestion du téléchargement
        modal.querySelector('.download-button').addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = qrCode;
            link.download = 'qr-code.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Gestion du partage
        modal.querySelector('.share-button').addEventListener('click', async () => {
            try {
                await navigator.share({
                    title: 'QR Code',
                    text: 'Scannez ce QR code',
                    url: qrCode
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    showFlashMessage('Une erreur est survenue lors du partage.', 'error');
                }
            }
        });

        // Fermeture en cliquant en dehors
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    generateShareOptionsHTML(type, url, title, text) {
        const options = [];

        // Facebook
        options.push(`
            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" 
               target="_blank" 
               class="share-option facebook">
                <i class="fab fa-facebook"></i>
                Facebook
            </a>
        `);

        // Twitter
        options.push(`
            <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}" 
               target="_blank" 
               class="share-option twitter">
                <i class="fab fa-twitter"></i>
                Twitter
            </a>
        `);

        // LinkedIn
        options.push(`
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}" 
               target="_blank" 
               class="share-option linkedin">
                <i class="fab fa-linkedin"></i>
                LinkedIn
            </a>
        `);

        // WhatsApp
        options.push(`
            <a href="https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}" 
               target="_blank" 
               class="share-option whatsapp">
                <i class="fab fa-whatsapp"></i>
                WhatsApp
            </a>
        `);

        // Email
        options.push(`
            <a href="mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(text + '\n\n' + url)}" 
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
    window.shareManager = new ShareManager();
}); 