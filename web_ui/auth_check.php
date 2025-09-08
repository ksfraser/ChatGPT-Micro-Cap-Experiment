<?php
/**
 * Auth Check - Simple authentication wrapper for pages
 * Include this at the top of pages that require authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    
    // Check if user is logged in
    if (!$userAuth->isLoggedIn()) {
        // Redirect to login page
        $currentPage = $_SERVER['REQUEST_URI'] ?? '';
        $loginUrl = 'login.php';
        
        // Add return URL parameter if not already on login page
        if (!empty($currentPage) && !strpos($currentPage, 'login.php')) {
            $loginUrl .= '?return=' . urlencode($currentPage);
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }
    
    // Make user data available to the page
    $currentUser = $userAuth->getCurrentUser();
    
} catch (Exception $e) {
    // If there's an authentication error, log it and redirect to login
    error_log('Authentication error: ' . $e->getMessage());
    header('Location: login.php?error=auth_error');
    exit;
}

// Function to check if current user is admin
function requireAdmin() {
    global $userAuth;
    if (!$userAuth->isAdmin()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body>';
        echo '<h1>Access Denied</h1>';
        echo '<p>You need administrator privileges to access this page.</p>';
        echo '<p><a href="index.php">Return to Dashboard</a></p>';
        echo '</body></html>';
        exit;
    }
}

// Function to get current user safely
function getCurrentUser() {
    global $currentUser;
    return $currentUser;
}

// Function to check if current user is admin (non-fatal)
function isCurrentUserAdmin() {
    global $userAuth;
    return $userAuth->isAdmin();
}
?>
