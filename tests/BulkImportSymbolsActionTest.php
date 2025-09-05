<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/BulkImportSymbolsAction.php';
require_once __DIR__ . '/../src/AddSymbolAction.php';
require_once __DIR__ . '/../src/TableTypeRegistry.php';
require_once __DIR__ . '/../src/IStockTableManager.php';

/**
 * @covers BulkImportSymbolsAction
 */
class BulkImportSymbolsActionTest extends TestCase
{
    public function testExecuteDryRun()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['IBM', 'AAPL'];
        $results = $action->execute($symbols, true);
        $this->assertEquals(['IBM', 'AAPL'], $results['created']);
    }

    public function testExecuteCreatesAndExists()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $mockAddSymbolAction->method('execute')->willReturnOnConsecutiveCalls(
            ['status' => 'created', 'symbol' => 'IBM'],
            ['status' => 'exists', 'symbol' => 'AAPL']
        );
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['IBM', 'AAPL'];
        $results = $action->execute($symbols, false);
        $this->assertEquals(['IBM'], $results['created']);
        $this->assertEquals(['AAPL'], $results['existing']);
    }

    public function testExecuteInvalidSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['ibm!'];
        $results = $action->execute($symbols, false);
        $this->assertNotEmpty($results['errors']);
    }
}
