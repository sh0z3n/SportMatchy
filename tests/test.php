<?php
// Start session before any output
ob_start();
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

class TestSuite {
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $tests = [];
    private $dbAvailable = false;

    public function __construct() {
        try {
            $this->db = Database::getInstance();
            $this->dbAvailable = true;
        } catch (Exception $e) {
            echo "⚠️ Database not available: " . $e->getMessage() . "\n";
            echo "Skipping database tests...\n\n";
        }
        $this->initializeTests();
    }

    private function initializeTests() {
        // Test des fonctions utilitaires
        $this->tests['formatDate'] = function() {
            $date = '2024-03-20 15:30:00';
            $formatted = formatDate($date);
            return $this->assert($formatted === '20/03/2024 15:30', 'formatDate');
        };

        $this->tests['formatDateRelative'] = function() {
            $date = date('Y-m-d H:i:s', strtotime('-1 hour'));
            $formatted = formatDateRelative($date);
            return $this->assert($formatted === 'il y a 1 heure', 'formatDateRelative');
        };

        $this->tests['validateEmail'] = function() {
            $valid = validateEmail('test@example.com');
            $invalid = validateEmail('invalid-email');
            return $this->assert($valid && !$invalid, 'validateEmail');
        };

        $this->tests['validatePassword'] = function() {
            $valid = validatePassword('Test123!');
            $invalid = validatePassword('weak');
            return $this->assert($valid && !$invalid, 'validatePassword');
        };

        $this->tests['sanitizeString'] = function() {
            $input = '<script>alert("test")</script>';
            $sanitized = sanitizeString($input);
            return $this->assert($sanitized === '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;', 'sanitizeString');
        };

        $this->tests['truncateText'] = function() {
            $text = 'This is a very long text that needs to be truncated';
            $truncated = truncateText($text, 20);
            return $this->assert(strlen($truncated) === 23 && substr($truncated, -3) === '...', 'truncateText');
        };

        $this->tests['isFutureDate'] = function() {
            $future = date('Y-m-d H:i:s', strtotime('+1 day'));
            $past = date('Y-m-d H:i:s', strtotime('-1 day'));
            return $this->assert(isFutureDate($future) && !isFutureDate($past), 'isFutureDate');
        };

        $this->tests['calculateDuration'] = function() {
            $start = '2024-03-20 10:00:00';
            $end = '2024-03-20 12:30:00';
            $duration = calculateDuration($start, $end);
            return $this->assert($duration === '02 heures 30 minutes', 'calculateDuration');
        };

        // Test de la base de données (seulement si disponible)
        if ($this->dbAvailable) {
            $this->tests['databaseConnection'] = function() {
                try {
                    $this->db->query('SELECT 1');
                    return $this->assert(true, 'databaseConnection');
                } catch (Exception $e) {
                    return $this->assert(false, 'databaseConnection');
                }
            };
        }

        // Test des sessions
        $this->tests['sessionManagement'] = function() {
            Session::start();
            Session::set('test_key', 'test_value');
            $value = Session::get('test_key');
            Session::remove('test_key');
            return $this->assert($value === 'test_value', 'sessionManagement');
        };
    }

    private function assert($condition, $testName) {
        if ($condition) {
            $this->passed++;
            echo "✅ Test passed: $testName\n";
            return true;
        } else {
            $this->failed++;
            echo "❌ Test failed: $testName\n";
            return false;
        }
    }

    public function run() {
        echo "Starting test suite...\n\n";
        
        foreach ($this->tests as $name => $test) {
            try {
                $test();
            } catch (Exception $e) {
                $this->failed++;
                echo "❌ Test failed with exception: $name\n";
                echo "Error: " . $e->getMessage() . "\n";
            }
        }

        echo "\nTest Summary:\n";
        echo "Total tests: " . count($this->tests) . "\n";
        echo "Passed: $this->passed\n";
        echo "Failed: $this->failed\n";
    }
}

// Exécution des tests
$testSuite = new TestSuite();
$testSuite->run(); 