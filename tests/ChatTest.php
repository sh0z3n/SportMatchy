<?php
use PHPUnit\Framework\TestCase;

class ChatTest extends TestCase
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

    public function testSendMessage()
    {
        $eventId = 1;
        $senderId = 1;
        $content = 'Test message';

        $stmt = $this->pdo->prepare("
            INSERT INTO messages (event_id, sender_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $result = $stmt->execute([$eventId, $senderId, $content]);
        $this->assertTrue($result);

        $messageId = $this->pdo->lastInsertId();

        // Verify message was created
        $stmt = $this->pdo->prepare("
            SELECT m.*, u.username, u.profile_picture
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch();

        $this->assertEquals($content, $message['content']);
        $this->assertEquals($eventId, $message['event_id']);
        $this->assertEquals($senderId, $message['sender_id']);
    }

    public function testGetEventMessages()
    {
        $eventId = 1;

        $stmt = $this->pdo->prepare("
            SELECT m.*, u.username, u.profile_picture
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.event_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$eventId]);
        $messages = $stmt->fetchAll();

        $this->assertNotEmpty($messages);
        foreach ($messages as $message) {
            $this->assertEquals($eventId, $message['event_id']);
            $this->assertNotEmpty($message['content']);
            $this->assertNotEmpty($message['username']);
        }
    }

    public function testMessageOrdering()
    {
        $eventId = 1;

        $stmt = $this->pdo->prepare("
            SELECT created_at
            FROM messages
            WHERE event_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$eventId]);
        $messages = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Verify messages are in chronological order
        $sorted = $messages;
        sort($sorted);
        $this->assertEquals($sorted, $messages);
    }
} 