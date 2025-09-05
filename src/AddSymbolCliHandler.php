<?php
require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';
require_once __DIR__ . '/IStockTableManager.php';
require_once __DIR__ . '/AddSymbolAction.php';

class AddSymbolCliHandler
{
    public function run($argv)
    {
        if (count($argv) < 2) {
            echo "Usage: php AddNewSymbol.php SYMBOL\n";
            exit(1);
        }
        $symbol = strtoupper(trim($argv[1]));
        DatabaseConfig::load();
        $tableManager = new StockTableManager();
        $action = new AddSymbolAction($tableManager);
        try {
            $result = $action->execute($symbol, [
                'company_name' => '',
                'sector' => '',
                'industry' => '',
                'market_cap' => 'micro',
                'active' => true
            ]);
            if ($result['status'] === 'created') {
                echo "Symbol {$symbol} registered and tables created.\n";
            } else {
                echo "Symbol {$symbol} already exists. Tables checked.\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}
