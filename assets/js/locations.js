class LocationManager {
    constructor() {
        this.locations = [];
        this.currentLocation = null;
        this.map = null;
        this.markers = new Map();
        this.initializeEventListeners();
        this.initializeMap();
        this.loadLocations();
    }

    initializeEventListeners() {
        // Gestion du formulaire de lieu
        const locationForm = document.querySelector('[data-location-form]');
        if (locationForm) {
            locationForm.addEventListener('submit', (e) => this.handleLocationSubmit(e));
        }

        // Gestion de la recherche de lieu
        const searchInput = document.querySelector('[data-location-search]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleLocationSearch(e));
        }

        // Gestion des filtres de lieu
        const filterButtons = document.querySelectorAll('[data-location-filter]');
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleLocationFilter(e));
        });

        // Gestion des actions de lieu
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-location-action]')) {
                this.handleLocationAction(e);
            }
        });

        // Gestion des suppressions de lieu
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-location-delete]')) {
                this.handleLocationDelete(e);
            }
        });

        // Gestion des partages de lieu
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-location-share]')) {
                this.handleLocationShare(e);
            }
        });

        // Gestion des favoris de lieu
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-location-favorite]')) {
                this.handleLocationFavorite(e);
            }
        });

        // Gestion des commentaires de lieu
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-location-comment]')) {
                this.handleLocationComment(e);
            }
        });
    }

    initializeMap() {
        const mapElement = document.querySelector('[data-location-map]');
        if (!mapElement) return;

        this.map = new google.maps.Map(mapElement, {
            center: { lat: 48.8566, lng: 2.3522 }, // Paris par défaut
            zoom: 12,
            styles: this.getMapStyles()
        });

        // Gestion du clic sur la carte
        this.map.addListener('click', (e) => {
            if (this.isAddingLocation) {
                this.handleMapClick(e);
            }
        });
    }

    async loadLocations() {
        try {
            const response = await fetch('/api/locations', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.locations = data.locations;
                this.updateLocationUI();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des lieux:', error);
        }
    }

    async handleLocationSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('/api/locations', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.locations.unshift(data.location);
                this.updateLocationUI();
                this.addMarker(data.location);
                form.reset();
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    handleLocationSearch(e) {
        const query = e.target.value.toLowerCase();
        const filteredLocations = this.locations.filter(location => 
            location.name.toLowerCase().includes(query) ||
            location.address.toLowerCase().includes(query)
        );
        this.updateLocationList(filteredLocations);
        this.updateMarkers(filteredLocations);
    }

    async handleLocationFilter(e) {
        e.preventDefault();
        const filter = e.target.dataset.locationFilter;

        try {
            const response = await fetch(`/api/locations/filter/${filter}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.locations = data.locations;
                this.updateLocationUI();
                this.updateMarkers(data.locations);
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleLocationAction(e) {
        e.preventDefault();
        const action = e.target.dataset.locationAction;
        const locationId = e.target.closest('[data-location-item]').dataset.locationId;

        try {
            const response = await fetch(`/api/locations/${locationId}/${action}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateLocation(locationId, data.location);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleLocationDelete(e) {
        e.preventDefault();
        const locationId = e.target.closest('[data-location-item]').dataset.locationId;

        if (!confirm('Êtes-vous sûr de vouloir supprimer ce lieu ?')) {
            return;
        }

        try {
            const response = await fetch(`/api/locations/${locationId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.removeLocation(locationId);
                this.removeMarker(locationId);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleLocationShare(e) {
        e.preventDefault();
        const locationId = e.target.closest('[data-location-item]').dataset.locationId;

        try {
            const response = await fetch(`/api/locations/${locationId}/share`, {
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

    async handleLocationFavorite(e) {
        e.preventDefault();
        const locationId = e.target.closest('[data-location-item]').dataset.locationId;

        try {
            const response = await fetch(`/api/locations/${locationId}/favorite`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateLocation(locationId, data.location);
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleLocationComment(e) {
        e.preventDefault();
        const locationId = e.target.closest('[data-location-item]').dataset.locationId;
        const commentInput = e.target.closest('[data-location-item]').querySelector('[data-comment-input]');
        const comment = commentInput.value.trim();

        if (!comment) return;

        try {
            const response = await fetch(`/api/locations/${locationId}/comment`, {
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
                this.updateLocation(locationId, data.location);
                commentInput.value = '';
                showFlashMessage(data.message, 'success');
            } else {
                showFlashMessage(data.message, 'error');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    handleMapClick(e) {
        const lat = e.latLng.lat();
        const lng = e.latLng.lng();

        // Mise à jour des champs de formulaire
        const latInput = document.querySelector('[data-location-lat]');
        const lngInput = document.querySelector('[data-location-lng]');
        if (latInput && lngInput) {
            latInput.value = lat;
            lngInput.value = lng;
        }

        // Ajout d'un marqueur temporaire
        if (this.tempMarker) {
            this.tempMarker.setMap(null);
        }
        this.tempMarker = new google.maps.Marker({
            position: { lat, lng },
            map: this.map,
            draggable: true
        });

        // Mise à jour de la position lors du déplacement
        this.tempMarker.addListener('dragend', (e) => {
            const newLat = e.latLng.lat();
            const newLng = e.latLng.lng();
            if (latInput && lngInput) {
                latInput.value = newLat;
                lngInput.value = newLng;
            }
        });
    }

    updateLocationUI() {
        this.updateLocationList();
        this.updateMarkers(this.locations);
    }

    updateLocationList(locations = this.locations) {
        const list = document.querySelector('[data-location-list]');
        if (list) {
            list.innerHTML = locations.map(location => this.generateLocationHTML(location)).join('');
        }
    }

    updateLocation(locationId, updatedLocation) {
        const index = this.locations.findIndex(l => l.id === locationId);
        if (index !== -1) {
            this.locations[index] = updatedLocation;
            this.updateLocationUI();
        }
    }

    removeLocation(locationId) {
        this.locations = this.locations.filter(l => l.id !== locationId);
        this.updateLocationUI();
    }

    addMarker(location) {
        if (!this.map) return;

        const marker = new google.maps.Marker({
            position: { lat: location.lat, lng: location.lng },
            map: this.map,
            title: location.name,
            icon: this.getMarkerIcon(location)
        });

        const infoWindow = new google.maps.InfoWindow({
            content: this.generateMarkerContent(location)
        });

        marker.addListener('click', () => {
            infoWindow.open(this.map, marker);
        });

        this.markers.set(location.id, marker);
    }

    updateMarkers(locations) {
        // Suppression des marqueurs non utilisés
        this.markers.forEach((marker, id) => {
            if (!locations.find(l => l.id === id)) {
                marker.setMap(null);
                this.markers.delete(id);
            }
        });

        // Ajout des nouveaux marqueurs
        locations.forEach(location => {
            if (!this.markers.has(location.id)) {
                this.addMarker(location);
            }
        });
    }

    removeMarker(locationId) {
        const marker = this.markers.get(locationId);
        if (marker) {
            marker.setMap(null);
            this.markers.delete(locationId);
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

    generateLocationHTML(location) {
        return `
            <div class="location-item" data-location-item="${location.id}">
                <div class="location-header">
                    <div class="location-info">
                        <h3>${location.name}</h3>
                        <p>${location.address}</p>
                    </div>
                    <div class="location-actions">
                        <button class="btn btn-icon ${location.favorite ? 'active' : ''}" 
                                data-location-favorite title="Favori">
                            <i class="fas fa-heart"></i>
                        </button>
                        <button class="btn btn-icon" data-location-share title="Partager">
                            <i class="fas fa-share"></i>
                        </button>
                        ${location.can_delete ? `
                            <button class="btn btn-icon" data-location-delete title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="location-content">
                    <div class="location-image">
                        <img src="${location.image}" alt="${location.name}">
                    </div>
                    <div class="location-details">
                        <p>${location.description}</p>
                        <div class="location-meta">
                            <span><i class="fas fa-star"></i> ${location.rating}</span>
                            <span><i class="fas fa-comment"></i> ${location.comments_count}</span>
                            <span><i class="fas fa-heart"></i> ${location.favorites_count}</span>
                        </div>
                    </div>
                </div>
                <div class="location-footer">
                    <div class="location-comments">
                        ${this.generateCommentsHTML(location.comments)}
                        <div class="comment-form">
                            <input type="text" data-comment-input placeholder="Ajouter un commentaire...">
                            <button class="btn btn-primary" data-location-comment>Envoyer</button>
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
                                <span class="comment-time">${this.formatLocationTime(comment.created_at)}</span>
                            </div>
                            <p>${comment.content}</p>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    generateMarkerContent(location) {
        return `
            <div class="marker-content">
                <h3>${location.name}</h3>
                <p>${location.address}</p>
                <div class="marker-actions">
                    <a href="/locations/${location.id}" class="btn btn-sm">Voir plus</a>
                </div>
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

    getMarkerIcon(location) {
        // Personnalisation des icônes de marqueur selon le type de lieu
        const icons = {
            'sport': {
                url: '/assets/images/markers/sport.png',
                scaledSize: new google.maps.Size(32, 32)
            },
            'event': {
                url: '/assets/images/markers/event.png',
                scaledSize: new google.maps.Size(32, 32)
            },
            'default': {
                url: '/assets/images/markers/default.png',
                scaledSize: new google.maps.Size(32, 32)
            }
        };
        return icons[location.type] || icons.default;
    }

    getMapStyles() {
        // Personnalisation du style de la carte
        return [
            {
                "featureType": "all",
                "elementType": "labels.text.fill",
                "stylers": [{"saturation": 36}, {"color": "#333333"}, {"lightness": 40}]
            },
            {
                "featureType": "all",
                "elementType": "labels.text.stroke",
                "stylers": [{"visibility": "on"}, {"color": "#ffffff"}, {"lightness": 16}]
            },
            {
                "featureType": "all",
                "elementType": "labels.icon",
                "stylers": [{"visibility": "off"}]
            },
            {
                "featureType": "administrative",
                "elementType": "geometry.fill",
                "stylers": [{"color": "#fefefe"}, {"lightness": 20}]
            },
            {
                "featureType": "administrative",
                "elementType": "geometry.stroke",
                "stylers": [{"color": "#fefefe"}, {"lightness": 17}, {"weight": 1.2}]
            },
            {
                "featureType": "landscape",
                "elementType": "geometry",
                "stylers": [{"color": "#f5f5f5"}, {"lightness": 20}]
            },
            {
                "featureType": "poi",
                "elementType": "geometry",
                "stylers": [{"color": "#f5f5f5"}, {"lightness": 21}]
            },
            {
                "featureType": "poi.park",
                "elementType": "geometry",
                "stylers": [{"color": "#dedede"}, {"lightness": 21}]
            },
            {
                "featureType": "road.highway",
                "elementType": "geometry.fill",
                "stylers": [{"color": "#ffffff"}, {"lightness": 17}]
            },
            {
                "featureType": "road.highway",
                "elementType": "geometry.stroke",
                "stylers": [{"color": "#ffffff"}, {"lightness": 29}, {"weight": 0.2}]
            },
            {
                "featureType": "road.arterial",
                "elementType": "geometry",
                "stylers": [{"color": "#ffffff"}, {"lightness": 18}]
            },
            {
                "featureType": "road.local",
                "elementType": "geometry",
                "stylers": [{"color": "#ffffff"}, {"lightness": 16}]
            },
            {
                "featureType": "transit",
                "elementType": "geometry",
                "stylers": [{"color": "#f2f2f2"}, {"lightness": 19}]
            },
            {
                "featureType": "water",
                "elementType": "geometry",
                "stylers": [{"color": "#e9e9e9"}, {"lightness": 17}]
            }
        ];
    }

    formatLocationTime(timestamp) {
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
    window.locationManager = new LocationManager();
}); 