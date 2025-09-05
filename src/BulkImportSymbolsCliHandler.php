<?php
require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';
require_once __DIR__ . '/IStockTableManager.php';
require_once __DIR__ . '/AddSymbolAction.php';
require_once __DIR__ . '/BulkImportSymbolsAction.php';

class BulkImportSymbolsCliHandler
{
    public function run($argv)
    {
        $options = [];
        $dryRun = false;
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            if ($arg === '--dry-run') {
                $dryRun = true;
            } elseif (strpos($arg, '--file=') === 0) {
                $options['file'] = substr($arg, 7);
            } elseif (strpos($arg, '--symbols=') === 0) {
                $options['symbols'] = substr($arg, 10);
            }
        }
        if (empty($options)) {
            echo "Error: No symbols specified\n";
            exit(1);
        }
        $symbols = [];
        if (isset($options['file'])) {
            $filename = $options['file'];
            if (!file_exists($filename)) {
                echo "File not found: {$filename}\n";
                exit(1);
            }
            $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;
                $symbols[] = strtoupper($line);
            }
        }
        if (isset($options['symbols'])) {
            $cmdSymbols = array_map('trim', explode(',', $options['symbols']));
            $cmdSymbols = array_map('strtoupper', $cmdSymbols);
            $symbols = array_merge($symbols, $cmdSymbols);
        }
        $symbols = array_unique($symbols);
        $symbols = array_filter($symbols, function($symbol) {
            return preg_match('/^[A-Z0-9]{1,10}$/', $symbol);
        });
        if (empty($symbols)) {
            echo "No valid symbols found\n";
            exit(1);
        }
        DatabaseConfig::load();
        $tableManager = new StockTableManager();
        $addSymbolAction = new AddSymbolAction($tableManager);
        $action = new BulkImportSymbolsAction($tableManager, $addSymbolAction);
        $results = $action->execute($symbols, $dryRun);
        echo "\n=== BULK IMPORT SUMMARY ===\n";
        echo "Total symbols processed: " . count($symbols) . "\n";
        echo "Already existed: " . count($results['existing']) . "\n";
        echo "Newly created: " . count($results['created']) . "\n";
        echo "Errors: " . count($results['errors']) . "\n";
        if (!empty($results['created'])) {
            echo "\nNewly created symbols:\n";
            foreach ($results['created'] as $symbol) {
                echo "  ✓ {$symbol}\n";
            }
        }
        if (!empty($results['existing'])) {
            echo "\nSymbols that already existed:\n";
            foreach ($results['existing'] as $symbol) {
                echo "  → {$symbol}\n";
            }
        }
        if (!empty($results['errors'])) {
            echo "\nErrors encountered:\n";
            foreach ($results['errors'] as $error) {
                echo "  ✗ {$error['symbol']}: {$error['error']}\n";
            }
        }
    }
}
