<?php
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=sportmatchy;charset=utf8mb4',
        'sportmatchy',
        'sportmatchy123',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
} 