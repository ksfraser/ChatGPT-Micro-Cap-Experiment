# Enhanced Database Abstraction Layer
## Micro-Cap Trading System - Database Module

### Overview
This enhanced database abstraction layer provides PDO-first, MySQLi-fallback database connectivity for the micro-cap trading system. It's designed to be spun off into a separate repository (ksfraser/Database) while maintaining backward compatibility with existing code.

### Architecture

#### 1. **Multi-Driver Support**
- **Primary**: PDO with MySQL driver
- **Fallback 1**: MySQLi for MySQL connectivity  
- **Fallback 2**: PDO with SQLite for development/testing

#### 2. **Automatic Driver Detection**
The system automatically detects available PHP extensions and chooses the best available driver:

```php
Priority Order:
1. PDO MySQL (optimal performance)
2. MySQLi (MySQL fallback)
3. PDO SQLite (development/testing)
```

#### 3. **Unified Interface**
All database connections implement consistent interfaces:
- `DatabaseConnectionInterface`: Connection management
- `DatabaseStatementInterface`: Statement execution

### Component Structure

```
src/Ksfraser/Database/
├── EnhancedDbManager.php          # Main database manager
├── PdoConnection.php              # PDO MySQL implementation
├── MysqliConnection.php           # MySQLi fallback implementation
├── PdoSqliteConnection.php        # SQLite fallback implementation
└── EnhancedUserAuthDAO.php        # Enhanced user authentication
```

### Configuration Support

The system supports multiple configuration formats:

#### INI Format (Recommended)
```ini
[database]
host = fhsws001.ksfraser.com
port = 3306
username = stocks
password = stocks
charset = utf8mb4

[database.legacy]
database = stock_market_2

[database.micro_cap]
database = stock_market_micro_cap_trading
```

#### YAML Format
```yaml
database:
  host: fhsws001.ksfraser.com
  port: 3306
  username: stocks
  password: stocks
  charset: utf8mb4
  legacy:
    database: stock_market_2
  micro_cap:
    database: stock_market_micro_cap_trading
```

### Key Features

#### 1. **Automatic Fallback**
- Graceful degradation when preferred drivers aren't available
- No code changes required for different environments
- Consistent API across all connection types

#### 2. **Backward Compatibility**
- Drop-in replacement for existing `UserAuthDAO`
- Maintains all existing method signatures
- Legacy configuration support

#### 3. **Enhanced Error Handling**
- Comprehensive exception handling
- Detailed error logging
- Graceful failure modes

#### 4. **Testing Support**
- In-memory SQLite for unit testing
- Comprehensive test suite included
- Driver availability detection

### Usage Examples

#### Basic Connection
```php
use Ksfraser\Database\EnhancedDbManager;

// Get connection (automatically selects best driver)
$connection = EnhancedDbManager::getConnection();

// Execute query
$users = EnhancedDbManager::fetchAll("SELECT * FROM users");
```

#### User Authentication
```php
// Drop-in replacement for existing UserAuthDAO
$auth = new UserAuthDAO(); // Now uses enhanced system

// All existing methods work the same
$userId = $auth->registerUser($username, $email, $password);
$users = $auth->getAllUsers();
$isLoggedIn = $auth->isLoggedIn();
```

#### Driver Information
```php
$auth = new UserAuthDAO();
$info = $auth->getDatabaseInfo();

echo "Using driver: " . $info['driver'];
// Output: "Using driver: pdo_mysql" or "mysqli" or "pdo_sqlite"
```

### Deployment

#### For Micro-Cap System
1. **Immediate**: Files are ready in `src/Ksfraser/Database/`
2. **Migration**: `web_ui/EnhancedUserAuthDAO.php` provides backward compatibility
3. **Testing**: `web_ui/test_simple_db.php` validates functionality

#### For Separate Repository
The code is structured to be easily extracted to `ksfraser/Database`:

```bash
# Repository structure for ksfraser/Database
├── src/Ksfraser/Database/
│   ├── EnhancedDbManager.php
│   ├── Connections/
│   │   ├── PdoConnection.php
│   │   ├── MysqliConnection.php
│   │   └── PdoSqliteConnection.php
│   └── DAO/
│       └── EnhancedUserAuthDAO.php
├── tests/
├── composer.json
└── README.md
```

### Compatibility Matrix

| Environment | PDO MySQL | MySQLi | SQLite | Status |
|-------------|-----------|--------|--------|---------|
| Production (F30 Apache) | ✅ | ✅ | ✅ | Optimal |
| Development (Windows) | ❌ | ✅ | ✅ | Fallback |
| Testing | ❌ | ❌ | ✅ | SQLite |
| CI/CD | ✅ | ✅ | ✅ | Full |

### Troubleshooting

#### Common Issues

1. **"No suitable database driver available"**
   - Install `php-pdo`, `php-mysql`, or `php-sqlite` extensions
   - Enable extensions in `php.ini`

2. **"Could not find driver"**
   - Enable `extension=pdo_mysql` in `php.ini`
   - Restart web server after changes

3. **Connection timeouts**
   - Check database server connectivity
   - Verify firewall settings
   - Confirm credentials in configuration

#### Debugging

```php
// Check available drivers
$drivers = PDO::getAvailableDrivers();
var_dump($drivers);

// Test specific connection
try {
    $connection = EnhancedDbManager::getConnection();
    echo "Using: " . EnhancedDbManager::getCurrentDriver();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Next Steps

1. **Testing**: Run `test_simple_db.php` to validate functionality
2. **Integration**: Replace existing `UserAuthDAO` usage gradually  
3. **Monitoring**: Add logging for driver selection and performance
4. **Optimization**: Fine-tune connection pooling and caching

### License
GPL-3.0 (compatible with existing ksfraser/Database repository)

### Contributing
This module is designed to be contributed back to the main ksfraser/Database repository for broader use across projects.
