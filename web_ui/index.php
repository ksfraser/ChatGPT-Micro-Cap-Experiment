<?php
/**
 * Enhanced Trading System Dashboard - Compatible with existing F30 Apache auth
 * 
 * Uses the same authentication system that works for the admin dashboard
 */

// Enable error reporting for debugging in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the UI rendering system - use namespace version directly
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
require_once 'SessionManager.php';
require_once 'MenuService.php';

// Use the namespaced UI Factory
use Ksfraser\UIRenderer\Factories\UiFactory;
use Ksfraser\UIRenderer\Contracts\ComponentInterface;

/**
 * Compatible Authentication Service using existing working auth system
 */
class CompatibleAuthenticationService {
    private $isAuthenticated = false;
    private $currentUser = null;
    private $isAdmin = false;
    private $authError = false;
    private $errorMessage = '';
    private $userAuth = null;
    private $sessionManager = null;
    
    public function __construct() {
        $this->initializeAuthentication();
    }
    
    private function initializeAuthentication() {
        try {
            // Use centralized SessionManager instead of manual session handling
            $this->sessionManager = SessionManager::getInstance();
            
            // Check if session is properly initialized
            if (!$this->sessionManager->isSessionActive()) {
                $error = $this->sessionManager->getInitializationError();
                if ($error) {
                    throw new Exception('Session initialization failed: ' . $error);
                }
            }
            
            // Use the existing UserAuthDAO that works with F30 Apache
            require_once __DIR__ . '/UserAuthDAO.php';
            $this->userAuth = new UserAuthDAO();
            
            // Check authentication status
            if ($this->userAuth->isLoggedIn()) {
                $this->isAuthenticated = true;
                $this->currentUser = $this->userAuth->getCurrentUser();
                $this->isAdmin = $this->userAuth->isAdmin();
            } else {
                // Not logged in, but don't redirect - just show guest mode
                $this->isAuthenticated = false;
                $this->currentUser = ['username' => 'Guest'];
                $this->isAdmin = false;
            }
            
        } catch (Exception $e) {
            $this->handleAuthFailure($e);
        } catch (Error $e) {
            $this->handleAuthFailure($e);
        }
    }
    
    private function handleAuthFailure($error) {
        $this->authError = true;
        $this->currentUser = ['username' => 'Guest (Auth Error)'];
        $this->isAuthenticated = false;
        $this->isAdmin = false;
        $this->errorMessage = $error->getMessage();
        
        // Log the error
        error_log('Auth system failure in index.php: ' . $error->getMessage());
        error_log('Auth error file: ' . $error->getFile() . ':' . $error->getLine());
    }
    
    public function isAuthenticated() {
        return $this->isAuthenticated;
    }
    
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    public function isAdmin() {
        return $this->isAdmin;
    }
    
    public function hasAuthError() {
        return $this->authError;
    }
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    public function getUserAuth() {
        return $this->userAuth;
    }
}

/**
 * Dashboard Content Service - Single Responsibility for dashboard content
 */
class DashboardContentService {
    private $authService;
    
    public function __construct($authService) {
        $this->authService = $authService;
    }
    
    public function createDashboardComponents() {
        $components = [];
        
        // Header component
        $components[] = UiFactory::createCard(
            'Welcome to Your Trading Dashboard',
            'Manage your portfolios, track trades, and analyze performance across all market segments',
            'default',
            ''
        );
        
        // Database connectivity warning
        $components[] = UiFactory::createCard(
            '⚠️ Database Connectivity Notice',
            'The system is currently running in fallback mode without database connectivity. The external database server may be unreachable. Core functionality is available but authentication and data persistence are limited.',
            'warning',
            '⚠️',
            [
                ['url' => 'debug_500.php', 'label' => 'Run Diagnostics', 'class' => 'btn btn-warning', 'icon' => '🔧'],
                ['url' => 'test_db_simple.php', 'label' => 'Test Database', 'class' => 'btn', 'icon' => '🗄️']
            ]
        );
        
        // Main feature cards
        $components[] = $this->createFeatureGrid();
        
        // Quick actions
        $components[] = $this->createQuickActionsCard();
        
        // System information
        $components[] = $this->createSystemInfoCard();
        
        return $components;
    }
    
    private function createFeatureGrid() {
        $gridHtml = '<div class="grid">
            <div class="card success">
                <h3>📈 Portfolio Overview</h3>
                <p>Monitor your investment performance across all market cap segments</p>
                <a href="portfolios.php" class="btn btn-success">View Portfolios</a>
            </div>

            <div class="card info">
                <h3>📋 Trade History</h3>
                <p>Review your trading activity and transaction history</p>
                <a href="trades.php" class="btn">View Trades</a>
            </div>

            <div class="card info">
                <h3>📊 Analytics & Reports</h3>
                <p>Analyze your trading patterns and portfolio performance</p>
                <a href="analytics.php" class="btn">View Analytics</a>
            </div>
        </div>';
        
        // Create a simple component that returns the grid HTML
        return new class($gridHtml) implements ComponentInterface {
            private $html;
            public function __construct($html) { $this->html = $html; }
            public function toHtml() { return $this->html; }
        };
    }
    
    private function createQuickActionsCard() {
        $actions = [
            ['url' => 'portfolios.php', 'label' => 'Add Portfolio', 'class' => 'btn btn-success', 'icon' => '📈'],
            ['url' => 'trades.php', 'label' => 'Record Trade', 'class' => 'btn', 'icon' => '📋'],
            ['url' => 'debug_500.php', 'label' => 'System Diagnostics', 'class' => 'btn btn-warning', 'icon' => '🔧'],
            ['url' => 'network_test.php', 'label' => 'Network Test', 'class' => 'btn', 'icon' => '🌐']
        ];
        
        return UiFactory::createCard(
            '🎯 Quick Actions',
            'Access frequently used features and system management',
            'default',
            '🎯',
            $actions
        );
    }
    
    private function createSystemInfoCard() {
        $user = $this->authService->getCurrentUser();
        $authStatus = '⚠️ Mock Authentication (Database unavailable)';
        $accessLevel = $this->authService->isAdmin() ? 'Administrator' : 'User';
        
        $content = '<strong>Database Architecture:</strong> Enhanced multi-driver system with PDO and MySQLi support<br>
                   <strong>User Management:</strong> Role-based access control with admin privileges<br>
                   <strong>Trading Features:</strong> Portfolio management, trade tracking, and performance analytics<br>
                   <strong>Authentication Status:</strong> ' . $authStatus . '<br>
                   <strong>Current User:</strong> ' . htmlspecialchars($user['username']) . '<br>
                   <strong>Access Level:</strong> ' . $accessLevel . '<br>
                   <strong>Mode:</strong> Fallback mode (no database connectivity)';
        
        return UiFactory::createCard(
            '📋 System Information',
            $content,
            'info',
            '📋'
        );
    }
}

/**
 * Main Application Controller - Compatible with existing F30 Apache auth
 */
class DashboardController {
    private $authService;
    private $contentService;
    
    public function __construct() {
        $this->authService = new CompatibleAuthenticationService();
        $this->contentService = new DashboardContentService($this->authService);
    }
    
    public function renderPage() {
        $user = $this->authService->getCurrentUser();
        $isAdmin = $this->authService->isAdmin();
        $isAuthenticated = $this->authService->isAuthenticated();
        
        $menuItems = MenuService::getMenuItems('dashboard', $isAdmin, $isAuthenticated);
        
        $title = $this->authService->hasAuthError() 
            ? 'Enhanced Trading System Dashboard (Auth Error)'
            : 'Enhanced Trading System Dashboard';
        
        $navigation = UiFactory::createNavigation(
            $title,
            'dashboard',
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
        
        $components = $this->contentService->createDashboardComponents();
        
        $pageRenderer = UiFactory::createPage(
            'Dashboard - Enhanced Trading System',
            $navigation,
            $components
        );
        
        return $pageRenderer->render();
    }
}

// Application Entry Point - Clean and simple
try {
    $controller = new DashboardController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Use UiRenderer for error pages instead of raw echo
    $errorNavigation = UiFactory::createNavigation(
        'System Error',
        'error',
        ['username' => 'Guest'],
        false,
        [],
        false
    );
    
    $errorCard = UiFactory::createCard(
        'System Error',
        '<p>The system encountered an error. Please try again later.</p>' .
        '<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #dc3545;">' .
        '<strong>Error Details:</strong><br>' .
        'Message: ' . htmlspecialchars($e->getMessage()) . '<br>' .
        'File: ' . htmlspecialchars($e->getFile()) . '<br>' .
        'Line: ' . $e->getLine() .
        '</div>' .
        '<div style="margin-top: 20px;">' .
        '<a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Try Again</a>' .
        '</div>',
        'error'
    );
    
    $pageRenderer = UiFactory::createPage(
        'System Error - Enhanced Trading System',
        $errorNavigation,
        [$errorCard]
    );
    
    echo $pageRenderer->render();
} catch (Error $e) {
    // Use UiRenderer for fatal error pages
    $errorNavigation = UiFactory::createNavigation(
        'Fatal Error',
        'fatal_error',
        ['username' => 'Guest'],
        false,
        [],
        false
    );
    
    $fatalErrorCard = UiFactory::createCard(
        'Fatal System Error',
        '<p>The system encountered a fatal error. Please contact support.</p>' .
        '<div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107;">' .
        '<strong>Fatal Error Details:</strong><br>' .
        'Message: ' . htmlspecialchars($e->getMessage()) . '<br>' .
        'File: ' . htmlspecialchars($e->getFile()) . '<br>' .
        'Line: ' . $e->getLine() .
        '</div>' .
        '<div style="margin-top: 20px;">' .
        '<a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Reload Page</a>' .
        '</div>',
        'error'
    );
    
    $pageRenderer = UiFactory::createPage(
        'Fatal Error - Enhanced Trading System',
        $errorNavigation,
        [$fatalErrorCard]
    );
    
    echo $pageRenderer->render();
}
?>
