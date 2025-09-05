<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/ManageSymbolsAction.php';
require_once __DIR__ . '/../src/IStockTableManager.php';

/**
 * @covers ManageSymbolsAction
 */
class ManageSymbolsActionTest extends TestCase
{
    public function testListSymbolsReturnsAll()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([
            ['symbol' => 'IBM', 'active' => true],
            ['symbol' => 'AAPL', 'active' => false]
        ]);
    $mockPdo = $this->createMock(PDO::class);
    $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $symbols = $action->listSymbols();
        $this->assertCount(2, $symbols);
        $this->assertEquals('IBM', $symbols[0]['symbol']);
    }

    public function testStatsReturnsNullIfNotFound()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getSymbolTableStats')->willReturn(null);
    $mockPdo = $this->createMock(PDO::class);
    $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $this->assertNull($action->stats('FAKE'));
    }

    public function testCheckReturnsExistsAndStats()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('tablesExistForSymbol')->willReturn(true);
        $mockTableManager->method('getSymbolTableStats')->willReturn(['row_count' => 5]);
    $mockPdo = $this->createMock(PDO::class);
    $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $results = $action->check(['IBM'], true);
        $this->assertTrue($results[0]['exists']);
        $this->assertEquals(5, $results[0]['stats']['row_count']);
    }

    public function testRemoveCallsManager()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->expects($this->once())->method('removeTablesForSymbol')->with('IBM', true)->willReturn(true);
    $mockPdo = $this->createMock(PDO::class);
    $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $this->assertTrue($action->remove('IBM'));
    }
}
