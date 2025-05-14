<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

class ChatServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $users = [];
    protected $userGroups = [];
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->db = new PDO(
            'mysql:host=localhost;dbname=sportmatchy;charset=utf8mb4',
            'sportmatchy',
            'sportmatchy123',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data) return;
        switch ($data['type'] ?? null) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'join_group':
                $this->handleJoinGroup($from, $data);
                break;
            case 'leave_group':
                $this->handleLeaveGroup($from, $data);
                break;
            case 'message':
                $this->handleMessage($from, $data);
                break;
            case 'typing':
                $this->handleTyping($from, $data);
                break;
        }
    }

    protected function handleAuth($conn, $data) {
        if (isset($data['userId'])) {
            $this->users[$conn->resourceId] = $data['userId'];
            $conn->send(json_encode([
                'type' => 'auth',
                'status' => 'success'
            ]));
        }
    }

    protected function handleJoinGroup($conn, $data) {
        if (!isset($this->users[$conn->resourceId], $data['groupId'])) return;
        $this->userGroups[$conn->resourceId] = $data['groupId'];
        $conn->send(json_encode([
            'type' => 'join_group',
            'groupId' => $data['groupId']
        ]));
    }

    protected function handleLeaveGroup($conn, $data) {
        unset($this->userGroups[$conn->resourceId]);
        $conn->send(json_encode([
            'type' => 'leave_group'
        ]));
    }

    protected function handleMessage($from, $data) {
        if (!isset($this->users[$from->resourceId], $this->userGroups[$from->resourceId], $data['message'])) return;
        if (!isset($data['groupId'])) return;
        $userId = $this->users[$from->resourceId];
        $groupId = $data['groupId'];
        $message = $data['message'];
        // Save message to database
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$groupId, $userId, $message]);
        // Broadcast to all clients in the same group
        foreach ($this->clients as $client) {
            if (
                isset($this->userGroups[$client->resourceId]) &&
                $this->userGroups[$client->resourceId] == $groupId
            ) {
                $client->send(json_encode([
                    'type' => 'message',
                    'groupId' => $groupId,
                    'userId' => $userId,
                    'message' => $message,
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
            }
        }
    }

    protected function handleTyping($from, $data) {
        if (!isset($this->users[$from->resourceId], $this->userGroups[$from->resourceId])) return;
        if (!isset($data['groupId'])) return;
        $userId = $this->users[$from->resourceId];
        $groupId = $data['groupId'];
        $isTyping = $data['isTyping'] ?? false;
        foreach ($this->clients as $client) {
            if (
                isset($this->userGroups[$client->resourceId]) &&
                $this->userGroups[$client->resourceId] == $groupId &&
                $client !== $from
            ) {
                $client->send(json_encode([
                    'type' => 'typing',
                    'groupId' => $groupId,
                    'userId' => $userId,
                    'isTyping' => $isTyping
                ]));
            }
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->users[$conn->resourceId], $this->userGroups[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "Chat server started on port 8080\n";
$server->run(); 