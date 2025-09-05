# Test Plan

## Test Strategy
- **Unit Testing:** All business logic in `/src` covered by PHPUnit tests.
- **Integration Testing:** CLI scripts tested with real DB and mock data.
- **Regression Testing:** Run full test suite after each major change.
- **Manual Testing:** Symbol management, migration, and destructive actions require manual confirmation.

### 1. TableTypeRegistry

### 2. AddSymbolAction

### 3. BulkImportSymbolsAction

### 4. MigrateSymbolAction

### 5. ManageSymbolsAction

## Acceptance Criteria

## Test Data

## Tools

### 6. CLI Handlers (e.g., MigrateSymbolsCliHandler)
- CLI argument parsing and exit/side-effect logic is not unit-testable due to PHP limitations (e.g., exit()).
- Covered by integration and manual tests.

## Acceptance Criteria
- 100% of business logic covered by unit tests
- CLI handler exit/side-effect logic covered by integration/manual tests
- All destructive actions require confirmation or `--force`
- Migration script supports dry-run and batch size
- CLI scripts exit with error on invalid input
