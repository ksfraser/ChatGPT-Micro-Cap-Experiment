<?php
/**
 * Enhanced Trading System Dashboard - Compatible with existing F30 Apache auth
 * 
 * Uses the same authentication system that works for the admin dashboard
 */

// Enable error reporting for debugging in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the UI rendering system
require_once 'UiRenderer.php';

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
    
    public function __construct() {
        $this->initializeAuthentication();
    }
    
    private function initializeAuthentication() {
        try {
            // Use the same authentication method that works for admin dashboard
            if (!headers_sent()) {
                // Start session like auth_check.php does
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
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
                
            } else {
                throw new Exception('Headers already sent - cannot initialize session');
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
 * Menu Service - Single Responsibility for menu generation
 */
class MenuService {
    public static function getMenuItems($currentPage, $isAdmin, $isAuthenticated) {
        $items = [];
        
        if ($isAuthenticated) {
            $items[] = [
                'url' => 'index.php',
                'label' => '🏠 Dashboard',
                'active' => $currentPage === 'dashboard'
            ];
            
            $items[] = [
                'url' => 'portfolios.php',
                'label' => '📈 Portfolios',
                'active' => $currentPage === 'portfolios'
            ];
            
            $items[] = [
                'url' => 'trades.php',
                'label' => '📋 Trades',
                'active' => $currentPage === 'trades'
            ];
            
            $items[] = [
                'url' => 'analytics.php',
                'label' => '📊 Analytics',
                'active' => $currentPage === 'analytics'
            ];
            
            if ($isAdmin) {
                $items[] = [
                    'url' => 'admin_users.php',
                    'label' => '👥 Users',
                    'active' => $currentPage === 'users',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'system_status.php',
                    'label' => '⚙️ System',
                    'active' => $currentPage === 'system',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'database.php',
                    'label' => '🗄️ Database',
                    'active' => $currentPage === 'database',
                    'admin_only' => true
                ];
            }
        }
        
        return $items;
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
        
        $navigation = UiFactory::createNavigationComponent(
            $title,
            'dashboard',
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
        
        $components = $this->contentService->createDashboardComponents();
        
        $pageRenderer = UiFactory::createPageRenderer(
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
    $errorNavigation = UiFactory::createNavigationComponent(
        'System Error',
        'error',
        ['username' => 'Guest'],
        false,
        [],
        false
    );
    
    $errorCard = UiFactory::createCardComponent(
        'System Error',
        'error',
        '<p>The system encountered an error. Please try again later.</p>' .
        '<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #dc3545;">' .
        '<strong>Error Details:</strong><br>' .
        'Message: ' . htmlspecialchars($e->getMessage()) . '<br>' .
        'File: ' . htmlspecialchars($e->getFile()) . '<br>' .
        'Line: ' . $e->getLine() .
        '</div>' .
        '<div style="margin-top: 20px;">' .
        '<a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Try Again</a>' .
        '</div>'
    );
    
    $pageRenderer = UiFactory::createPageRenderer(
        'System Error - Enhanced Trading System',
        $errorNavigation,
        [$errorCard]
    );
    
    echo $pageRenderer->render();
} catch (Error $e) {
    // Use UiRenderer for fatal error pages
    $errorNavigation = UiFactory::createNavigationComponent(
        'Fatal Error',
        'fatal_error',
        ['username' => 'Guest'],
        false,
        [],
        false
    );
    
    $fatalErrorCard = UiFactory::createCardComponent(
        'Fatal System Error',
        'fatal',
        '<p>The system encountered a fatal error. Please contact support.</p>' .
        '<div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107;">' .
        '<strong>Fatal Error Details:</strong><br>' .
        'Message: ' . htmlspecialchars($e->getMessage()) . '<br>' .
        'File: ' . htmlspecialchars($e->getFile()) . '<br>' .
        'Line: ' . $e->getLine() .
        '</div>' .
        '<div style="margin-top: 20px;">' .
        '<a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Reload Page</a>' .
        '</div>'
    );
    
    $pageRenderer = UiFactory::createPageRenderer(
        'Fatal Error - Enhanced Trading System',
        $errorNavigation,
        [$fatalErrorCard]
    );
    
    echo $pageRenderer->render();
}
?>
