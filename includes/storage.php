<?php
class Storage {
    private static $instance = null;
    private $data = [];

    private function __construct() {
        $this->loadData();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadData() {
        $this->data['users'] = $this->readFile(USERS_FILE);
        $this->data['events'] = $this->readFile(EVENTS_FILE);
        $this->data['sports'] = $this->readFile(SPORTS_FILE);
    }

    private function readFile($file) {
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function writeFile($file, $data) {
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    // User operations
    public function createUser($userData) {
        $users = $this->data['users']['users'] ?? [];
        $userData['id'] = count($users) + 1;
        $userData['created_at'] = date('Y-m-d H:i:s');
        $users[] = $userData;
        $this->data['users']['users'] = $users;
        $this->writeFile(USERS_FILE, $this->data['users']);
        return $userData;
    }

    public function getUserByEmail($email) {
        $users = $this->data['users']['users'] ?? [];
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        return null;
    }

    public function getUserById($id) {
        $users = $this->data['users']['users'] ?? [];
        foreach ($users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }

    public function updateUser($id, $userData) {
        $users = $this->data['users']['users'] ?? [];
        foreach ($users as $key => $user) {
            if ($user['id'] === $id) {
                $users[$key] = array_merge($user, $userData);
                $this->data['users']['users'] = $users;
                $this->writeFile(USERS_FILE, $this->data['users']);
                return $users[$key];
            }
        }
        return null;
    }

    // Event operations
    public function createEvent($eventData) {
        $events = $this->data['events']['events'] ?? [];
        $eventData['id'] = count($events) + 1;
        $eventData['created_at'] = date('Y-m-d H:i:s');
        $events[] = $eventData;
        $this->data['events']['events'] = $events;
        $this->writeFile(EVENTS_FILE, $this->data['events']);
        return $eventData;
    }

    public function getEvent($id) {
        $events = $this->data['events']['events'] ?? [];
        foreach ($events as $event) {
            if ($event['id'] === $id) {
                return $event;
            }
        }
        return null;
    }

    public function getEvents($filters = []) {
        $events = $this->data['events']['events'] ?? [];
        if (empty($filters)) {
            return $events;
        }

        return array_filter($events, function($event) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($event[$key]) || $event[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    public function updateEvent($id, $eventData) {
        $events = $this->data['events']['events'] ?? [];
        foreach ($events as $key => $event) {
            if ($event['id'] === $id) {
                $events[$key] = array_merge($event, $eventData);
                $this->data['events']['events'] = $events;
                $this->writeFile(EVENTS_FILE, $this->data['events']);
                return $events[$key];
            }
        }
        return null;
    }

    public function deleteEvent($id) {
        $events = $this->data['events']['events'] ?? [];
        foreach ($events as $key => $event) {
            if ($event['id'] === $id) {
                unset($events[$key]);
                $this->data['events']['events'] = array_values($events);
                $this->writeFile(EVENTS_FILE, $this->data['events']);
                return true;
            }
        }
        return false;
    }

    // Sport operations
    public function getSports() {
        return $this->data['sports']['sports'] ?? [];
    }

    public function getSport($id) {
        $sports = $this->data['sports']['sports'] ?? [];
        foreach ($sports as $sport) {
            if ($sport['id'] === $id) {
                return $sport;
            }
        }
        return null;
    }
} 