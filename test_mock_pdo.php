<?php
require_once 'tests/MockPDO.php';

echo "Testing MockPDO...\n";

$pdo = new MockPDO();
echo "MockPDO created successfully\n";

// Test table creation
echo "Creating table...\n";
$result = $pdo->exec("CREATE TABLE test_table (id INT PRIMARY KEY)");
echo "Table creation returned: " . $result . "\n";

// Test SHOW TABLES for schema_migrations (should exist by default)
echo "Testing SHOW TABLES for schema_migrations...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
echo "Statement created, executing...\n";
$executeResult = $stmt->execute();
echo "Execute result: " . ($executeResult ? "true" : "false") . "\n";
$result = $stmt->fetch();
echo "schema_migrations fetch result: " . print_r($result, true) . "\n";

// Test SHOW TABLES for test_table
echo "Testing SHOW TABLES for test_table...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'test_table'");
$executeResult = $stmt->execute();
echo "Execute result: " . ($executeResult ? "true" : "false") . "\n";
$result = $stmt->fetch();
echo "test_table fetch result: " . print_r($result, true) . "\n";

echo "MockPDO test complete\n";
