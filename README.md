# SportMatchy 🏃‍♂️

SportMatchy est une plateforme web permettant aux sportifs de trouver des partenaires pour leurs activités sportives en temps réel.

## Membres du groupe
- [Votre nom]
- [Nom de votre binôme]

## Structure du Projet

```
SportMatchy/
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js
│   │   ├── events.js
│   │   └── realtime.js
│   └── images/
├── includes/
│   ├── config.php
│   ├── database.php
│   ├── functions.php
│   └── session.php
├── pages/
│   ├── index.php
│   ├── events.php
│   ├── profile.php
│   └── create-event.php
├── api/
│   ├── events.php
│   ├── users.php
│   └── realtime.php
└── index.php
```

## Fonctionnalités

### 1. Authentification
- Inscription
- Connexion
- Déconnexion
- Gestion des sessions

### 2. Gestion des Événements Sportifs
- Création d'événements sportifs
- Recherche d'événements
- Filtrage par sport/date/lieu
- Participation aux événements
- Annulation de participation

### 3. Interactions en Temps Réel
- Mise à jour en temps réel des participants
- Notifications de nouveaux événements
- Chat intégré pour chaque événement
- Statut de disponibilité des participants

### 4. Profils Utilisateurs
- Informations personnelles
- Historique des événements
- Préférences sportives
- Statistiques de participation

### 5. Interface Responsive
- Design adaptatif (mobile/desktop)
- Navigation intuitive
- Thème professionnel et moderne

## Technologies Utilisées

- HTML5
- CSS3 (Flexbox, Grid, Media Queries)
- JavaScript (ES6+)
- PHP 8+
- MySQL
- AJAX
- WebSockets (pour le temps réel)

## Installation

1. Cloner le repository
2. Configurer la base de données dans `includes/config.php`
3. Importer le schéma de la base de données
4. Lancer le serveur web local

## Base de Données

Tables principales :
- users
- events
- sports
- event_participants
- messages

## Tests

Le projet inclut des tests unitaires pour les fonctionnalités principales :
- Tests d'authentification
- Tests de création d'événements
- Tests des interactions en temps réel

## Démonstration
Une vidéo de démonstration est disponible [lien vers la vidéo]

## Diagramme d'architecture
[Lien vers le diagramme d'architecture]
