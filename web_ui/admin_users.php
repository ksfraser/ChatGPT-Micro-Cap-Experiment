<?php
/**
 * Admin User Management - Manage system users
 */

require_once __DIR__ . '/UserAuthDAO.php';

// Check if user is admin
$userAuth = new UserAuthDAO();
$userAuth->requireAdmin();

$currentUser = $userAuth->getCurrentUser();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $isAdmin = isset($_POST['is_admin']);
        
        try {
            $userId = $userAuth->registerUser($username, $email, $password, $isAdmin);
            if ($userId) {
                $message = 'User created successfully with ID: ' . $userId;
                $messageType = 'success';
            } else {
                $message = 'Failed to create user';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error creating user: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_user'])) {
        $userId = (int)$_POST['user_id'];
        $currentUser = $userAuth->getCurrentUser();
        
        if ($userId === $currentUser['id']) {
            $message = 'Cannot delete your own account!';
            $messageType = 'error';
        } else {
            try {
                if ($userAuth->deleteUser($userId)) {
                    $message = 'User deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete user.';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = 'Error deleting user: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['toggle_admin'])) {
        $userId = (int)$_POST['user_id'];
        $isAdmin = isset($_POST['is_admin']);
        $currentUser = $userAuth->getCurrentUser();
        
        if ($userId === $currentUser['id']) {
            $message = 'Cannot modify your own admin status!';
            $messageType = 'error';
        } else {
            try {
                if ($userAuth->updateUserAdminStatus($userId, $isAdmin)) {
                    $message = 'User admin status updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update user admin status.';
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = 'Error updating user: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get all users
try {
    $users = $userAuth->getAllUsers();
} catch (Exception $e) {
    $users = [];
    $message = 'Error loading users: ' . $e->getMessage();
    $messageType = 'error';
}

$currentUser = $userAuth->getCurrentUser();
?>

<style>
    .user-management {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .management-section {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .user-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .user-table th,
    .user-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .user-table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    
    .user-table tr:hover {
        background-color: #f5f5f5;
    }
    
    .admin-badge {
        background: #dc3545;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }
    
    .status-active {
        color: #28a745;
        font-weight: bold;
    }
    
    .status-inactive {
        color: #dc3545;
        font-weight: bold;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-right: 5px;
    }
    
    .btn-primary {
        background: #007cba;
        color: white;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-warning {
        background: #ffc107;
        color: #212529;
    }
    
    .btn-small {
        padding: 4px 8px;
        font-size: 12px;
    }
    
    .alert {
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #007cba;
    }
    
    .stat-label {
        color: #666;
        font-size: 14px;
    }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
</head>
<body>

<div class="user-management">
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
                                            <button type="submit" name="toggle_admin" class="btn btn-warning btn-small" 
                                                    onclick="return confirm('Make this user an administrator?')">
                                                Make Admin
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="toggle_admin" class="btn btn-warning btn-small"
                                                    onclick="return confirm('Remove admin privileges from this user?')">
                                                Remove Admin
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
