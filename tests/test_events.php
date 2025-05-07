<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';
require_once '../includes/auth.php';

class EventTest {
    private $db;
    private $testUserId;
    private $testEventId;
    private $testSportId;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->setupTestData();
    }
    
    private function setupTestData() {
        // Create test sport
        $this->testSportId = $this->db->insert('sports', [
            'name' => 'Test Sport',
            'description' => 'Test sport description'
        ]);
        
        // Create test user
        $this->testUserId = $this->db->insert('users', [
            'username' => 'testuser_' . time(),
            'email' => 'test_' . time() . '@example.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT)
        ]);
        
        // Create test event
        $this->testEventId = $this->db->insert('events', [
            'title' => 'Test Event',
            'description' => 'Test event description',
            'sport_id' => $this->testSportId,
            'creator_id' => $this->testUserId,
            'location' => 'Test Location',
            'event_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_participants' => 10
        ]);
    }
    
    public function runTests() {
        echo "Running Event Tests...\n\n";
        
        $this->testCreateEvent();
        $this->testJoinEvent();
        $this->testLeaveEvent();
        $this->testDeleteEvent();
        $this->testEventListing();
        
        $this->cleanup();
    }
    
    private function testCreateEvent() {
        echo "Testing Event Creation...\n";
        
        $eventData = [
            'title' => 'New Test Event',
            'description' => 'New test event description',
            'sport_id' => $this->testSportId,
            'creator_id' => $this->testUserId,
            'location' => 'New Test Location',
            'event_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'max_participants' => 5
        ];
        
        $eventId = $this->db->insert('events', $eventData);
        
        if ($eventId) {
            $event = $this->db->query(
                "SELECT * FROM events WHERE id = ?",
                [$eventId]
            )->fetch();
            
            if ($event && $event['title'] === $eventData['title']) {
                echo "✓ Event creation successful\n";
            } else {
                echo "✗ Event creation failed\n";
            }
        } else {
            echo "✗ Event creation failed\n";
        }
    }
    
    private function testJoinEvent() {
        echo "\nTesting Event Join...\n";
        
        // Create another test user
        $userId = $this->db->insert('users', [
            'username' => 'joiner_' . time(),
            'email' => 'joiner_' . time() . '@example.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT)
        ]);
        
        // Join event
        $this->db->insert('event_participants', [
            'event_id' => $this->testEventId,
            'user_id' => $userId,
            'status' => 'joined'
        ]);
        
        // Check if joined
        $isParticipant = $this->db->query(
            "SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND user_id = ?",
            [$this->testEventId, $userId]
        )->fetchColumn();
        
        if ($isParticipant) {
            echo "✓ Event join successful\n";
        } else {
            echo "✗ Event join failed\n";
        }
    }
    
    private function testLeaveEvent() {
        echo "\nTesting Event Leave...\n";
        
        // Create another test user
        $userId = $this->db->insert('users', [
            'username' => 'leaver_' . time(),
            'email' => 'leaver_' . time() . '@example.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT)
        ]);
        
        // Join event
        $this->db->insert('event_participants', [
            'event_id' => $this->testEventId,
            'user_id' => $userId,
            'status' => 'joined'
        ]);
        
        // Leave event
        $this->db->query(
            "DELETE FROM event_participants WHERE event_id = ? AND user_id = ?",
            [$this->testEventId, $userId]
        );
        
        // Check if left
        $isParticipant = $this->db->query(
            "SELECT COUNT(*) FROM event_participants WHERE event_id = ? AND user_id = ?",
            [$this->testEventId, $userId]
        )->fetchColumn();
        
        if (!$isParticipant) {
            echo "✓ Event leave successful\n";
        } else {
            echo "✗ Event leave failed\n";
        }
    }
    
    private function testDeleteEvent() {
        echo "\nTesting Event Deletion...\n";
        
        // Create test event
        $eventId = $this->db->insert('events', [
            'title' => 'Delete Test Event',
            'description' => 'Delete test event description',
            'sport_id' => $this->testSportId,
            'creator_id' => $this->testUserId,
            'location' => 'Delete Test Location',
            'event_date' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'max_participants' => 5
        ]);
        
        // Delete event
        $this->db->query("DELETE FROM events WHERE id = ?", [$eventId]);
        
        // Check if deleted
        $event = $this->db->query(
            "SELECT * FROM events WHERE id = ?",
            [$eventId]
        )->fetch();
        
        if (!$event) {
            echo "✓ Event deletion successful\n";
        } else {
            echo "✗ Event deletion failed\n";
        }
    }
    
    private function testEventListing() {
        echo "\nTesting Event Listing...\n";
        
        // Get events
        $events = $this->db->query(
            "SELECT e.*, s.name as sport_name, u.username as creator_name,
                    (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as participant_count
             FROM events e 
             JOIN sports s ON e.sport_id = s.id 
             JOIN users u ON e.creator_id = u.id 
             WHERE e.event_date >= NOW() 
             ORDER BY e.event_date ASC"
        )->fetchAll();
        
        if (count($events) > 0) {
            echo "✓ Event listing successful\n";
            echo "Found " . count($events) . " events\n";
        } else {
            echo "✗ Event listing failed\n";
        }
    }
    
    private function cleanup() {
        echo "\nCleaning up test data...\n";
        
        // Delete test events
        $this->db->query("DELETE FROM events WHERE creator_id = ?", [$this->testUserId]);
        
        // Delete test sport
        $this->db->query("DELETE FROM sports WHERE id = ?", [$this->testSportId]);
        
        // Delete test users
        $this->db->query("DELETE FROM users WHERE id = ?", [$this->testUserId]);
        
        echo "Cleanup complete\n";
    }
}

// Run tests
$test = new EventTest();
$test->runTests(); 