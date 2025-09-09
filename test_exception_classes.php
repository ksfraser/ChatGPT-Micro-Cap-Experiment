<?php
// Test just the custom exception classes
echo "Testing custom exception classes\n";

require_once 'web_ui/AuthExceptions.php';

echo "AuthExceptions loaded successfully\n";

// Test throwing and catching a LoginRequiredException
try {
    throw new LoginRequiredException('login.php?test=1', 'Test login required');
} catch (LoginRequiredException $e) {
    echo "LoginRequiredException works: " . $e->getMessage() . "\n";
    echo "Redirect URL: " . $e->getRedirectUrl() . "\n";
}

// Test throwing and catching an AdminRequiredException
try {
    throw new AdminRequiredException('Test admin required');
} catch (AdminRequiredException $e) {
    echo "AdminRequiredException works: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

echo "Custom exception classes work correctly\n";
?>
