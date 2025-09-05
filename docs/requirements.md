# Requirements Document

## Functional Requirements
1. **Per-Symbol Table Management**
   - System must create, manage, and remove 7 tables per stock symbol.
   - Must support adding, activating, deactivating, and removing symbols.
2. **Data Migration**
   - Must migrate data from legacy monolithic tables to per-symbol tables.
   - Must support dry-run and batch migration.
3. **Distributed Job Processing**
   - Must process jobs across multiple machines and queue backends (MQTT, Redis, RabbitMQ, DB).
   - Must support worker registration, monitoring, and job assignment.
4. **CLI Management**
   - Must provide CLI scripts for symbol management, bulk import, migration, and maintenance.
5. **Monitoring**
   - Must provide a dashboard and REST API for system health and job status.

## Non-Functional Requirements
- **Performance:** Table operations must scale to thousands of symbols without filesystem issues.
- **Reliability:** System must prevent data loss and support granular backup/restore.
- **Security:** Input validation, confirmation for destructive actions, and secure config management.
- **Extensibility:** New table types and data fields must be easy to add.
- **Testability:** All business logic must be unit tested with PHPUnit.
- **Documentation:** All scripts, classes, and APIs must be documented.

## Constraints
- PHP 8+, MySQL/MariaDB, Mosquitto (MQTT), Composer, PHPUnit
- Must run on Linux and Windows
- Must support legacy and new code integration
