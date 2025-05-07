-- Test users
INSERT INTO users (username, email, password_hash, created_at) VALUES
('testuser1', 'test1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW()),
('testuser2', 'test2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- Test sports
INSERT INTO sports (name, description, icon) VALUES
('Football', 'Le sport le plus populaire au monde', 'futbol'),
('Basketball', 'Sport collectif avec panier', 'basketball-ball'),
('Tennis', 'Sport de raquette', 'table-tennis');

-- Test events
INSERT INTO events (title, description, sport_id, creator_id, location, max_participants, start_time, end_time, status, created_at) VALUES
('Match de foot amical', 'Match amical de football', 1, 1, 'Stade municipal', 10, DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY), 'active', NOW()),
('Tournoi de basket', 'Tournoi 3v3', 2, 2, 'Gymnase central', 6, DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY), 'active', NOW());

-- Test participants
INSERT INTO event_participants (event_id, user_id, status, joined_at) VALUES
(1, 1, 'joined', NOW()),
(1, 2, 'joined', NOW());

-- Test messages
INSERT INTO messages (event_id, sender_id, content, created_at) VALUES
(1, 1, 'Bonjour à tous !', NOW()),
(1, 2, 'Salut !', NOW()); 