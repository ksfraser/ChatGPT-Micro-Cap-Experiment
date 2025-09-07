<?php
// Test script to diagnose admin_brokerages issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing admin_brokerages dependencies...\n";

try {
    echo "1. Loading MidCapBankImportDAO...\n";
    require_once __DIR__ . '/web_ui/MidCapBankImportDAO.php';
    echo "   ✓ MidCapBankImportDAO loaded successfully\n";
    
    echo "2. Creating MidCapBankImportDAO instance...\n";
    $dao = new MidCapBankImportDAO();
    echo "   ✓ MidCapBankImportDAO instantiated successfully\n";
    
    echo "3. Testing getPdo() method...\n";
    $pdo = $dao->getPdo();
    echo "   ✓ getPdo() method works\n";
    
    if ($pdo) {
        echo "   ✓ PDO connection is available\n";
    } else {
        echo "   ⚠ PDO connection is null\n";
        $errors = $dao->getErrors();
        if (!empty($errors)) {
            echo "   Errors: " . implode(', ', $errors) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}
