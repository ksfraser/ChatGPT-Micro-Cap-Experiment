# PHP Code Quality Review: SOLID, DRY, DI & Best Practices Analysis

## Executive Summary

The codebase has a solid foundation but requires significant refactoring to align with modern PHP best practices. Key areas for improvement include dependency injection, separation of concerns, error handling, and adherence to SOLID principles.

## Critical Issues Identified

### 1. **Dependency Injection (DI) Violations**

#### Current State:
- Hard-coded database configuration class names (`'LegacyDatabaseConfig'`)
- Direct instantiation of dependencies within constructors
- Static calls to configuration classes
- Global functions mixed with class-based code

#### Examples:
```php
// MidCapBankImportDAO.php
public function __construct() {
    parent::__construct('LegacyDatabaseConfig');  // Hard-coded dependency
    $migrator = new SchemaMigrator($this->pdo, $schemaDir);  // Direct instantiation
}

// DbConfigClasses.php
function ensureApiKeysTable() {  // Global function
    $pdo = LegacyDatabaseConfig::createConnection();  // Static call
}
```

### 2. **Single Responsibility Principle (SRP) Violations**

#### Issues:
- `MidCapBankImportDAO` handles parsing, staging, migration, AND database operations
- `CommonDAO` mixes database connection, CSV operations, and error logging
- `PortfolioDAO` handles both database and CSV operations, plus session management
- `bank_import.php` contains business logic mixed with presentation layer

#### Example:
```php
// MidCapBankImportDAO does too much:
class MidCapBankImportDAO extends CommonDAO {
    public function parseAccountHoldingsCSV($filePath) { /* CSV parsing */ }
    public function saveStagingCSV($rows, $type) { /* File operations */ }
    public function importToMidCap($rows, $type) { /* Business logic */ }
    private function insertTransaction($table, $row) { /* Database operations */ }
    public function identifyBankAccount($rows) { /* Data analysis */ }
}
```

### 3. **Open/Closed Principle (OCP) Violations**

#### Issues:
- Configuration classes have repetitive code for different database types
- DAO classes are not easily extensible for new data sources
- Hard-coded table names and field mappings

### 4. **Interface Segregation & Liskov Substitution Issues**

#### Problems:
- No interfaces defined - classes depend on concrete implementations
- Abstract `CommonDAO` doesn't define clear contracts
- Inheritance used where composition would be better

### 5. **DRY (Don't Repeat Yourself) Violations**

#### Examples:
```php
// Repeated database connection logic in LegacyDatabaseConfig and MicroCapDatabaseConfig
public static function createConnection() {
    $c = static::getConfig();
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', 
                   $c['host'], $c['port'], $c['dbname'], $c['charset']);
    $options = [/* same options */];
    return new PDO($dsn, $c['username'], $c['password'], $options);
}

// Repeated CSV parsing logic
// MidCapBankImportDAO::parseAccountHoldingsCSV vs parseTransactionHistoryCSV
// Both have identical parsing logic
```

### 6. **Error Handling & Logging Issues**

#### Problems:
- Inconsistent error handling strategies
- Silent failures (empty catch blocks)
- Error logging mixed with business logic
- No centralized error management

### 7. **Security Concerns**

#### Issues:
- Direct SQL queries without consistent parameterization
- File upload handling without proper validation
- Session management scattered across classes
- HTML output not consistently escaped

### 8. **Architecture & Design Issues**

#### Problems:
- Tight coupling between layers
- No clear separation between business logic and presentation
- Global state usage (sessions, globals)
- Lack of proper MVC structure

## Recommended Refactoring Plan

### Phase 1: Foundation - Dependency Injection & Interfaces

#### 1.1 Create Core Interfaces
```php
interface DatabaseConnectionInterface {
    public function getConnection(): PDO;
}

interface ConfigurationInterface {
    public function get(string $key, $default = null);
}

interface LoggerInterface {
    public function error(string $message, array $context = []);
    public function info(string $message, array $context = []);
}

interface CsvParserInterface {
    public function parse(string $filePath): array;
}

interface DataRepositoryInterface {
    public function save(array $data): bool;
    public function findAll(): array;
    public function findBy(array $criteria): array;
}
```

#### 1.2 Implement Dependency Injection Container
```php
class DIContainer {
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, callable $concrete): void;
    public function singleton(string $abstract, callable $concrete): void;
    public function resolve(string $abstract);
}
```

### Phase 2: Refactor Core Classes

#### 2.1 Database Configuration Refactoring
```php
class DatabaseConfigurationFactory {
    public static function create(string $type, ConfigurationInterface $config): DatabaseConnectionInterface;
}

class MysqlDatabaseConnection implements DatabaseConnectionInterface {
    public function __construct(private ConfigurationInterface $config) {}
    public function getConnection(): PDO;
}
```

#### 2.2 Separate Concerns in DAOs
```php
// Separate CSV operations
class CsvParser implements CsvParserInterface {
    public function parse(string $filePath): array;
}

// Separate repository pattern
class TransactionRepository implements DataRepositoryInterface {
    public function __construct(
        private DatabaseConnectionInterface $database,
        private LoggerInterface $logger
    ) {}
}

// Separate business logic
class BankImportService {
    public function __construct(
        private CsvParserInterface $parser,
        private DataRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}
    
    public function importFromCsv(string $filePath, string $type): ImportResult;
}
```

### Phase 3: Error Handling & Logging

#### 3.1 Centralized Error Handling
```php
class ErrorHandler {
    public function __construct(private LoggerInterface $logger) {}
    
    public function handleException(Throwable $exception): ErrorResponse;
    public function handleError(int $level, string $message, string $file, int $line): bool;
}

class ImportResult {
    public function __construct(
        private bool $success,
        private array $errors = [],
        private array $warnings = [],
        private int $processedCount = 0
    ) {}
}
```

### Phase 4: Security & Validation

#### 4.1 Input Validation
```php
interface ValidatorInterface {
    public function validate($data): ValidationResult;
}

class CsvFileValidator implements ValidatorInterface {
    public function validate($data): ValidationResult;
}

class TransactionDataValidator implements ValidatorInterface {
    public function validate($data): ValidationResult;
}
```

#### 4.2 Secure File Handling
```php
class SecureFileUploadHandler {
    public function handle(array $uploadedFile): UploadResult;
    private function validateFileType(string $filename): bool;
    private function sanitizeFilename(string $filename): string;
}
```

### Phase 5: Presentation Layer Refactoring

#### 5.1 MVC Structure
```php
abstract class BaseController {
    protected function render(string $template, array $data = []): void;
    protected function redirect(string $url): void;
    protected function json($data): void;
}

class BankImportController extends BaseController {
    public function __construct(
        private BankImportService $importService,
        private ValidatorInterface $validator
    ) {}
    
    public function upload(): void;
    public function confirm(): void;
}
```

### Phase 6: Testing Infrastructure

#### 6.1 Testable Architecture
- All dependencies injected via constructor
- Interfaces for all external dependencies
- No static calls or global state
- Proper mocking capabilities

## Implementation Priority

### High Priority (Security & Stability)
1. ✅ **Input validation for file uploads**
2. ✅ **Consistent SQL parameterization** 
3. ✅ **Error handling standardization**
4. ✅ **Session security improvements**

### Medium Priority (Architecture)
1. **Dependency injection implementation**
2. **Interface segregation**
3. **Repository pattern adoption**
4. **Service layer extraction**

### Low Priority (Optimization)
1. **Performance improvements**
2. **Caching layer**
3. **Advanced logging features**
4. **Database optimization**

## Specific File Recommendations

### Immediate Actions Required:

#### 1. DbConfigClasses.php
- Extract global function to proper class
- Implement configuration interface
- Add proper error handling
- Remove code duplication

#### 2. MidCapBankImportDAO.php
- Split into multiple classes (Parser, Repository, Service)
- Inject dependencies instead of hard-coding
- Extract field mapping to configuration
- Add proper validation

#### 3. CommonDAO.php
- Define interfaces for database operations
- Separate CSV operations to dedicated class
- Improve error handling
- Remove mixed responsibilities

#### 4. bank_import.php
- Extract business logic to service classes
- Implement proper MVC pattern
- Add input validation
- Secure file upload handling

#### 5. admin_brokerages.php
- Add CSRF protection
- Implement proper validation
- Extract business logic
- Add error handling

## Conclusion

The codebase requires significant refactoring to meet modern PHP standards. The proposed changes will improve maintainability, testability, security, and scalability. Implementation should be done incrementally, starting with the highest priority security and stability issues.
