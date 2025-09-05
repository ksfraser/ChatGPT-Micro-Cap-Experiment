<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/MigrateSymbolAction.php';
require_once __DIR__ . '/../src/TableTypeRegistry.php';
require_once __DIR__ . '/../src/IStockTableManager.php';
require_once __DIR__ . '/../src/IStockDataAccess.php';

/**
 * @covers MigrateSymbolAction
 */
class MigrateSymbolActionTest extends TestCase
{
    public function testExecuteInvalidSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockDataAccess = $this->createMock(IStockDataAccess::class);
    $mockPdo = $this->createMock(PDO::class);
    $action = new MigrateSymbolAction($mockTableManager, $mockDataAccess, $mockPdo);
        $result = $action->execute('ibm!', [], ['dry_run' => true]);
        $this->assertContains('Invalid symbol format', $result['errors']);
    }
}
