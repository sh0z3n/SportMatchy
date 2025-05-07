<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\TextUI\Command;

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create test suites
$unitSuite = new PHPUnit\Framework\TestSuite('Unit Tests');
$integrationSuite = new PHPUnit\Framework\TestSuite('Integration Tests');
$e2eSuite = new PHPUnit\Framework\TestSuite('End-to-End Tests');

// Add test files
$unitSuite->addTestFile(__DIR__ . '/unit/test_events.php');
$integrationSuite->addTestFile(__DIR__ . '/integration/test_events_integration.php');
$e2eSuite->addTestFile(__DIR__ . '/e2e/test_events_e2e.php');

// Create main suite
$suite = new PHPUnit\Framework\TestSuite('SportMatchy Test Suite');
$suite->addTest($unitSuite);
$suite->addTest($integrationSuite);
$suite->addTest($e2eSuite);

// Run tests
$result = $suite->run();

// Output results
echo "\nTest Results Summary:\n";
echo "====================\n";
echo "Total Tests: " . $result->count() . "\n";
echo "Passed: " . ($result->count() - $result->failureCount() - $result->errorCount()) . "\n";
echo "Failed: " . $result->failureCount() . "\n";
echo "Errors: " . $result->errorCount() . "\n";

// Exit with appropriate status code
exit($result->wasSuccessful() ? 0 : 1); 