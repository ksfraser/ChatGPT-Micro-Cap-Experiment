<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/TableTypeRegistry.php';

/**
 * @covers TableTypeRegistry
 */
class TableTypeRegistryTest extends TestCase
{
    public function testIsValidSymbolValid()
    {
        $this->assertTrue(TableTypeRegistry::isValidSymbol('IBM'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('AAPL'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('GOOGL'));
        $this->assertTrue(TableTypeRegistry::isValidSymbol('TSLA1'));
    }

    public function testIsValidSymbolInvalid()
    {
        $this->assertFalse(TableTypeRegistry::isValidSymbol('ibm'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('AAPL!'));
        $this->assertFalse(TableTypeRegistry::isValidSymbol(''));
        $this->assertFalse(TableTypeRegistry::isValidSymbol('TOOLONGSYMBOL123'));
    }
}
