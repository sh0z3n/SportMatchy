<?php
require 'vendor/autoload.php';
require_once '../includes/config.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

class EventWebSocket implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $users = [];
    protected $eventSubscriptions = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'auth':
                // Authentification de l'utilisateur
                if (isset($data['userId'])) {
                    $this->users[$from->resourceId] = $data['userId'];
                    echo "Utilisateur {$data['userId']} authentifié\n";
                }
                break;

            case 'subscribe':
                // Abonnement à un événement
                if (isset($data['event_id'])) {
                    $this->eventSubscriptions[$from->resourceId] = $data['event_id'];
                    echo "Client {$from->resourceId} abonné à l'événement {$data['event_id']}\n";
                }
                break;

            case 'new_message':
                // Diffusion d'un nouveau message
                if (isset($data['event_id']) && isset($data['message'])) {
                    $this->broadcastMessage($data['event_id'], $data['message']);
                }
                break;

            case 'event_update':
                // Mise à jour d'un événement
                if (isset($data['event_id']) && isset($data['event'])) {
                    $this->broadcastEventUpdate($data['event_id'], $data['event']);
                }
                break;
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->users[$conn->resourceId]);
        unset($this->eventSubscriptions[$conn->resourceId]);
        echo "Connexion {$conn->resourceId} fermée\n";
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "Erreur: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastMessage($eventId, $message) {
        foreach ($this->clients as $client) {
            if (isset($this->eventSubscriptions[$client->resourceId]) 
                && $this->eventSubscriptions[$client->resourceId] == $eventId) {
                $client->send(json_encode([
                    'type' => 'new_message',
                    'event_id' => $eventId,
                    'message' => $message
                ]));
            }
        }
    }

    protected function broadcastEventUpdate($eventId, $event) {
        foreach ($this->clients as $client) {
            if (isset($this->eventSubscriptions[$client->resourceId]) 
                && $this->eventSubscriptions[$client->resourceId] == $eventId) {
                $client->send(json_encode([
                    'type' => 'event_update',
                    'event_id' => $eventId,
                    'event' => $event
                ]));
            }
        }
    }
}

// Création du serveur WebSocket
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new EventWebSocket()
        )
    ),
    WS_PORT,
    WS_HOST
);

echo "Serveur WebSocket démarré sur ws://" . WS_HOST . ":" . WS_PORT . "\n";
$server->run(); 