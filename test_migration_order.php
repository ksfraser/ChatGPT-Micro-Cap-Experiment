<?php
require_once 'tests/MockPDO.php';

echo "Testing migration_order table...\n";

// Test the regex first
$sql = "INSERT INTO migration_order VALUES ('second')";
echo "Testing SQL: $sql\n";

if (preg_match('/VALUES\s*\(([^)]+)\)/i', $sql, $valueMatches)) {
    echo "VALUES match found: " . $valueMatches[1] . "\n";
    $values = $valueMatches[1];
    if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $values, $stringMatches)) {
        echo "String matches: ";
        print_r($stringMatches[1]);
    } else {
        echo "No string matches found\n";
    }
} else {
    echo "No VALUES match found\n";
}

$pdo = new MockPDO();

// Create table
echo "Creating table...\n";
$pdo->exec("CREATE TABLE IF NOT EXISTS migration_order (step TEXT)");

// Insert data
echo "Inserting 'second'...\n";
$pdo->exec("INSERT INTO migration_order VALUES ('second')");
echo "Inserting 'third'...\n";
$pdo->exec("INSERT INTO migration_order VALUES ('third')");

// Check what's in the table
$reflection = new ReflectionClass($pdo);
$tablesProperty = $reflection->getProperty('tables');
$tablesProperty->setAccessible(true);
$tables = $tablesProperty->getValue($pdo);
echo "migration_order table contents:\n";
print_r($tables['migration_order']);

echo "Done\n";
