<?php
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            "mysql:host=localhost;dbname=sportmatchy_test",
            "root",
            "",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function testCreateEvent()
    {
        $title = 'Test Event';
        $description = 'Test Description';
        $sportId = 1;
        $creatorId = 1;
        $location = 'Test Location';
        $maxParticipants = 10;
        $startTime = date('Y-m-d H:i:s', strtotime('+1 day'));
        $endTime = date('Y-m-d H:i:s', strtotime('+2 days'));

        $stmt = $this->pdo->prepare("
            INSERT INTO events (
                title, description, sport_id, creator_id, location,
                max_participants, start_time, end_time, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");

        $result = $stmt->execute([
            $title, $description, $sportId, $creatorId,
            $location, $maxParticipants, $startTime, $endTime
        ]);

        $this->assertTrue($result);
        $eventId = $this->pdo->lastInsertId();

        // Verify event was created
        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();

        $this->assertEquals($title, $event['title']);
        $this->assertEquals($description, $event['description']);
        $this->assertEquals($sportId, $event['sport_id']);
        $this->assertEquals($creatorId, $event['creator_id']);
    }

    public function testJoinEvent()
    {
        $eventId = 1;
        $userId = 2;

        $stmt = $this->pdo->prepare("
            INSERT INTO event_participants (event_id, user_id, status, joined_at)
            VALUES (?, ?, 'joined', NOW())
        ");

        $result = $stmt->execute([$eventId, $userId]);
        $this->assertTrue($result);

        // Verify participant was added
        $stmt = $this->pdo->prepare("
            SELECT * FROM event_participants 
            WHERE event_id = ? AND user_id = ? AND status = 'joined'
        ");
        $stmt->execute([$eventId, $userId]);
        $participant = $stmt->fetch();

        $this->assertNotFalse($participant);
    }

    public function testLeaveEvent()
    {
        $eventId = 1;
        $userId = 2;

        $stmt = $this->pdo->prepare("
            UPDATE event_participants
            SET status = 'declined'
            WHERE event_id = ? AND user_id = ?
        ");

        $result = $stmt->execute([$eventId, $userId]);
        $this->assertTrue($result);

        // Verify participant status was updated
        $stmt = $this->pdo->prepare("
            SELECT * FROM event_participants 
            WHERE event_id = ? AND user_id = ?
        ");
        $stmt->execute([$eventId, $userId]);
        $participant = $stmt->fetch();

        $this->assertEquals('declined', $participant['status']);
    }

    public function testEventMaxParticipants()
    {
        $eventId = 1;
        
        // Get current participant count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count, e.max_participants
            FROM event_participants ep
            JOIN events e ON e.id = ep.event_id
            WHERE ep.event_id = ? AND ep.status = 'joined'
            GROUP BY e.id
        ");
        $stmt->execute([$eventId]);
        $result = $stmt->fetch();

        $this->assertLessThanOrEqual($result['max_participants'], $result['count']);
    }
} 