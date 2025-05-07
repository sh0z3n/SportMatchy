-- Création de la base de données
CREATE DATABASE IF NOT EXISTS sportmatchy;
USE sportmatchy;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des sports
CREATE TABLE IF NOT EXISTS sports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des événements
CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    sport_id INTEGER NOT NULL,
    creator_id INTEGER NOT NULL,
    location VARCHAR(255) NOT NULL,
    event_date DATETIME NOT NULL,
    max_participants INTEGER DEFAULT 10,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sport_id) REFERENCES sports(id),
    FOREIGN KEY (creator_id) REFERENCES users(id)
);

-- Table des participants aux événements
CREATE TABLE IF NOT EXISTS event_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'joined',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(event_id, user_id)
);

-- Table des messages
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    sender_id INT,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (sender_id) REFERENCES users(id)
);

-- Table des préférences sportives des utilisateurs
CREATE TABLE user_sport_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    sport_id INT,
    skill_level ENUM('débutant', 'intermédiaire', 'avancé') DEFAULT 'intermédiaire',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (sport_id) REFERENCES sports(id),
    UNIQUE KEY unique_preference (user_id, sport_id)
);

-- Insertion des sports de base
INSERT OR IGNORE INTO sports (name, description) VALUES
    ('Football', 'Le sport le plus populaire au monde'),
    ('Basketball', 'Sport d''équipe rapide et dynamique'),
    ('Tennis', 'Sport de raquette élégant'),
    ('Volleyball', 'Sport d''équipe explosif'),
    ('Rugby', 'Sport de contact intense'),
    ('Natation', 'Sport aquatique complet'),
    ('Cyclisme', 'Sport d''endurance sur deux roues'),
    ('Course à pied', 'Sport accessible à tous'),
    ('Yoga', 'Discipline corps-esprit'),
    ('Musculation', 'Renforcement musculaire');

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_events_sport_id ON events(sport_id);
CREATE INDEX IF NOT EXISTS idx_events_creator_id ON events(creator_id);
CREATE INDEX IF NOT EXISTS idx_events_event_date ON events(event_date);
CREATE INDEX IF NOT EXISTS idx_event_participants_event_id ON event_participants(event_id);
CREATE INDEX IF NOT EXISTS idx_event_participants_user_id ON event_participants(user_id); 