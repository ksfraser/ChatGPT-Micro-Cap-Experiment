<?php
// Test custom authentication exceptions
echo "Testing custom authentication exceptions\n";

try {
    // Set up mock environment
    $_SERVER['REQUEST_URI'] = '/test';
    $_SERVER['HTTP_HOST'] = 'localhost';
    
    echo "Including auth_check.php with custom exceptions\n";
    include 'web_ui/auth_check.php';
    
    echo "Authentication passed - user is logged in\n";
    
} catch (LoginRequiredException $e) {
    echo "LoginRequiredException caught: " . $e->getMessage() . "\n";
    echo "Redirect URL: " . $e->getRedirectUrl() . "\n";
    echo "Should redirect: " . ($e->shouldRedirect() ? 'Yes' : 'No') . "\n";
} catch (AdminRequiredException $e) {
    echo "AdminRequiredException caught: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
} catch (SessionException $e) {
    echo "SessionException caught: " . $e->getMessage() . "\n";
    echo "Redirect URL: " . $e->getRedirectUrl() . "\n";
} catch (AuthenticationException $e) {
    echo "AuthenticationException caught: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Exception caught: " . $e->getMessage() . "\n";
}

echo "Test completed successfully\n";
?>
