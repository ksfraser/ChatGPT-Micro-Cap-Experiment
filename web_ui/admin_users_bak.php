<?php
/**
 * Admin User Management Entry Point - Clean MVC Architecture
 * This file serves as the entry point and bootstraps the User Management Controller
 */

// Include authentication and UI system
// require_once 'auth_check.php';  // Temporarily commented for testing
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';

// Import the User Management Controller
use Ksfraser\User\Controllers\UserManagementController;

// For backward compatibility with existing UserAuthDAO methods
require_once __DIR__ . '/UserAuthDAO.php';

// Application Entry Point
try {
    $userAuth = new UserAuthDAO();
    $controller = new UserManagementController($userAuth);
    echo $controller->renderPage();
} catch (Exception $e) {
    echo UserManagementController::renderErrorPage($e->getMessage());
}
?>
    private $userAuth;
    private $currentUser;
    private $isAdmin;
    private $message;
    private $messageType;
    
    public function __construct($userAuth, $currentUser, $isAdmin) {
        $this->userAuth = $userAuth;
        $this->currentUser = $currentUser;
        $this->isAdmin = $isAdmin;
        $this->message = '';
        $this->messageType = '';
        
        // Handle form submissions
        $this->handleFormSubmissions();
    }
    
    /**
     * Handle form submissions using User namespace classes
     */
    private function handleFormSubmissions() {
        // Temporarily disabled for testing
        /*
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $request = new UserManagementRequest();
            
            if ($request->hasAction()) {
                $service = new UserManagementService($this->userAuth, $this->currentUser);
                
                if ($request->isCreateAction()) {
                    list($this->message, $this->messageType) = $service->createUser($request);
                } elseif ($request->isDeleteAction()) {
                    list($this->message, $this->messageType) = $service->deleteUser($request);
                } elseif ($request->isToggleAdminAction()) {
                    list($this->message, $this->messageType) = $service->toggleAdminStatus($request);
                }
            }
        }
        */
    }
    
    /**
     * Create user management dashboard components
     */
    public function createUserManagementComponents() {
        $components = [];
        
        // Message alert if any
        if ($this->message) {
            $components[] = $this->createMessageCard();
        }
        
        // User statistics
        $components[] = $this->createUserStatsCard();
        
        // Create new user form
        $components[] = $this->createNewUserForm();
        
        // User list table
        $components[] = $this->createUserListCard();
        
        return $components;
    }
    
    /**
     * Create message alert card
     */
    private function createMessageCard() {
        if ($this->messageType === 'success') {
            return UiFactory::createSuccessCard('Success', $this->message);
        } else {
            return UiFactory::createErrorCard('Error', $this->message);
        }
    }
    
    /**
     * Create user statistics cards
     */
    private function createUserStatsCard() {
        $totalUsers = $this->userAuth->getUserCount();
        $adminUsers = $this->userAuth->getAdminCount();
        $activeUsers = $this->userAuth->getActiveUserCount();
        
        $statsHtml = '
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number">' . $totalUsers . '</div>
                    <div>Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $adminUsers . '</div>
                    <div>Admin Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $activeUsers . '</div>
                    <div>Active Users</div>
                </div>
            </div>
        ';
        
        return UiFactory::createCard('User Statistics', $statsHtml);
    }
    
    /**
     * Create new user form
     */
    private function createNewUserForm() {
        $formHtml = '
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_admin" value="1"> Admin User
                    </label>
                </div>
                <button type="submit" name="action" value="create" class="btn">Create User</button>
            </form>
        ';
        
        return UiFactory::createCard('Create New User', $formHtml);
    }
    
    /**
     * Create user list table
     */
    private function createUserListCard() {
        $users = $this->userAuth->getAllUsers();
        
        if (empty($users)) {
            return UiFactory::createCard('Existing Users', '<p>No users found.</p>');
        }
        
        // Prepare table data
        $tableData = [];
        $headers = ['Username', 'Email', 'Status', 'Actions'];
        
        foreach ($users as $user) {
            $status = $user['is_admin'] ? '<span class="admin-badge">Admin</span>' : 'User';
            $actions = $this->createUserActionsHtml($user);
            
            $tableData[] = [
                htmlspecialchars($user['username']),
                htmlspecialchars($user['email']),
                $status,
                $actions
            ];
        }
        
        return UiFactory::createDataCard('Existing Users', $tableData, $headers);
    }
    
    /**
     * Create action buttons HTML for user row
     */
    private function createUserActionsHtml($user) {
        if ($user['id'] == $this->currentUser['id']) {
            return '<em>Current User</em>';
        }
        
        $actions = '';
        
        // Toggle admin status
        if ($user['is_admin']) {
            $actions .= '
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="' . $user['id'] . '">
                    <button type="submit" name="action" value="toggle_admin" class="btn btn-warning" 
                            onclick="return confirm(\'Remove admin privileges from ' . htmlspecialchars($user['username']) . '?\')">
                        Remove Admin
                    </button>
                </form>
            ';
        } else {
            $actions .= '
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="' . $user['id'] . '">
                    <button type="submit" name="action" value="toggle_admin" class="btn">
                        Make Admin
                    </button>
                </form>
            ';
        }
        
        // Delete user
        $actions .= '
            <form method="POST" style="display: inline;">
                <input type="hidden" name="user_id" value="' . $user['id'] . '">
                <button type="submit" name="action" value="delete" class="btn btn-danger" 
                        onclick="return confirm(\'Are you sure you want to delete user ' . htmlspecialchars($user['username']) . '?\')">
                    Delete
                </button>
            </form>
        ';
        
        return '<div class="actions">' . $actions . '</div>';
    }
}

/**
 * User Management Controller - Following analytics.php pattern
 */
class UserManagementController {
    private $contentService;
    private $currentUser;
    private $isAdmin;
    
    public function __construct() {
        // Simplified for testing - no auth dependencies
        $this->currentUser = ['username' => 'test', 'id' => 1];
        $this->isAdmin = true;
        $this->contentService = null; // Don't create for now
    }
    
    /**
     * Render the user management page
     */
    public function renderPage() {
        // Check authentication
        if (!$this->currentUser) {
            $errorNavigation = UiFactory::createNavigation(
                'Access Denied - User Management',
                'error',
                null,
                false,
                [],
                false
            );
            
            $errorCard = UiFactory::createErrorCard(
                'Access Denied',
                '<p>Please log in to access the user management dashboard.</p>' .
                '<div style="margin-top: 15px;"><a href="login.php" class="btn">Login</a></div>'
            );
            
            $pageRenderer = UiFactory::createPage(
                'Access Denied - User Management',
                $errorNavigation,
                [$errorCard]
            );
            
            return $pageRenderer->render();
        }
        
        if (!$this->isAdmin) {
            $errorNavigation = UiFactory::createNavigation(
                'Access Denied - User Management',
                'admin_users',
                $this->currentUser,
                false,
                [],
                true
            );
            
            $errorCard = UiFactory::createErrorCard(
                'Admin Access Required',
                '<p>You need administrator privileges to access the user management dashboard.</p>' .
                '<div style="margin-top: 15px;"><a href="index.php" class="btn">Return to Portfolio</a></div>'
            );
            
            $pageRenderer = UiFactory::createPage(
                'Access Denied - User Management',
                $errorNavigation,
                [$errorCard]
            );
            
            return $pageRenderer->render();
        }
        
        // Create menu service
        $menuItems = []; // Simplified for testing
        
        // Generate page components - simplified
        $components = [UiFactory::createCard('User Management', 'This is the user management system.')];
        
        // Create navigation
        $navigation = UiFactory::createNavigation(
            'User Management Dashboard',
            'admin_users',
            $this->currentUser,
            $this->isAdmin,
            $menuItems,
            true
        );
        
        // Create page layout
        $pageRenderer = UiFactory::createPage(
            'User Management Dashboard - Enhanced Trading System',
            $navigation,
            $components
        );
        
        return $pageRenderer->render();
    }
}

// Application Entry Point
try {
    $controller = new UserManagementController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Use UiRenderer for error pages
    $errorNavigation = UiFactory::createNavigation(
        'Error - User Management',
        'error',
        null,
        false,
        [],
        false
    );
    
    $errorCard = UiFactory::createErrorCard(
        'User Management System Error',
        '<p>The user management system encountered an error.</p>' .
        '<div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">' .
        '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . 
        '</div>' .
        '<div style="margin-top: 15px;">' .
        '<a href="index.php" class="btn">Return to Portfolio</a>' .
        '</div>'
    );
    
    $pageRenderer = UiFactory::createPage(
        'Error - User Management System',
        $errorNavigation,
        [$errorCard]
    );
    
    echo $pageRenderer->render();
}
?>
} catch (Exception $e) {
    $users = [];
    $message = 'Error loading users: ' . $e->getMessage();
    $messageType = 'error';
}

$currentUser = $userAuth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Enhanced Trading System</title>
    <style>
        <?php 
        // Include NavigationManager for consistent navigation
        require_once 'NavigationManager.php';
        $navManager = new NavigationManager();
        echo $navManager->getNavigationCSS(); 
        
        // Add basic CSS for user management
        echo CSSManager::getBaseCSS();
        echo CSSManager::getFormCSS();
        echo CSSManager::getTableCSS();
        echo CSSManager::getCardCSS();
        echo CSSManager::getUtilityCSS();
        ?>
    </style>
</head>
<body>

<?php 
// Render navigation with MenuService integration
require_once 'NavigationManager.php';
$navManager = new NavigationManager();
$navManager->renderNavigationHeader('User Management', 'users');

// Add admin menu items for quick access
$isAdminUser = $userAuth->isAdmin();
$menuItems = MenuService::getMenuItems('users', $isAdminUser, true);

// Display quick access links for admin users
if ($isAdminUser && !empty($menuItems)) {
    echo '<div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
    echo '<strong>Quick Access:</strong> ';
    foreach ($menuItems as $item) {
        if (isset($item['admin_only']) && $item['admin_only'] && !$item['active']) {
            echo '<a href="' . htmlspecialchars($item['url']) . '" style="margin-right: 10px; color: #007cba; text-decoration: none;">' . htmlspecialchars($item['label']) . '</a>';
        }
    }
    echo '</div>';
}
?>

<div class="container">
    <h1>👥 User Management</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- User Statistics -->
    <div class="management-section">
        <h2>User Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($users, function($u) { return $u['is_admin']; })); ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($users, function($u) { return !$u['is_admin']; })); ?></div>
                <div class="stat-label">Regular Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($users, function($u) { return !empty($u['last_login']); })); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
    </div>
    
    <!-- Create New User -->
    <div class="management-section">
        <h2>Create New User</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required minlength="3" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_admin"> Administrator
                </label>
            </div>
            <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
        </form>
    </div>
    
    <!-- User List -->
    <div class="management-section">
        <h2>All Users</h2>
        <?php if (empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($user['username']); ?>
                                <?php if ($user['id'] === $currentUser['id']): ?>
                                    <small>(You)</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="admin-badge">Admin</span>
                                <?php else: ?>
                                    User
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?php echo date('M j, Y H:i', strtotime($user['last_login'])); ?>
                                <?php else: ?>
                                    <em>Never</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] !== $currentUser['id']): ?>
                                    <!-- Toggle Admin Status -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php if (!$user['is_admin']): ?>
                                            <input type="hidden" name="is_admin" value="1">
                                            <button type="submit" name="toggle_admin" class="btn btn-success btn-small" 
                                                    onclick="return confirm('Promote this user to administrator?')">
                                                ⬆️ Make Admin
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="is_admin" value="0">
                                            <button type="submit" name="toggle_admin" class="btn btn-warning btn-small"
                                                    onclick="return confirm('Remove admin privileges and make this user a regular user?')">
                                                ⬇️ Make Regular User
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <!-- Delete User -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-small"
                                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <em>Current User</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="management-section">
        <?php require_once 'QuickActions.php'; QuickActions::render(); ?>
    </div>
</div>

</body>
</html>
