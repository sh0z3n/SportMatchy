<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

class DatabaseInitializer {
    private $db;
    private $schemaFile;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->schemaFile = __DIR__ . '/schema.sql';
    }
    
    public function initialize() {
        echo "Initializing database...\n";
        
        try {
            // Read schema file
            $schema = file_get_contents($this->schemaFile);
            if ($schema === false) {
                throw new Exception("Could not read schema file");
            }
            
            // Split schema into individual statements
            $statements = array_filter(
                array_map(
                    'trim',
                    explode(';', $schema)
                ),
                'strlen'
            );
            
            // Execute each statement
            foreach ($statements as $statement) {
                $this->db->query($statement);
                echo ".";
            }
            
            echo "\nDatabase initialized successfully!\n";
            
            // Verify initialization
            $this->verifyInitialization();
        } catch (Exception $e) {
            echo "\nError initializing database: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function verifyInitialization() {
        echo "\nVerifying initialization...\n";
        
        // Check tables
        $tables = ['users', 'sports', 'events', 'event_participants'];
        foreach ($tables as $table) {
            $exists = $this->db->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name=?",
                [$table]
            )->fetch();
            
            if ($exists) {
                echo "✓ Table '$table' exists\n";
            } else {
                echo "✗ Table '$table' does not exist\n";
            }
        }
        
        // Check indexes
        $indexes = [
            'idx_events_sport_id',
            'idx_events_creator_id',
            'idx_events_event_date',
            'idx_event_participants_event_id',
            'idx_event_participants_user_id'
        ];
        
        foreach ($indexes as $index) {
            $exists = $this->db->query(
                "SELECT name FROM sqlite_master WHERE type='index' AND name=?",
                [$index]
            )->fetch();
            
            if ($exists) {
                echo "✓ Index '$index' exists\n";
            } else {
                echo "✗ Index '$index' does not exist\n";
            }
        }
        
        // Check default sports
        $sports = $this->db->query("SELECT COUNT(*) as count FROM sports")->fetch();
        if ($sports['count'] >= 10) {
            echo "✓ Default sports are present\n";
        } else {
            echo "✗ Default sports are missing\n";
        }
    }
}

// Run initialization
$initializer = new DatabaseInitializer();
$initializer->initialize(); 