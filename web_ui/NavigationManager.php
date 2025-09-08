<?php
/**
 * Navigation Manager - RBAC-based Navigation System
 * Centralized navigation management with role-based access control
 */

class NavigationManager {
    private $userAuth;
    private $currentUser;
    private $isLoggedIn;
    private $isAdmin;
    
    public function __construct() {
        // Initialize authentication - handle sessions carefully
        if (session_status() === PHP_SESSION_NONE) {
            // Only start session if no output has been sent
            if (!headers_sent()) {
                session_start();
            }
        }
        
        try {
            require_once __DIR__ . '/UserAuthDAO.php';
            $this->userAuth = new UserAuthDAO();
            $this->isLoggedIn = $this->userAuth->isLoggedIn();
            $this->currentUser = $this->isLoggedIn ? $this->userAuth->getCurrentUser() : null;
            $this->isAdmin = $this->isLoggedIn && $this->userAuth->isAdmin();
        } catch (Exception $e) {
            // Gracefully handle auth failures
            $this->userAuth = null;
            $this->isLoggedIn = false;
            $this->currentUser = null;
            $this->isAdmin = false;
        }
    }
    
    /**
     * Get navigation CSS styles
     */
    public function getNavigationCSS() {
        return '
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-header.admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .nav-links a.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        .admin-badge {
            background: #ffffff;
            color: #dc3545;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #ffffff;
        }
        .nav-header.admin .admin-badge {
            background: #ffffff;
            color: #dc3545;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .user-info {
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }';
    }
    
    /**
     * Get navigation menu items based on user role
     */
    public function getNavigationItems($currentPage = '') {
        $items = [];
        
        if ($this->isLoggedIn) {
            // Core navigation items for all users
            $items[] = [
                'url' => 'index.php',
                'label' => '🏠 Dashboard',
                'icon' => '🏠',
                'active' => $currentPage === 'index.php' || $currentPage === 'dashboard'
            ];
            
            $items[] = [
                'url' => 'portfolios.php',
                'label' => '📈 Portfolios',
                'icon' => '📈',
                'active' => $currentPage === 'portfolios.php' || $currentPage === 'portfolios'
            ];
            
            $items[] = [
                'url' => 'trades.php',
                'label' => '📋 Trades',
                'icon' => '📋',
                'active' => $currentPage === 'trades.php' || $currentPage === 'trades'
            ];
            
            $items[] = [
                'url' => 'analytics.php',
                'label' => '📊 Analytics',
                'icon' => '📊',
                'active' => $currentPage === 'analytics.php' || $currentPage === 'analytics'
            ];
            
            // Admin-only navigation items
            if ($this->isAdmin) {
                $items[] = [
                    'url' => 'admin_users.php',
                    'label' => '👥 Users',
                    'icon' => '👥',
                    'active' => $currentPage === 'admin_users.php' || $currentPage === 'users',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'system_status.php',
                    'label' => '⚙️ System',
                    'icon' => '⚙️',
                    'active' => $currentPage === 'system_status.php' || $currentPage === 'system',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'database.php',
                    'label' => '🗄️ Database',
                    'icon' => '🗄️',
                    'active' => $currentPage === 'database.php' || $currentPage === 'database',
                    'admin_only' => true
                ];
            }
        } else {
            // Public navigation items
            $items[] = [
                'url' => 'login.php',
                'label' => '🔐 Login',
                'icon' => '🔐',
                'active' => $currentPage === 'login.php' || $currentPage === 'login'
            ];
            
            $items[] = [
                'url' => 'register.php',
                'label' => '📝 Register',
                'icon' => '📝',
                'active' => $currentPage === 'register.php' || $currentPage === 'register'
            ];
        }
        
        return $items;
    }
    
    /**
     * Render the complete navigation header
     */
    public function renderNavigationHeader($pageTitle = 'Enhanced Trading System', $currentPage = '') {
        $adminClass = $this->isAdmin ? ' admin' : '';
        $navigationItems = $this->getNavigationItems($currentPage);
        
        echo '<div class="nav-header' . $adminClass . '">';
        echo '<div class="nav-container">';
        echo '<h1 class="nav-title">' . htmlspecialchars($pageTitle) . '</h1>';
        
        echo '<div class="nav-user">';
        
        // Navigation links
        echo '<div class="nav-links">';
        foreach ($navigationItems as $item) {
            $activeClass = $item['active'] ? ' active' : '';
            $adminIndicator = isset($item['admin_only']) ? ' title="Admin Only"' : '';
            echo '<a href="' . htmlspecialchars($item['url']) . '" class="nav-link' . $activeClass . '"' . $adminIndicator . '>';
            echo htmlspecialchars($item['label']);
            echo '</a>';
        }
        
        // Logout link for logged in users
        if ($this->isLoggedIn) {
            echo '<a href="logout.php" class="nav-link">🚪 Logout</a>';
        }
        echo '</div>';
        
        // User info section
        if ($this->isLoggedIn && $this->currentUser) {
            echo '<div class="user-info">';
            echo '<span>👤 ' . htmlspecialchars($this->currentUser['username']) . '</span>';
            if ($this->isAdmin) {
                echo ' <span class="admin-badge">ADMIN</span>';
            }
            echo '</div>';
        }
        
        echo '</div>'; // nav-user
        echo '</div>'; // nav-container
        echo '</div>'; // nav-header
    }
    
    /**
     * Check if user has access to a specific page/feature
     */
    public function hasAccess($feature) {
        switch ($feature) {
            case 'admin_users':
            case 'system_status':
            case 'database':
            case 'user_management':
                return $this->isAdmin;
                
            case 'portfolios':
            case 'trades':
            case 'analytics':
            case 'dashboard':
                return $this->isLoggedIn;
                
            case 'login':
            case 'register':
                return !$this->isLoggedIn;
                
            default:
                return false;
        }
    }
    
    /**
     * Get user information
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return $this->isLoggedIn;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isAdmin;
    }
    
    /**
     * Get quick actions based on user role
     */
    public function getQuickActions() {
        $actions = [];
        
        if ($this->isLoggedIn) {
            $actions[] = [
                'url' => 'portfolios.php',
                'label' => 'Add Portfolio',
                'class' => 'btn btn-success',
                'icon' => '📈'
            ];
            
            $actions[] = [
                'url' => 'trades.php',
                'label' => 'Record Trade',
                'class' => 'btn',
                'icon' => '📋'
            ];
            
            if ($this->isAdmin) {
                $actions[] = [
                    'url' => 'admin_users.php',
                    'label' => 'Manage Users',
                    'class' => 'btn btn-warning',
                    'icon' => '👥'
                ];
                
                $actions[] = [
                    'url' => 'database.php',
                    'label' => 'Database Tools',
                    'class' => 'btn',
                    'icon' => '🗄️'
                ];
            }
        }
        
        return $actions;
    }
}

// Global navigation instance - lazy loaded
$navManager = null;

// Helper function to get NavigationManager instance
function getNavManager() {
    global $navManager;
    if ($navManager === null) {
        $navManager = new NavigationManager();
    }
    return $navManager;
}

// Helper functions for backward compatibility
function renderNavigationHeader($pageTitle = 'Enhanced Trading System', $currentPage = '') {
    $navManager = getNavManager();
    $navManager->renderNavigationHeader($pageTitle, $currentPage);
}

function getCurrentUser() {
    $navManager = getNavManager();
    return $navManager->getCurrentUser();
}

function isCurrentUserAdmin() {
    $navManager = getNavManager();
    return $navManager->isAdmin();
}

function isLoggedIn() {
    $navManager = getNavManager();
    return $navManager->isLoggedIn();
}
?>
