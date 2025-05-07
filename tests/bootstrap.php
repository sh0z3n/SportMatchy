<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Create test database if it doesn't exist
$pdo = new PDO(
    "mysql:host=localhost",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE DATABASE IF NOT EXISTS sportmatchy_test");
$pdo->exec("USE sportmatchy_test");

// Load schema
$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
$pdo->exec($schema);

// Load test data
$testData = file_get_contents(__DIR__ . '/test_data.sql');
$pdo->exec($testData);

// Mock session
session_start();
$_SESSION = []; 