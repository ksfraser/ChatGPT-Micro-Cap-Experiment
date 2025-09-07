<?php
/**
 * Enhanced Bank Accounts Management System
 * 
 * Features:
 * - Table creation and setup workflow
 * - Prepopulation with sample Canadian bank accounts
 * - Full CRUD operations (Create, Read, Update, Delete)
 * - Integration with bank import functionality
 * - Enhanced error handling and validation
 * - Modern UI with consistent styling
 * 
 * Architecture:
 * - Extends EnhancedCommonDAO for modern database operations
 * - Uses SimpleValidators for input validation
 * - Integrates with existing schema migration system
 * - Follows existing centralized database patterns
 */

require_once __DIR__ . '/EnhancedCommonDAO.php';

class BankAccountsDAO extends EnhancedCommonDAO {
    
    public function __construct() {
        parent::__construct('LegacyDatabaseConfig');
    }
    
    /**
     * Check if bank_accounts table exists and is properly set up
     */
    public function checkTableStatus() {
        try {
            if (!$this->pdo) {
                return ['status' => 'no_connection', 'message' => 'Database connection not available'];
            }
            
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'bank_accounts'");
            if ($stmt->rowCount() === 0) {
                return ['status' => 'missing', 'message' => 'Bank accounts table does not exist'];
            }
            
            // Check if table has data
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM bank_accounts");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['count'];
            
            if ($count === 0) {
                return ['status' => 'empty', 'message' => 'Bank accounts table exists but is empty'];
            }
            
            return ['status' => 'ready', 'message' => "Bank accounts table ready with {$count} entries"];
            
        } catch (Exception $e) {
            $this->logError("Error checking table status: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error checking table status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create bank_accounts table using schema migration
     */
    public function createTable() {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Run schema migrations to ensure table is created
            require_once __DIR__ . '/SchemaMigrator.php';
            $schemaDir = __DIR__ . '/schema';
            $migrator = new SchemaMigrator($this->pdo, $schemaDir);
            $migrator->migrate();
            
            $this->logInfo("Bank accounts table created successfully");
            return true;
            
        } catch (Exception $e) {
            $this->logError("Error creating bank accounts table: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Prepopulate with sample Canadian bank accounts
     */
    public function prepopulateSampleAccounts() {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            $sampleAccounts = [
                ['bank' => 'Royal Bank of Canada', 'number' => 'SAMPLE-001', 'nickname' => 'RBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'TD Canada Trust', 'number' => 'SAMPLE-002', 'nickname' => 'TD Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Bank of Nova Scotia', 'number' => 'SAMPLE-003', 'nickname' => 'Scotia Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Bank of Montreal', 'number' => 'SAMPLE-004', 'nickname' => 'BMO Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Canadian Imperial Bank of Commerce', 'number' => 'SAMPLE-005', 'nickname' => 'CIBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'National Bank of Canada', 'number' => 'SAMPLE-006', 'nickname' => 'NBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Desjardins Group', 'number' => 'SAMPLE-007', 'nickname' => 'Desjardins Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'HSBC Bank Canada', 'number' => 'SAMPLE-008', 'nickname' => 'HSBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Laurentian Bank', 'number' => 'SAMPLE-009', 'nickname' => 'Laurentian Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Canadian Western Bank', 'number' => 'SAMPLE-010', 'nickname' => 'CWB Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Other', 'number' => 'SAMPLE-999', 'nickname' => 'Other Bank Sample', 'type' => 'Investment Account']
            ];
            
            $inserted = 0;
            foreach ($sampleAccounts as $account) {
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO bank_accounts (bank_name, account_number, account_nickname, account_type, currency) VALUES (?, ?, ?, ?, ?)');
                if ($stmt->execute([$account['bank'], $account['number'], $account['nickname'], $account['type'], 'CAD'])) {
                    if ($stmt->rowCount() > 0) {
                        $inserted++;
                    }
                }
            }
            
            $this->logInfo("Prepopulated {$inserted} sample bank accounts");
            return $inserted;
            
        } catch (Exception $e) {
            $this->logError("Error prepopulating bank accounts: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all bank accounts with optional search and pagination
     */
    public function getAllBankAccounts($search = '', $limit = 100, $offset = 0) {
        try {
            if (!$this->pdo) {
                return [];
            }
            
            $sql = "SELECT * FROM bank_accounts";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " WHERE bank_name LIKE ? OR account_number LIKE ? OR account_nickname LIKE ?";
                $searchTerm = "%{$search}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            $sql .= " ORDER BY bank_name, account_number LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Error getting bank accounts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of bank accounts (for pagination)
     */
    public function getBankAccountsCount($search = '') {
        try {
            if (!$this->pdo) {
                return 0;
            }
            
            $sql = "SELECT COUNT(*) as count FROM bank_accounts";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " WHERE bank_name LIKE ? OR account_number LIKE ? OR account_nickname LIKE ?";
                $searchTerm = "%{$search}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['count'];
            
        } catch (Exception $e) {
            $this->logError("Error counting bank accounts: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get bank account by ID
     */
    public function getBankAccountById($id) {
        try {
            if (!$this->pdo) {
                return null;
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE id = ?");
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Error getting bank account by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new bank account
     */
    public function createBankAccount($bankName, $accountNumber, $nickname = '', $accountType = '', $currency = 'CAD', $isActive = true) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Validate input
            $validator = new SimpleValidators();
            if (!$validator->validateRequired($bankName)) {
                throw new Exception('Bank name is required');
            }
            
            if (!$validator->validateRequired($accountNumber)) {
                throw new Exception('Account number is required');
            }
            
            if (!$validator->validateLength($bankName, 1, 128)) {
                throw new Exception('Bank name must be between 1 and 128 characters');
            }
            
            if (!$validator->validateLength($accountNumber, 1, 64)) {
                throw new Exception('Account number must be between 1 and 64 characters');
            }
            
            $stmt = $this->pdo->prepare('INSERT INTO bank_accounts (bank_name, account_number, account_nickname, account_type, currency, is_active) VALUES (?, ?, ?, ?, ?, ?)');
            if ($stmt->execute([$bankName, $accountNumber, $nickname, $accountType, $currency, $isActive ? 1 : 0])) {
                $id = $this->pdo->lastInsertId();
                $this->logInfo("Created bank account: {$bankName} - {$accountNumber} (ID: {$id})");
                return $id;
            }
            
            throw new Exception('Failed to create bank account');
            
        } catch (Exception $e) {
            $this->logError("Error creating bank account: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update bank account
     */
    public function updateBankAccount($id, $bankName, $accountNumber, $nickname = '', $accountType = '', $currency = 'CAD', $isActive = true) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Validate input
            $validator = new SimpleValidators();
            if (!$validator->validateRequired($bankName)) {
                throw new Exception('Bank name is required');
            }
            
            if (!$validator->validateRequired($accountNumber)) {
                throw new Exception('Account number is required');
            }
            
            if (!$validator->validateLength($bankName, 1, 128)) {
                throw new Exception('Bank name must be between 1 and 128 characters');
            }
            
            if (!$validator->validateLength($accountNumber, 1, 64)) {
                throw new Exception('Account number must be between 1 and 64 characters');
            }
            
            $stmt = $this->pdo->prepare('UPDATE bank_accounts SET bank_name = ?, account_number = ?, account_nickname = ?, account_type = ?, currency = ?, is_active = ? WHERE id = ?');
            if ($stmt->execute([$bankName, $accountNumber, $nickname, $accountType, $currency, $isActive ? 1 : 0, $id])) {
                $this->logInfo("Updated bank account ID {$id}: {$bankName} - {$accountNumber}");
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to update bank account');
            
        } catch (Exception $e) {
            $this->logError("Error updating bank account: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete bank account (with safety checks)
     */
    public function deleteBankAccount($id) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Check if bank account is in use in transactions
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM midcap_transactions mt JOIN bank_accounts ba ON mt.bank_name = ba.bank_name AND mt.account_number = ba.account_number WHERE ba.id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ((int)$result['count'] > 0) {
                throw new Exception('Cannot delete bank account: it is referenced by existing transactions');
            }
            
            $stmt = $this->pdo->prepare('DELETE FROM bank_accounts WHERE id = ?');
            if ($stmt->execute([$id])) {
                $this->logInfo("Deleted bank account ID: {$id}");
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to delete bank account');
            
        } catch (Exception $e) {
            $this->logError("Error deleting bank account: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get unique bank names for dropdown
     */
    public function getUniqueBankNames() {
        try {
            if (!$this->pdo) {
                return [];
            }
            
            $stmt = $this->pdo->query("SELECT DISTINCT bank_name FROM bank_accounts ORDER BY bank_name");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            $this->logError("Error getting unique bank names: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Find or suggest bank account from transaction data
     */
    public function findOrSuggestBankAccount($bankName, $accountNumber) {
        try {
            if (!$this->pdo) {
                return null;
            }
            
            // Exact match
            $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE bank_name = ? AND account_number = ?");
            $stmt->execute([$bankName, $accountNumber]);
            $exact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exact) {
                return ['type' => 'exact', 'account' => $exact];
            }
            
            // Partial bank name match
            $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE bank_name LIKE ? ORDER BY bank_name");
            $stmt->execute(["%{$bankName}%"]);
            $partial = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($partial)) {
                return ['type' => 'similar', 'accounts' => $partial];
            }
            
            return ['type' => 'new', 'suggested' => ['bank_name' => $bankName, 'account_number' => $accountNumber]];
            
        } catch (Exception $e) {
            $this->logError("Error finding bank account: " . $e->getMessage());
            return null;
        }
    }
}

// Initialize DAO
$dao = new BankAccountsDAO();
$tableStatus = $dao->checkTableStatus();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_table':
                    $dao->createTable();
                    $message = "✅ Bank accounts table created successfully!";
                    $tableStatus = $dao->checkTableStatus();
                    break;
                    
                case 'prepopulate':
                    $count = $dao->prepopulateSampleAccounts();
                    $message = "✅ Added {$count} sample Canadian bank accounts!";
                    $tableStatus = $dao->checkTableStatus();
                    break;
                    
                case 'create':
                    $bankName = trim($_POST['bank_name'] ?? '');
                    $accountNumber = trim($_POST['account_number'] ?? '');
                    $nickname = trim($_POST['nickname'] ?? '');
                    $accountType = trim($_POST['account_type'] ?? '');
                    $currency = trim($_POST['currency'] ?? 'CAD');
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    
                    $id = $dao->createBankAccount($bankName, $accountNumber, $nickname, $accountType, $currency, $isActive);
                    $message = "✅ Bank account '{$bankName} - {$accountNumber}' created successfully!";
                    break;
                    
                case 'update':
                    $id = (int)$_POST['id'];
                    $bankName = trim($_POST['bank_name'] ?? '');
                    $accountNumber = trim($_POST['account_number'] ?? '');
                    $nickname = trim($_POST['nickname'] ?? '');
                    $accountType = trim($_POST['account_type'] ?? '');
                    $currency = trim($_POST['currency'] ?? 'CAD');
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    
                    $dao->updateBankAccount($id, $bankName, $accountNumber, $nickname, $accountType, $currency, $isActive);
                    $message = "✅ Bank account updated successfully!";
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    $dao->deleteBankAccount($id);
                    $message = "✅ Bank account deleted successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Get bank accounts for display
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$bankAccounts = $dao->getAllBankAccounts($search, $limit, $offset);
$totalCount = $dao->getBankAccountsCount($search);
$totalPages = ceil($totalCount / $limit);

// Get single bank account for editing
$editAccount = null;
if (isset($_GET['edit'])) {
    $editAccount = $dao->getBankAccountById((int)$_GET['edit']);
}

$uniqueBanks = $dao->getUniqueBankNames();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Accounts Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .status-box { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .status-error { background: #ffeeee; border: 1px solid #cc0000; color: #cc0000; }
        .status-warning { background: #fff8dc; border: 1px solid #daa520; color: #daa520; }
        .status-success { background: #eeffee; border: 1px solid #00cc00; color: #00cc00; }
        .status-info { background: #eeeeff; border: 1px solid #0066cc; color: #0066cc; }
        
        .setup-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .setup-section h3 { margin-top: 0; color: #333; }
        
        .form-row { display: flex; gap: 15px; align-items: end; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group.small { flex: 0 0 100px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group input[type="submit"], .btn { background: #007cba; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        .form-group input[type="submit"]:hover, .btn:hover { background: #005a87; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 5px; }
        
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 5px 10px; font-size: 12px; margin: 2px; }
        
        .search-form { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .search-form input { display: inline-block; width: 300px; margin-right: 10px; }
        
        .accounts-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .accounts-table th, .accounts-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .accounts-table th { background-color: #f2f2f2; font-weight: bold; }
        .accounts-table tr:hover { background-color: #f5f5f5; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        
        .pagination { text-align: center; margin: 20px 0; }
        .pagination a { display: inline-block; padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; }
        .pagination a:hover { background: #f5f5f5; }
        .pagination .current { background: #007cba; color: white; }
        
        .stats { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .quick-actions { margin: 15px 0; }
        .quick-actions button { margin: 5px; }
        
        .currency-list { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏪 Bank Accounts Management</h1>
        
        <!-- Database Connection Status -->
        <div class="status-box <?php echo $tableStatus['status'] === 'no_connection' ? 'status-error' : 'status-info'; ?>">
            <strong>Database Connection Status:</strong> 
            <?php if ($tableStatus['status'] === 'no_connection'): ?>
                ❌ Not Connected
                <p>Database functionality is currently unavailable. Please check your database configuration.</p>
            <?php else: ?>
                ✅ Connected
            <?php endif; ?>
        </div>
        
        <!-- Messages -->
        <?php if (isset($message)): ?>
            <div class="status-box status-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="status-box status-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($tableStatus['status'] !== 'no_connection'): ?>
            
            <!-- Table Status and Setup -->
            <?php if ($tableStatus['status'] === 'missing'): ?>
                <div class="setup-section">
                    <h3>🔧 Initial Setup Required</h3>
                    <p>The bank accounts table needs to be created before you can manage bank accounts.</p>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="create_table">
                        <input type="submit" value="Create Bank Accounts Table" class="btn">
                    </form>
                </div>
                
            <?php elseif ($tableStatus['status'] === 'empty'): ?>
                <div class="setup-section">
                    <h3>📋 Prepopulate Sample Accounts</h3>
                    <p>The bank accounts table is empty. Would you like to add sample Canadian bank accounts?</p>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="prepopulate">
                        <input type="submit" value="Add Sample Canadian Bank Accounts" class="btn">
                    </form>
                </div>
                
            <?php else: ?>
                <!-- Table Status Info -->
                <div class="stats">
                    <strong>Table Status:</strong> <?php echo htmlspecialchars($tableStatus['message']); ?>
                    <br><strong>Unique Banks:</strong> <?php echo count($uniqueBanks); ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="prepopulate">
                        <button type="submit" class="btn btn-secondary">Add More Sample Accounts</button>
                    </form>
                    <a href="bank_import.php" class="btn btn-secondary">Import Bank CSV</a>
                </div>
            <?php endif; ?>
            
            <?php if ($tableStatus['status'] === 'ready'): ?>
                
                <!-- Search Form -->
                <form method="get" class="search-form">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search bank name, account number, or nickname...">
                    <input type="submit" value="Search" class="btn">
                    <?php if (!empty($search)): ?>
                        <a href="?" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
                
                <!-- Add/Edit Form -->
                <div class="setup-section">
                    <h3><?php echo $editAccount ? 'Edit Bank Account' : 'Add New Bank Account'; ?></h3>
                    <form method="post">
                        <input type="hidden" name="action" value="<?php echo $editAccount ? 'update' : 'create'; ?>">
                        <?php if ($editAccount): ?>
                            <input type="hidden" name="id" value="<?php echo $editAccount['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bank_name">Bank Name:</label>
                                <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($editAccount['bank_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="account_number">Account Number:</label>
                                <input type="text" id="account_number" name="account_number" value="<?php echo htmlspecialchars($editAccount['account_number'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nickname">Account Nickname:</label>
                                <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($editAccount['account_nickname'] ?? ''); ?>" placeholder="Optional friendly name">
                            </div>
                            <div class="form-group">
                                <label for="account_type">Account Type:</label>
                                <select id="account_type" name="account_type">
                                    <option value="">Select Type</option>
                                    <option value="Investment Account" <?php echo ($editAccount['account_type'] ?? '') === 'Investment Account' ? 'selected' : ''; ?>>Investment Account</option>
                                    <option value="Checking Account" <?php echo ($editAccount['account_type'] ?? '') === 'Checking Account' ? 'selected' : ''; ?>>Checking Account</option>
                                    <option value="Savings Account" <?php echo ($editAccount['account_type'] ?? '') === 'Savings Account' ? 'selected' : ''; ?>>Savings Account</option>
                                    <option value="TFSA" <?php echo ($editAccount['account_type'] ?? '') === 'TFSA' ? 'selected' : ''; ?>>TFSA</option>
                                    <option value="RRSP" <?php echo ($editAccount['account_type'] ?? '') === 'RRSP' ? 'selected' : ''; ?>>RRSP</option>
                                    <option value="Other" <?php echo ($editAccount['account_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group small">
                                <label for="currency">Currency:</label>
                                <select id="currency" name="currency">
                                    <option value="CAD" <?php echo ($editAccount['currency'] ?? 'CAD') === 'CAD' ? 'selected' : ''; ?>>CAD</option>
                                    <option value="USD" <?php echo ($editAccount['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                                    <option value="EUR" <?php echo ($editAccount['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                    <option value="GBP" <?php echo ($editAccount['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" <?php echo (!isset($editAccount) || $editAccount['is_active']) ? 'checked' : ''; ?>>
                                Account is active
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <input type="submit" value="<?php echo $editAccount ? 'Update Account' : 'Add Account'; ?>" class="btn">
                            <?php if ($editAccount): ?>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Bank Accounts List -->
                <?php if (!empty($bankAccounts)): ?>
                    <h3>Bank Accounts (<?php echo $totalCount; ?> total)</h3>
                    
                    <table class="accounts-table">
                        <thead>
                            <tr>
                                <th>Bank</th>
                                <th>Account Number</th>
                                <th>Nickname</th>
                                <th>Type</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bankAccounts as $account): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($account['bank_name']); ?></td>
                                    <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                    <td><?php echo htmlspecialchars($account['account_nickname'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($account['account_type'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($account['currency']); ?></td>
                                    <td>
                                        <span class="<?php echo $account['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $account['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $account['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this bank account?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $account['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <p><em>No bank accounts found<?php echo !empty($search) ? ' matching your search' : ''; ?>.</em></p>
                <?php endif; ?>
                
            <?php endif; ?>
            
        <?php endif; ?>
        
        <!-- Documentation Link -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><strong>Related:</strong> 
                <a href="admin_account_types.php">Account Types Management</a> | 
                <a href="admin_brokerages_enhanced.php">Brokerages Management</a> | 
                <a href="bank_import.php">Bank CSV Import</a>
            </p>
        </div>
    </div>
</body>
</html>
