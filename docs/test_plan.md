# Test Plan

## Test Strategy
- **Unit Testing:** All business logic in `/src` covered by PHPUnit tests.
- **Integration Testing:** CLI scripts tested with real DB and mock data.
- **Regression Testing:** Run full test suite after each major change.
- **Manual Testing:** Symbol management, migration, and destructive actions require manual confirmation.

## Test Cases
### 1. TableTypeRegistry
- Valid/invalid symbol validation
- Table type list integrity

### 2. AddSymbolAction
- Add new symbol (creates tables)
- Add existing symbol (no duplicate tables)
- Invalid symbol (throws exception)

### 3. BulkImportSymbolsAction
- Import from file/CLI
- Dry-run mode
- Handles invalid symbols

### 4. MigrateSymbolAction
- Migrates data for valid symbol
- Skips invalid symbol
- Handles missing/empty legacy tables

### 5. ManageSymbolsAction
- List, stats, check, remove, activate, deactivate, cleanup
- Handles missing/invalid symbols

## Acceptance Criteria
- 100% of business logic covered by unit tests
- All destructive actions require confirmation or `--force`
- Migration script supports dry-run and batch size
- CLI scripts exit with error on invalid input

## Test Data
- Use mock and real symbols: IBM, AAPL, MSFT, GOOGL, etc.
- Legacy tables with sample data for migration

## Tools
- PHPUnit for unit tests
- Manual CLI for integration/destructive tests
