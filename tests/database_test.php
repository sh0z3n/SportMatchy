<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class DatabaseTest {
    private $db;
    private $testResults = [];

    public function __construct() {
        try {
            $this->db = Database::getInstance();
            echo "Database connection successful.\n";
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }

    public function runTests() {
        $this->testConnection();
        $this->testUserOperations();
        $this->testSportOperations();
        $this->testEventOperations();
        $this->testBackup();
        $this->displayResults();
    }

    private function testConnection() {
        try {
            $result = $this->db->query('SELECT 1')->fetch();
            $this->testResults['connection'] = $result ? 'PASS' : 'FAIL';
        } catch (Exception $e) {
            $this->testResults['connection'] = 'FAIL: ' . $e->getMessage();
        }
    }

    private function testUserOperations() {
        try {
            // Test user creation
            $userId = $this->db->insert('users', [
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT)
            ]);
            $this->testResults['user_insert'] = $userId ? 'PASS' : 'FAIL';

            // Test user retrieval
            $user = $this->db->fetch('SELECT * FROM users WHERE id = ?', [$userId]);
            $this->testResults['user_fetch'] = $user ? 'PASS' : 'FAIL';

            // Test user update
            $this->db->update('users', ['bio' => 'Test bio'], 'id = ?', [$userId]);
            $updatedUser = $this->db->fetch('SELECT * FROM users WHERE id = ?', [$userId]);
            $this->testResults['user_update'] = ($updatedUser['bio'] === 'Test bio') ? 'PASS' : 'FAIL';

            // Cleanup
            $this->db->delete('users', 'id = ?', [$userId]);
            $this->testResults['user_delete'] = 'PASS';
        } catch (Exception $e) {
            $this->testResults['user_operations'] = 'FAIL: ' . $e->getMessage();
        }
    }

    private function testSportOperations() {
        try {
            // Test sport creation
            $sportId = $this->db->insert('sports', [
                'name' => 'Test Sport',
                'description' => 'Test Description',
                'icon' => 'fa-test'
            ]);
            $this->testResults['sport_insert'] = $sportId ? 'PASS' : 'FAIL';

            // Test sport retrieval
            $sport = $this->db->fetch('SELECT * FROM sports WHERE id = ?', [$sportId]);
            $this->testResults['sport_fetch'] = $sport ? 'PASS' : 'FAIL';

            // Cleanup
            $this->db->delete('sports', 'id = ?', [$sportId]);
            $this->testResults['sport_delete'] = 'PASS';
        } catch (Exception $e) {
            $this->testResults['sport_operations'] = 'FAIL: ' . $e->getMessage();
        }
    }

    private function testEventOperations() {
        try {
            // Create test user and sport
            $userId = $this->db->insert('users', [
                'username' => 'eventtest',
                'email' => 'event@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT)
            ]);
            
            $sportId = $this->db->insert('sports', [
                'name' => 'Event Test Sport',
                'description' => 'Test Description',
                'icon' => 'fa-test'
            ]);

            // Test event creation
            $eventId = $this->db->insert('events', [
                'title' => 'Test Event',
                'description' => 'Test Description',
                'sport_id' => $sportId,
                'creator_id' => $userId,
                'location' => 'Test Location',
                'date' => date('Y-m-d H:i:s'),
                'max_participants' => 10
            ]);
            $this->testResults['event_insert'] = $eventId ? 'PASS' : 'FAIL';

            // Test event retrieval
            $event = $this->db->fetch('SELECT * FROM events WHERE id = ?', [$eventId]);
            $this->testResults['event_fetch'] = $event ? 'PASS' : 'FAIL';

            // Cleanup
            $this->db->delete('events', 'id = ?', [$eventId]);
            $this->db->delete('sports', 'id = ?', [$sportId]);
            $this->db->delete('users', 'id = ?', [$userId]);
            $this->testResults['event_delete'] = 'PASS';
        } catch (Exception $e) {
            $this->testResults['event_operations'] = 'FAIL: ' . $e->getMessage();
        }
    }

    private function testBackup() {
        try {
            $backupFile = $this->db->backup();
            $this->testResults['backup'] = file_exists($backupFile) ? 'PASS' : 'FAIL';
        } catch (Exception $e) {
            $this->testResults['backup'] = 'FAIL: ' . $e->getMessage();
        }
    }

    private function displayResults() {
        echo "\nTest Results:\n";
        echo "=============\n";
        foreach ($this->testResults as $test => $result) {
            echo sprintf("%-20s: %s\n", $test, $result);
        }
    }
}

// Run tests
$tester = new DatabaseTest();
$tester->runTests(); 